<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\CompatibilityService;
use App\Services\CompatibilityServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CompatibilityServiceInterface::class,
            CompatibilityService::class
        );
    }

    public function boot(): void
    {
        //
    }
}