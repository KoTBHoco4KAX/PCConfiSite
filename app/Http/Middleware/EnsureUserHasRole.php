<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Пропускает запрос, если у пользователя есть хотя бы одна из перечисленных ролей.
     *
     * Использование: ->middleware('role:manager,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Неавторизован.',
            ], 401);
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Доступ запрещён. Требуется одна из ролей: ' . implode(', ', $roles) . '.',
        ], 403);
    }
}