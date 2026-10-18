<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message'               => 'Подтвердите email — без этого нельзя сохранять и оформлять заказы.',
                'requires_verification' => true,
            ], 403);
        }

        return $next($request);
    }
}