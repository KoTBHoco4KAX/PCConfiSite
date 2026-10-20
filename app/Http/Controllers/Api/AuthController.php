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

            // firstOrCreate защитит от ошибки, если роли вдруг нет в БД
            $buyerRole = Role::firstOrCreate(['slug' => 'buyer'], ['name' => 'Покупатель']);
            $user->roles()->attach($buyerRole->id);

            return $user;
        });

        $user->sendEmailVerificationNotification();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user'  => $this->serializeUser($user->load('roles')),
            'token' => $token,
            'message' => 'Регистрация успешна. Проверьте почту.',
        ], 201);
    }

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

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->serializeUser($request->user()->load('roles')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token) $token->delete();
        return response()->json(['message' => 'Выход выполнен.']);
    }

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

        // МАГИЯ ЗДЕСЬ: Берем текущий домен, с которого пришел запрос (т.е. твой slow-cat...)
        // И перекидываем туда, а не на localhost.
        $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');

        return redirect($baseUrl . '/?verified=1');
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Почта уже подтверждена.'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Письмо отправлено повторно.']);
    }

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