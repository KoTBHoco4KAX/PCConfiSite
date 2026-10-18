<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class CompatibilityService implements CompatibilityServiceInterface
{
    public const SYSTEM_BASE_TDP = 75;

    private const BUDGET_CHIPSETS_AMD   = ['A320', 'A520', 'A620'];
    private const BUDGET_CHIPSETS_INTEL = ['H610'];

    private const DEFAULT_TARGET  = 100000;
    private const MIN_TARGET      = 30000;
    private const MAX_TARGET      = 200000;
    private const INFINITY_TARGET = 10000000;

    private const MIN_CPU_PERF = 5;
    private const MIN_GPU_PERF = 5;

    private const INITIAL_WEIGHTS = [
        'cpu'         => 0.22,
        'gpu'         => 0.35,
        'motherboard' => 0.12,
        'ram'         => 0.08,
        'storage'     => 0.08,
        'cooling'     => 0.04,
        'case'        => 0.05,
        'psu'         => 0.06,
    ];

    private const OFFICE_WEIGHTS = [
        'cpu'         => 0.28,
        'motherboard' => 0.14,
        'ram'         => 0.10,
        'storage'     => 0.10,
        'cooling'     => 0.06,
        'case'        => 0.08,
        'psu'         => 0.08,
    ];

    private const UPGRADE_ORDER_DEFAULT = ['gpu', 'cpu', 'motherboard', 'ram', 'storage', 'cooling', 'case', 'psu'];
    private const UPGRADE_ORDER_OFFICE  = ['cpu', 'ram', 'storage', 'motherboard', 'cooling', 'case', 'psu'];

    private const FILL_ITERATIONS    = 40;
    private const FILL_MIN_REMAINDER = 1500;

    public function generateShadowBaseline(string $budget = 'mid_low', string $useCase = 'gaming'): array
    {
        return [
            'budget'             => $budget,
            'use_case'           => $useCase,
            'monitor_resolution' => '1440p',
            'cpu_vendor'         => 'amd',
            'gpu_vendor'         => 'nvidia',
            'ram_size'           => '16',
            'storage_type'       => 'nvme',
            'storage_size'       => '1',
            'color'              => 'black',
            'cooling'            => 'air',
            'case'               => 'mid-tower',
        ];
    }

    public function calculateRecommendedPsuWattage(int $cpuTdp, int $gpuTdp = 0): array
    {
        $totalEstimated = $cpuTdp + $gpuTdp + self::SYSTEM_BASE_TDP;
        $margin = ($gpuTdp >= 250) ? 1.35 : 1.25;
        $targetPower = (int) (ceil(($totalEstimated * $margin) / 50.0) * 50);

        return [
            'base_estimated_tdp' => $totalEstimated,
            'recommended_psu'    => $targetPower,
            'margin_factor'      => $margin,
        ];
    }

    public function validateConfiguration(array $productIds): array
    {
        $errors   = [];
        $warnings = [];

        $products = Product::with([
            'category', 'brand',
            'cpuSpec', 'gpuSpec', 'motherboardSpec', 'ramSpec',
            'storageSpec', 'caseSpec', 'coolingSpec', 'psuSpec',
        ])->whereIn('id', $productIds)->get();

        $cpu         = $products->first(fn ($p) => $p->category?->slug === 'cpu');
        $gpu         = $products->first(fn ($p) => $p->category?->slug === 'gpu');
        $motherboard = $products->first(fn ($p) => $p->category?->slug === 'motherboard');
        $ram         = $products->first(fn ($p) => $p->category?->slug === 'ram');
        $pcCase      = $products->first(fn ($p) => $p->category?->slug === 'pc_case');
        $cooling     = $products->first(fn ($p) => $p->category?->slug === 'cooling');
        $psu         = $products->first(fn ($p) => $p->category?->slug === 'psu');

        if ($cpu && $motherboard) {
            $cpuSocket = (string) ($cpu->cpuSpec?->socket ?? '');
            $mbSocket  = (string) ($motherboard->motherboardSpec?->socket ?? '');
            if ($cpuSocket !== '' && $mbSocket !== '' && $cpuSocket !== $mbSocket) {
                $errors[] = "Сокеты не совпадают: {$cpu->name} ({$cpuSocket}) vs {$motherboard->name} ({$mbSocket}).";
            }
            $vrm = $this->checkVrmCompatibility($cpu, $motherboard);
            if ($vrm !== null) $errors[] = $vrm;
        }

        if ($motherboard && $ram) {
            $mbRam   = strtoupper((string) ($motherboard->motherboardSpec?->ram_type ?? ''));
            $ramType = strtoupper((string) ($ram->ramSpec?->type ?? ''));
            if ($mbRam !== '' && $ramType !== '' && $mbRam !== $ramType) {
                $errors[] = "ОЗУ: плата требует {$mbRam}, а память — {$ramType}.";
            }
        }

        if ($pcCase && $motherboard) {
            $caseForm = strtolower($pcCase->caseSpec?->form_factor ?? '');
            $mbForm   = strtoupper($motherboard->motherboardSpec?->form_factor ?? '');
            if ($caseForm === 'mini-itx' && $mbForm !== 'ITX') {
                $errors[] = "Корпус mini-itx вмещает только ITX. Плата {$mbForm}.";
            }
        }

        if ($cpu && ! $gpu && ! $this->cpuHasIgpu($cpu)) {
            $errors[] = "Нет видеовыхода: у {$cpu->name} нет встроенной графики, и дискретная видеокарта не выбрана.";
        }

        if ($cpu && $cooling) {
            $cpuTdp    = (int) ($cpu->cpuSpec?->tdp ?? 65);
            $coolerTdp = (int) ($cooling->coolingSpec?->tdp ?? 0);
            if ($coolerTdp < $cpuTdp) {
                $errors[] = "Кулер {$coolerTdp}W слабее TDP процессора {$cpuTdp}W.";
            }
        }

        $cpuTdp  = (int) ($cpu?->cpuSpec?->tdp ?? 65);
        $gpuTdp  = (int) ($gpu?->gpuSpec?->tdp ?? 0);
        $psuCalc = $this->calculateRecommendedPsuWattage($cpuTdp, $gpuTdp);

        if ($psu) {
            $psuPower = (int) ($psu->psuSpec?->power ?? 0);
            if ($psuPower < $psuCalc['base_estimated_tdp']) {
                $errors[] = "БП {$psuPower}W слабее расчётной нагрузки {$psuCalc['base_estimated_tdp']}W.";
            }
        }

        $totalPrice = $products->sum('price');

        return [
            'is_valid'                => empty($errors),
            'is_compatible'           => empty($errors),
            'errors'                  => $errors,
            'warnings'                => $warnings,
            'total_price'             => (float) $totalPrice,
            'estimated_wattage'       => $psuCalc['base_estimated_tdp'],
            'base_estimated_tdp'      => $psuCalc['base_estimated_tdp'],
            'recommended_psu_wattage' => $psuCalc['recommended_psu'],
            'components_count'        => $products->count(),
            'products'                => $products,
        ];
    }

    protected function checkVrmCompatibility(Product $cpu, Product $motherboard): ?string
    {
        $cpuTdp  = (int) ($cpu->cpuSpec?->tdp ?? 0);
        $chipset = strtoupper((string) ($motherboard->motherboardSpec?->chipset ?? ''));

        $isHotCpu = $cpuTdp >= 100 || preg_match('/(X3D|KF|KS|K|X)\b/', (string) $cpu->name) === 1;
        if (! $isHotCpu || $chipset === '') return null;

        $isBudget = in_array($chipset, self::BUDGET_CHIPSETS_AMD, true)
            || in_array($chipset, self::BUDGET_CHIPSETS_INTEL, true);

        return $isBudget
            ? "VRM защита: {$cpu->name} ({$cpuTdp}W) требует чипсет B-серии или старше, а не {$chipset}."
            : null;
    }

    private function cpuHasIgpu(Product $cpu): bool
    {
        $raw = $cpu->cpuSpec?->has_igpu;
        return (int) $raw === 1 || $raw === true;
    }

    public function autoBuild(array $params = []): array
    {
        $target   = $this->resolveTargetBudget($params);
        $isOffice = ($params['use_case'] ?? '') === 'office';

        $cpu         = $this->selectCpu($target, $params, $isOffice);
        $gpu         = $isOffice ? null : $this->selectGpu($target, $params);
        $motherboard = $this->selectMotherboard($cpu, $target, $params);
        $ram         = $this->selectRam($motherboard, $target, $params);
        $storage     = $this->selectStorage($target, $params);
        $cooling     = $this->selectCooling($cpu, $target, $params, $isOffice);
        $case        = $this->selectCase($target, $params, $isOffice);
        $psu         = $this->selectPsu($cpu, $gpu, $target, $params);

        $selected = collect([$cpu, $gpu, $motherboard, $ram, $storage, $cooling, $case, $psu])
            ->filter()
            ->values();

        if ($target < self::INFINITY_TARGET) {
            $selected = $this->fillBudget($selected, $target, $isOffice, $params);
        }

        $selected   = $this->ensureCpuHasOutput($selected, $target);
        $byCategory = $selected->keyBy(fn ($p) => $p->category->slug);
        $validation = $this->validateConfiguration($selected->pluck('id')->toArray());

        $storageItem = $byCategory->get('storage');

        return [
            'config_applied' => $params,
            'config'         => $params,
            'components'     => [
                'cpu'         => $byCategory->get('cpu'),
                'gpu'         => $byCategory->get('gpu'),
                'motherboard' => $byCategory->get('motherboard'),
                'ram'         => $byCategory->get('ram'),
                'storage'     => $storageItem ? collect([$storageItem]) : collect(),
                'case'        => $byCategory->get('pc_case'),
                'cooling'     => $byCategory->get('cooling'),
                'psu'         => $byCategory->get('psu'),
            ],
            'products'    => $selected,
            'total_price' => (float) $selected->sum('price'),
            'budget'      => [
                'target'      => $target,
                'tier'        => 'custom',
                'over_budget' => false,
            ],
            'validation'  => $validation,
        ];
    }

    private function ensureCpuHasOutput(Collection $selected, int $target): Collection
    {
        $byCategory = $selected->keyBy(fn ($p) => $p->category->slug);
        $cpu        = $byCategory->get('cpu');
        $gpu        = $byCategory->get('gpu');

        if (! $cpu || $gpu || $this->cpuHasIgpu($cpu)) {
            return $selected;
        }

        $replacement = $this->findCpuWithIgpu($cpu);
        if ($replacement) {
            $byCategory->put('cpu', $replacement);
        }

        return $byCategory->values();
    }

    private function findCpuWithIgpu(Product $originalCpu): ?Product
    {
        $socket = $originalCpu->cpuSpec?->socket;

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'cpu'))
            ->whereHas('cpuSpec', function ($q) use ($socket) {
                $q->where('performance', '>=', self::MIN_CPU_PERF);
                if ($socket) $q->where('socket', $socket);
            })
            ->with(['category', 'cpuSpec', 'brand']);

        $filter = fn ($p) => $this->cpuHasIgpu($p);

        $candidates = (clone $query)
            ->whereBetween('price', [
                (int) ($originalCpu->price * 0.9),
                (int) ($originalCpu->price * 1.05),
            ])
            ->get()
            ->filter($filter);

        if ($candidates->isNotEmpty()) {
            return $candidates->sortByDesc('price')->first();
        }

        $candidates = (clone $query)->where('price', '<=', $originalCpu->price)->get()->filter($filter);
        if ($candidates->isNotEmpty()) {
            return $candidates->sortByDesc('price')->first();
        }

        return Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'cpu'))
            ->whereHas('cpuSpec', fn ($q) => $q->where('performance', '>=', self::MIN_CPU_PERF))
            ->with(['category', 'cpuSpec', 'brand'])
            ->get()
            ->filter($filter)
            ->sortByDesc('price')
            ->first();
    }

    private function resolveTargetBudget(array $params): int
    {
        if (isset($params['target_budget']) && is_numeric($params['target_budget'])) {
            $v = (int) $params['target_budget'];
            if ($v >= self::MAX_TARGET) return self::INFINITY_TARGET;
            return max($v, self::MIN_TARGET);
        }
        return self::DEFAULT_TARGET;
    }

    // ============================================================
    // Первичный подбор — с учётом ВСЕХ флагов профиля
    // ============================================================

    private function selectCpu(int $target, array $params, bool $isOffice): ?Product
    {
        $weights  = $isOffice ? self::OFFICE_WEIGHTS : self::INITIAL_WEIGHTS;
        $maxPrice = (int) round($target * ($weights['cpu'] ?? 0.20));
        $vendor   = $params['cpu_vendor'] ?? 'auto';

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'cpu'))
            ->whereHas('cpuSpec', fn ($q) => $q->where('performance', '>=', self::MIN_CPU_PERF))
            ->with(['category', 'cpuSpec', 'brand']);

        if ($isOffice) {
            $query->whereHas('cpuSpec', fn ($q) => $q->where('has_igpu', true));
        }

        if ($vendor !== 'auto' && $vendor !== '') {
            $pool = (clone $query)
                ->whereHas('brand', fn ($b) => $b->where('slug', $vendor))
                ->where('price', '<=', $maxPrice)
                ->get();
            $best = $this->pickCpu($pool, $isOffice);
            if ($best) return $best;

            $best = (clone $query)
                ->whereHas('brand', fn ($b) => $b->where('slug', $vendor))
                ->orderBy('price')
                ->get()
                ->filter(fn ($p) => ! $isOffice || $this->cpuHasIgpu($p))
                ->first();
            if ($best) return $best;
        }

        $pool = (clone $query)->where('price', '<=', $maxPrice)->get();
        $best = $this->pickCpu($pool, $isOffice);
        if ($best) return $best;

        return $query->orderBy('price')->first();
    }

    private function pickCpu(Collection $pool, bool $requireIgpu): ?Product
    {
        if ($pool->isEmpty()) return null;
        $filtered = $requireIgpu ? $pool->filter(fn ($p) => $this->cpuHasIgpu($p)) : $pool;
        if ($filtered->isEmpty()) return null;
        return $filtered->sortByDesc('price')->first();
    }

    private function selectGpu(int $target, array $params): ?Product
    {
        $maxPrice = (int) round($target * self::INITIAL_WEIGHTS['gpu']);
        $vendor   = $params['gpu_vendor'] ?? 'auto';

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'gpu'))
            ->whereHas('gpuSpec', fn ($q) => $q->where('performance', '>=', self::MIN_GPU_PERF))
            ->with(['category', 'gpuSpec', 'brand']);

        if ($vendor !== 'auto' && $vendor !== '') {
            $best = (clone $query)
                ->whereHas('brand', fn ($b) => $b->where('slug', $vendor))
                ->where('price', '<=', $maxPrice)
                ->orderByDesc('price')
                ->first();
            if ($best) return $best;

            $best = (clone $query)
                ->whereHas('brand', fn ($b) => $b->where('slug', $vendor))
                ->orderBy('price')
                ->first();
            if ($best) return $best;
        }

        $best = (clone $query)->where('price', '<=', $maxPrice)->orderByDesc('price')->first();
        if ($best) return $best;

        return $query->orderBy('price')->first();
    }

    private function selectMotherboard(?Product $cpu, int $target, array $params): ?Product
    {
        if (! $cpu) return null;

        $socket   = $cpu->cpuSpec?->socket ?? 'AM5';
        $maxPrice = (int) round($target * self::INITIAL_WEIGHTS['motherboard']);
        $cpuTdp   = (int) ($cpu->cpuSpec?->tdp ?? 0);
        $isHot    = $cpuTdp >= 100 || preg_match('/(X3D|KF|KS|K|X)\b/', (string) $cpu->name) === 1;

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'motherboard'))
            ->whereHas('motherboardSpec', function ($q) use ($socket, $isHot) {
                $q->where('socket', $socket);
                if ($isHot) {
                    $q->whereNotIn('chipset', ['A320', 'A520', 'A620', 'H610']);
                }
            })
            ->with(['category', 'motherboardSpec', 'brand']);

        $best = (clone $query)->where('price', '<=', $maxPrice)->orderByDesc('price')->first();
        if ($best) return $best;

        return $query->orderBy('price')->first();
    }

    private function selectRam(?Product $motherboard, int $target, array $params): ?Product
    {
        if (! $motherboard) return null;

        $ramType  = $motherboard->motherboardSpec?->ram_type ?? 'DDR5';
        $capacity = (int) ($params['ram_size'] ?? 16);
        $maxPrice = (int) round($target * self::INITIAL_WEIGHTS['ram']);

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'ram'))
            ->whereHas('ramSpec', fn ($q) => $q->where('type', $ramType)->where('capacity', '>=', $capacity))
            ->with(['category', 'ramSpec', 'brand']);

        $best = (clone $query)->where('price', '<=', $maxPrice)->orderByDesc('price')->first();
        if ($best) return $best;

        return $query->orderBy('price')->first();
    }

    private function selectStorage(int $target, array $params): ?Product
    {
        $maxPrice = (int) round($target * self::INITIAL_WEIGHTS['storage']);

        $type = match ($params['storage_type'] ?? 'nvme') {
            'sata'  => 'SATA SSD',
            'hdd'   => 'HDD',
            'combo' => 'NVMe SSD',
            default => 'NVMe SSD',
        };

        $capacity = match ((string) ($params['storage_size'] ?? '1')) {
            '512' => 500, '1' => 1024, '2' => 2048, '4' => 4096, default => 1024,
        };

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'storage'))
            ->whereHas('storageSpec', fn ($q) => $q->where('type', $type)->where('capacity', '>=', $capacity))
            ->with(['category', 'storageSpec', 'brand']);

        $best = (clone $query)->where('price', '<=', $maxPrice)->orderByDesc('price')->first();
        if ($best) return $best;

        return $query->orderBy('price')->first();
    }

    private function selectCooling(?Product $cpu, int $target, array $params, bool $isOffice): ?Product
    {
        if (! $cpu) return null;

        $neededTdp = (int) ceil(($cpu->cpuSpec?->tdp ?? 65) * 1.2);
        $maxPrice  = (int) round($target * self::INITIAL_WEIGHTS['cooling']);
        $type      = $isOffice ? 'air' : (($params['cooling'] ?? 'air') === 'water' ? 'water' : 'air');

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'cooling'))
            ->whereHas('coolingSpec', fn ($q) => $q->where('tdp', '>=', $neededTdp)->where('type', $type))
            ->with(['category', 'coolingSpec', 'brand']);

        $best = (clone $query)->where('price', '<=', $maxPrice)->orderByDesc('price')->first();
        if ($best) return $best;

        return $query->orderBy('price')->first();
    }

    private function selectCase(int $target, array $params, bool $isOffice): ?Product
    {
        $maxPrice = (int) round($target * self::INITIAL_WEIGHTS['case']);
        $form     = $isOffice ? 'mid-tower' : ($params['case'] ?? 'mid-tower');

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'pc_case'))
            ->whereHas('caseSpec', fn ($q) => $q->where('form_factor', $form))
            ->with(['category', 'caseSpec', 'brand']);

        $best = (clone $query)->where('price', '<=', $maxPrice)->orderByDesc('price')->first();
        if ($best) return $best;

        return $query->orderBy('price')->first();
    }

    private function selectPsu(?Product $cpu, ?Product $gpu, int $target, array $params): ?Product
    {
        if (! $cpu) return null;

        $cpuTdp      = (int) ($cpu->cpuSpec?->tdp ?? 65);
        $gpuTdp      = (int) ($gpu?->gpuSpec?->tdp ?? 0);
        $need        = $this->calculateRecommendedPsuWattage($cpuTdp, $gpuTdp);
        $recommended = $need['recommended_psu'];
        $maxPrice    = (int) round($target * self::INITIAL_WEIGHTS['psu']);

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', 'psu'))
            ->whereHas('psuSpec', fn ($q) => $q->where('power', '>=', $need['base_estimated_tdp']))
            ->with(['category', 'psuSpec', 'brand']);

        $pool = (clone $query)->where('price', '<=', $maxPrice)->get();
        if ($pool->isNotEmpty()) {
            return $pool->sort(function (Product $a, Product $b) use ($recommended): int {
                $pa = (int) ($a->psuSpec?->power ?? 0);
                $pb = (int) ($b->psuSpec?->power ?? 0);
                $da = abs($pa - $recommended);
                $db = abs($pb - $recommended);
                if ($da !== $db) return $da <=> $db;
                return (float) $b->price <=> (float) $a->price;
            })->first();
        }

        return $query->orderBy('price')->first();
    }

    // ============================================================
    // Жадный добор — учитывает ВСЕ флаги профиля
    // ============================================================

    private function fillBudget(Collection $selected, int $target, bool $isOffice, array $config): Collection
    {
        $items = $selected->keyBy(fn ($p) => $p->category->slug);
        $order = $isOffice ? self::UPGRADE_ORDER_OFFICE : self::UPGRADE_ORDER_DEFAULT;

        for ($iter = 0; $iter < self::FILL_ITERATIONS; $iter++) {
            $spent     = (int) $items->sum(fn ($p) => (float) $p->price);
            $remainder = $target - $spent;

            if ($remainder < self::FILL_MIN_REMAINDER) break;

            $upgraded = false;
            foreach ($order as $cat) {
                $current = $items->get($cat);
                if (! $current) continue;

                $next = $this->findNextUpgrade($cat, $current, $remainder, $items, $config);
                if ($next) {
                    $items->put($cat, $next);
                    $upgraded = true;
                    break;
                }
            }

            if (! $upgraded) break;
        }

        return $items->values();
    }

    /**
     * Ищет более дорогой вариант в категории, который:
     *  1) влезает в оставшийся бюджет,
     *  2) НЕ нарушает ни один флаг профиля (вендор, объём, тип, форм-фактор...),
     *  3) совместим с остальной сборкой.
     */
    private function findNextUpgrade(
        string $category,
        Product $current,
        int $budgetRoom,
        Collection $items,
        array $config
    ): ?Product {
        $upperBound = $current->price + $budgetRoom;

        $query = Product::query()
            ->whereHas('category', fn ($q) => $q->where('slug', $category))
            ->where('price', '>', $current->price)
            ->where('price', '<=', $upperBound)
            ->with([
                'category', 'brand',
                'cpuSpec', 'gpuSpec', 'motherboardSpec', 'ramSpec',
                'storageSpec', 'caseSpec', 'coolingSpec', 'psuSpec',
            ]);

        // ---------- CPU ----------
        if ($category === 'cpu') {
            $vendor = $config['cpu_vendor'] ?? 'auto';
            if ($vendor !== 'auto' && $vendor !== '') {
                $query->whereHas('brand', fn ($q) => $q->where('slug', $vendor));
            }
            if (($config['use_case'] ?? '') === 'office') {
                $query->whereHas('cpuSpec', fn ($q) => $q->where('has_igpu', true));
            }
        }

        // ---------- GPU ----------
        if ($category === 'gpu') {
            $vendor = $config['gpu_vendor'] ?? 'auto';
            if ($vendor !== 'auto' && $vendor !== '') {
                $query->whereHas('brand', fn ($q) => $q->where('slug', $vendor));
            }
        }

        // ---------- RAM ----------
        if ($category === 'ram') {
            $capacity = (int) ($config['ram_size'] ?? 16);
            if ($capacity > 0) {
                $query->whereHas('ramSpec', fn ($q) => $q->where('capacity', '>=', $capacity));
            }
        }

        // ---------- Storage ----------
        if ($category === 'storage') {
            $type = match ($config['storage_type'] ?? 'nvme') {
                'sata'  => 'SATA SSD',
                'hdd'   => 'HDD',
                'combo' => 'NVMe SSD',
                default => 'NVMe SSD',
            };
            $capacity = match ((string) ($config['storage_size'] ?? '1')) {
                '512' => 500, '1' => 1024, '2' => 2048, '4' => 4096, default => 1024,
            };
            $query->whereHas('storageSpec', function ($q) use ($type, $capacity) {
                $q->where('type', $type)->where('capacity', '>=', $capacity);
            });
        }

        // ---------- Case ----------
        if ($category === 'pc_case') {
            $form = $config['case'] ?? 'mid-tower';
            if ($form && $form !== 'auto') {
                $query->whereHas('caseSpec', fn ($q) => $q->where('form_factor', $form));
            }
        }

        // ---------- Cooling ----------
        if ($category === 'cooling') {
            $type = (($config['use_case'] ?? '') === 'office')
                ? 'air'
                : ((($config['cooling'] ?? 'air') === 'water') ? 'water' : 'air');
            $query->whereHas('coolingSpec', fn ($q) => $q->where('type', $type));
        }

        $candidates = $query->orderByDesc('price')->get();

        foreach ($candidates as $candidate) {
            if ($this->isCompatibleReplacement($category, $candidate, $items)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isCompatibleReplacement(string $category, Product $candidate, Collection $items): bool
    {
        $cpu = $items->get('cpu');
        $mb  = $items->get('motherboard');
        $ram = $items->get('ram');
        $gpu = $items->get('gpu');

        return match ($category) {
            'cpu'         => $this->fitsCpuReplacement($candidate, $mb, $gpu),
            'gpu'         => true,
            'motherboard' => $this->fitsMbReplacement($candidate, $cpu, $ram),
            'ram'         => $this->fitsRamReplacement($candidate, $mb),
            'storage'     => true,
            'cooling'     => $this->fitsCoolingReplacement($candidate, $cpu),
            'pc_case'     => $this->fitsCaseReplacement($candidate, $mb),
            'psu'         => $this->fitsPsuReplacement($candidate, $cpu, $gpu),
            default       => true,
        };
    }

    private function fitsCpuReplacement(Product $candidate, ?Product $mb, ?Product $gpu): bool
    {
        if ($mb) {
            if ($candidate->cpuSpec?->socket !== $mb->motherboardSpec?->socket) return false;
            if ($this->checkVrmCompatibility($candidate, $mb) !== null) return false;
        }
        if (! $gpu && ! $this->cpuHasIgpu($candidate)) return false;
        return true;
    }

    private function fitsMbReplacement(Product $candidate, ?Product $cpu, ?Product $ram): bool
    {
        if ($cpu) {
            if ($candidate->motherboardSpec?->socket !== $cpu->cpuSpec?->socket) return false;
            if ($this->checkVrmCompatibility($cpu, $candidate) !== null) return false;
        }
        if ($ram && $candidate->motherboardSpec?->ram_type !== $ram->ramSpec?->type) return false;
        return true;
    }

    private function fitsRamReplacement(Product $candidate, ?Product $mb): bool
    {
        if (! $mb) return true;
        return $candidate->ramSpec?->type === $mb->motherboardSpec?->ram_type;
    }

    private function fitsCoolingReplacement(Product $candidate, ?Product $cpu): bool
    {
        if (! $cpu) return true;
        $needed = (int) ($cpu->cpuSpec?->tdp ?? 65);
        return (int) ($candidate->coolingSpec?->tdp ?? 0) >= $needed;
    }

    private function fitsCaseReplacement(Product $candidate, ?Product $mb): bool
    {
        if (! $mb) return true;
        $caseForm = strtolower($candidate->caseSpec?->form_factor ?? '');
        $mbForm   = strtoupper($mb->motherboardSpec?->form_factor ?? '');
        return ! ($caseForm === 'mini-itx' && $mbForm !== 'ITX');
    }

    private function fitsPsuReplacement(Product $candidate, ?Product $cpu, ?Product $gpu): bool
    {
        if (! $cpu) return true;
        $cpuTdp = (int) ($cpu->cpuSpec?->tdp ?? 65);
        $gpuTdp = (int) ($gpu?->gpuSpec?->tdp ?? 0);
        $need   = $this->calculateRecommendedPsuWattage($cpuTdp, $gpuTdp)['base_estimated_tdp'];
        return (int) ($candidate->psuSpec?->power ?? 0) >= $need;
    }
}