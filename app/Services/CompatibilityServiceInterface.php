<?php

declare(strict_types=1);

namespace App\Services;

interface CompatibilityServiceInterface
{
    public function generateShadowBaseline(
        string $budget = 'mid_low',
        string $useCase = 'gaming'
    ): array;

    public function calculateRecommendedPsuWattage(
        int $cpuTdp,
        int $gpuTdp = 0
    ): array;

    public function validateConfiguration(array $productIds): array;

    public function autoBuild(array $params = []): array;
}