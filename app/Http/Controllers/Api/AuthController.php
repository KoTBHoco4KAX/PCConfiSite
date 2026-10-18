<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $buyerRole = Role::where('slug', 'buyer')->first();
            if ($buyerRole) {
                $user->roles()->attach($buyerRole->id);
            }

            return $user;
        });

        // Письмо с подтверждением
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $this->serializeUser($user->load('roles')),
            'token' => $token,
            'message' => 'Регистрация успешна. Проверьте почту для подтверждения.',
        ], 201);
    }

    /**
     * POST /api/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверные учётные данные.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $this->serializeUser($user->load('roles')),
            'token' => $token,
        ]);
    }

    /**
     * GET /api/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->serializeUser($request->user()->load('roles')),
        ]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Выход выполнен.']);
    }

    /**
     * GET /api/auth/email/verify/{id}/{hash}?expires=...&signature=...
     */
    public function verifyEmail(Request $request, int $id, string $hash): RedirectResponse|JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'Пользователь не найден.'], 404);
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Неверная ссылка подтверждения.'], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $frontend = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');

        return redirect($frontend . '/?verified=1');
    }

    /**
     * POST /api/auth/email/resend
     */
    public function resendVerification(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Почта уже подтверждена.'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Письмо отправлено повторно.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeUser(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'roles'             => $user->roles->pluck('slug')->all(),
        ];
    }
}