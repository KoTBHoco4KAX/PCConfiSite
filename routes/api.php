<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuilderController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SavedBuildController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->whereNumber('id')
        ->name('verification.verify');
});

Route::prefix('builder')->group(function (): void {
    Route::post('auto',     [BuilderController::class, 'auto']);
    Route::post('validate', [BuilderController::class, 'validate']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::get('me',               [AuthController::class, 'me']);
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::post('email/resend',    [AuthController::class, 'resendVerification']);
    });

    Route::middleware(['role:buyer,seller,admin', 'email.verified'])->group(function (): void {
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders',  [OrderController::class, 'index']);

        Route::post('builds',        [SavedBuildController::class, 'store']);
        Route::get('builds',         [SavedBuildController::class, 'index']);
        Route::get('builds/{id}',    [SavedBuildController::class, 'show'])->whereNumber('id');
        Route::delete('builds/{id}', [SavedBuildController::class, 'destroy'])->whereNumber('id');
    });

    Route::prefix('manage')->middleware(['role:seller,admin', 'email.verified'])->group(function (): void {
        Route::get('orders',               [OrderController::class, 'manageIndex']);
        Route::patch('orders/{id}/status', [OrderController::class, 'updateStatus'])->whereNumber('id');
    });

    Route::prefix('manage')->middleware(['role:admin', 'email.verified'])->group(function (): void {
        Route::get('users',         [UserController::class, 'index']);
        Route::post('users',        [UserController::class, 'store']);
        Route::patch('users/{id}',  [UserController::class, 'update'])->whereNumber('id');
        Route::delete('users/{id}', [UserController::class, 'destroy'])->whereNumber('id');
        Route::get('roles',         [UserController::class, 'roles']);
    });
});