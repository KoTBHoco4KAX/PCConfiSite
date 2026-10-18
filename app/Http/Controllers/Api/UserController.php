<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * GET /api/manage/users
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search'   => ['sometimes', 'string', 'max:255'],
            'role'     => ['sometimes', 'string', 'exists:roles,slug'],
        ]);

        $query = User::with('roles')->orderByDesc('created_at');

        if (! empty($validated['search'])) {
            $s = $validated['search'];
            $query->where(function ($q) use ($s): void {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if (! empty($validated['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('slug', $validated['role']));
        }

        $page = $query->paginate($validated['per_page'] ?? 30);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($u) => $this->serialize($u))->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
                'last_page'    => $page->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/manage/roles
     */
    public function roles(): JsonResponse
    {
        return response()->json([
            'roles' => Role::orderBy('id')->get(['id', 'slug', 'name']),
        ]);
    }

    /**
     * POST /api/manage/users
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles'    => ['required', 'array', 'min:1'],
            'roles.*'  => ['string', 'exists:roles,slug'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $roleIds = Role::whereIn('slug', $validated['roles'])->pluck('id')->all();
            $user->roles()->sync($roleIds);

            return $user;
        });

        return response()->json([
            'user' => $this->serialize($user->load('roles')),
        ], 201);
    }

    /**
     * PATCH /api/manage/users/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::with('roles')->findOrFail($id);

        $validated = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'roles'    => ['sometimes', 'array', 'min:1'],
            'roles.*'  => ['string', 'exists:roles,slug'],
        ]);

        DB::transaction(function () use ($user, $validated, $request): void {
            $patch = [];

            if (isset($validated['name']))  $patch['name']  = $validated['name'];
            if (isset($validated['email'])) $patch['email'] = $validated['email'];
            if (! empty($validated['password'])) {
                $patch['password'] = Hash::make($validated['password']);
            }

            if ($patch) $user->update($patch);

            if (isset($validated['roles'])) {
                $actor = $request->user();
                $selfDemote = $actor && $actor->id === $user->id
                    && ! in_array('admin', $validated['roles'], true);

                if (! $selfDemote) {
                    $roleIds = Role::whereIn('slug', $validated['roles'])->pluck('id')->all();
                    $user->roles()->sync($roleIds);
                }
            }
        });

        return response()->json([
            'user' => $this->serialize($user->fresh('roles')),
        ]);
    }

    /**
     * DELETE /api/manage/users/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::findOrFail($id);

        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'Нельзя удалить самого себя.',
            ], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Пользователь удалён.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'roles'      => $user->roles->pluck('slug')->all(),
            'roles_full' => $user->roles->map(fn ($r) => ['slug' => $r->slug, 'name' => $r->name])->all(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}