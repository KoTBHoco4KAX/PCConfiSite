<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Services\CompatibilityServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BuilderController extends Controller
{
    public function __construct(
        private readonly CompatibilityServiceInterface $compatibilityService,
    ) {
    }

    public function auto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_budget'        => ['sometimes', 'integer', 'min:0', 'max:10000000'],
            'use_case'             => ['sometimes', 'string', 'in:gaming,office,work,streaming,universal'],
            'game_type'            => ['sometimes', 'string'],
            'monitor_resolution'   => ['sometimes', 'string', 'in:1080p,1440p,4k,auto'],
            'monitor_refresh_rate' => ['sometimes', 'string'],
            'cpu_vendor'           => ['sometimes', 'string', 'in:amd,intel,auto'],
            'gpu_vendor'           => ['sometimes', 'string', 'in:nvidia,amd,intel,auto'],
            'ram_size'             => ['sometimes'],
            'storage_type'         => ['sometimes', 'string', 'in:nvme,sata,hdd,combo,auto'],
            'storage_size'         => ['sometimes'],
            'color'                => ['sometimes', 'string'],
            'cooling'              => ['sometimes', 'string', 'in:air,water,auto'],
            'case'                 => ['sometimes', 'string', 'in:mid-tower,mini-itx,aquarium,auto'],
        ]);

        $params = array_filter(
            $validated,
            static fn ($v) => $v !== 'auto' && $v !== 'any' && $v !== '' && $v !== null
        );

        $result = $this->compatibilityService->autoBuild($params);

        return response()->json($this->normalizeBuild($result));
    }

    public function validate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids'   => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $result = $this->compatibilityService->validateConfiguration($validated['product_ids']);

        return response()->json([
            'is_valid'          => (bool) ($result['is_valid'] ?? $result['is_compatible'] ?? false),
            'errors'            => $result['errors'] ?? [],
            'warnings'          => $result['warnings'] ?? [],
            'total_price'       => $result['total_price'] ?? 0,
            'estimated_wattage' => $result['estimated_wattage'] ?? $result['base_estimated_tdp'] ?? 0,
        ]);
    }

    private function normalizeBuild(array $result): array
    {
        return [
            'config_applied' => $result['config_applied'] ?? $result['config'] ?? [],
            'components'     => $result['components'] ?? [],
            'total_price'    => $result['total_price'] ?? 0,
            'budget'         => $result['budget'] ?? null,
            'validation'     => [
                'is_valid'                => (bool) ($result['validation']['is_valid'] ?? $result['validation']['is_compatible'] ?? false),
                'errors'                  => $result['validation']['errors'] ?? [],
                'warnings'                => $result['validation']['warnings'] ?? [],
                'estimated_wattage'       => $result['validation']['estimated_wattage'] ?? $result['validation']['base_estimated_tdp'] ?? 0,
                'recommended_psu_wattage' => $result['validation']['recommended_psu_wattage'] ?? 0,
            ],
        ];
    }
}