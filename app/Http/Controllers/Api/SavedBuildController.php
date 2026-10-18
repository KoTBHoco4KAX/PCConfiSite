<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\SavedBuild;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SavedBuildController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'snapshot'    => ['required', 'array'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $build = SavedBuild::create([
            'user_id'     => $user->id,
            'name'        => $validated['name'],
            'total_price' => (float) $validated['total_price'],
            'snapshot'    => $validated['snapshot'],
            'is_public'   => false,
        ]);

        return response()->json(['build' => $this->serialize($build)], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $builds = SavedBuild::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'builds' => $builds->map(fn ($b) => $this->serialize($b))->all(),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        /** @var SavedBuild $build */
        $build = SavedBuild::findOrFail($id);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($build->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json(['build' => $this->serialize($build, true)]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var SavedBuild $build */
        $build = SavedBuild::findOrFail($id);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if ($build->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $build->delete();

        return response()->json(['message' => 'Черновик удалён.']);
    }

    public function manageIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'user_id'  => ['sometimes', 'integer', 'exists:users,id'],
        ]);

        $query = SavedBuild::query()
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if (! empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        $page = $query->paginate($validated['per_page'] ?? 30);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($b) => $this->serialize($b, true, true))->all(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
                'last_page'    => $page->lastPage(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(SavedBuild $b, bool $withSnapshot = false, bool $withUser = false): array
    {
        $payload = [
            'id'          => $b->id,
            'name'        => $b->name,
            'total_price' => (float) $b->total_price,
            'created_at'  => $b->created_at?->toIso8601String(),
        ];

        if ($withSnapshot) {
            $payload['snapshot'] = $b->snapshot;
        }

        if ($withUser && $b->relationLoaded('user') && $b->user) {
            $payload['user'] = [
                'id'    => $b->user->id,
                'name'  => $b->user->name,
                'email' => $b->user->email,
            ];
        }

        return $payload;
    }
}