<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\CompatibilityService;
use Illuminate\Console\Command;

class TestBuilderCommand extends Command
{
    protected $signature = 'pc:test-builder';
    protected $description = 'Тестирование алгоритма автоподбора и валидатора совместимости комплектующих ПК';

    public function handle(CompatibilityService $builder): int
    {
        $this->info("=== ЗАПУСК ТЕСТОВОГО СЬЮТА КОНФИГУРАТОРА ПК ===");

        // ТЕСТ 1: Игровая сбалансированная сборка
        $this->warn("\n[Тест 1] Автоподбор: Игровая сборка (Gaming / Mid-Low / AMD + NVIDIA)");
        $build1 = $builder->autoBuild([
            'budget'     => 'mid_low',
            'use_case'   => 'gaming',
            'cpu_vendor' => 'amd',
            'gpu_vendor' => 'nvidia',
        ]);
        $this->displayBuildTable($build1);

        // ТЕСТ 2: Офисный ПК (без дискретной видеокарты)
        $this->warn("\n[Тест 2] Автоподбор: Офисный ПК (Office / Low / Intel iGPU)");
        $build2 = $builder->autoBuild([
            'budget'     => 'low',
            'use_case'   => 'office',
            'cpu_vendor' => 'intel',
        ]);
        $this->displayBuildTable($build2);

        // ТЕСТ 3: Рабочая станция с комбо-накопителем и СЖО
        $this->warn("\n[Тест 3] Автоподбор: Рабочая станция (Work / High / Combo Storage NVMe+HDD)");
        $build3 = $builder->autoBuild([
            'budget'       => 'high',
            'use_case'     => 'work',
            'storage_type' => 'combo',
            'cooling'      => 'water',
        ]);
        $this->displayBuildTable($build3);

        // ТЕСТ 4: Валидация несовместимой конфигурации
        $this->warn("\n[Тест 4] Проверка валидатора: Заведомо несовместимый набор железа");
        $this->runIncompatibleValidationTest($builder);

        $this->info("\n Все сценарии тестирования успешно пройдены!");
        return Command::SUCCESS;
    }

    protected function displayBuildTable(array $result): void
    {
        $components = $result['components'];
        $rows = [];

        $addRow = function (string $type, ?Product $product, string $specs) use (&$rows) {
            $rows[] = [
                $type,
                $product ? $product->name : '— [Отсутствует]',
                $specs,
                $product ? number_format($product->price, 0, '.', ' ') . ' ₽' : '0 ₽',
            ];
        };

        $addRow('CPU', $components['cpu'], "Сокет {$components['cpu']->cpuSpec->socket}, {$components['cpu']->cpuSpec->cores} ядер, TDP {$components['cpu']->cpuSpec->tdp}W");

        if ($components['gpu']) {
            $addRow('GPU', $components['gpu'], "{$components['gpu']->gpuSpec->vram}GB VRAM, TDP {$components['gpu']->gpuSpec->tdp}W");
        } else {
            $rows[] = ['GPU', 'Встроенная графика (iGPU)', 'Интегрированное в процессор видеоядро', '0 ₽'];
        }

        $addRow('Материнская плата', $components['motherboard'], "Сокет {$components['motherboard']->motherboardSpec->socket}, {$components['motherboard']->motherboardSpec->form_factor}, {$components['motherboard']->motherboardSpec->ram_type}");
        $addRow('ОЗУ', $components['ram'], "{$components['ram']->ramSpec->capacity}GB {$components['ram']->ramSpec->type} {$components['ram']->ramSpec->speed}MHz");

        foreach ($components['storage'] as $str) {
            $addRow('Накопитель', $str, "{$str->storageSpec->type} {$str->storageSpec->capacity}GB");
        }

        $addRow('Корпус', $components['case'], "Форм-фактор {$components['case']->caseSpec->form_factor}, цвет {$components['case']->caseSpec->color}");
        $addRow('Охлаждение', $components['cooling'], "Тип {$components['cooling']->coolingSpec->type}, TDP {$components['cooling']->coolingSpec->tdp}W");
        $addRow('Блок питания', $components['psu'], "Мощность {$components['psu']->psuSpec->power}W");

        $this->table(['Категория', 'Модель', 'Характеристики', 'Цена'], $rows);

        $this->line("Итоговая цена: <info>" . number_format($result['total_price'], 0, '.', ' ') . " ₽</info>");
        $this->line("Базовый TDP системы: <comment>{$result['validation']['base_estimated_tdp']}W</comment> | Рекомендуемый БП: <comment>{$result['validation']['recommended_psu_wattage']}W</comment>");

        if ($result['validation']['is_compatible']) {
            $this->info(" Статус совместимости: ПОЛНОСТЬЮ СОВМЕСТИМО");
        } else {
            $this->error(" Ошибки совместимости: " . implode('; ', $result['validation']['errors']));
        }

        if (!empty($result['validation']['warnings'])) {
            $this->comment(" Предупреждения: " . implode('; ', $result['validation']['warnings']));
        }
    }

    protected function runIncompatibleValidationTest(CompatibilityService $builder): void
    {
        // 1. Процессор AM4
        $cpu = Product::whereHas('category', fn($q) => $q->where('slug', 'cpu'))
            ->whereHas('cpuSpec', fn($q) => $q->where('socket', 'AM4'))
            ->first();

        // 2. Несовместимая плата LGA1700 формата ATX
        $mb = Product::whereHas('category', fn($q) => $q->where('slug', 'motherboard'))
            ->whereHas('motherboardSpec', fn($q) => $q->where('socket', 'LGA1700')->where('form_factor', 'ATX'))
            ->first();

        // 3. Mini-ITX корпус (куда физически не влезет ATX плата)
        $case = Product::whereHas('category', fn($q) => $q->where('slug', 'pc_case'))
            ->whereHas('caseSpec', fn($q) => $q->where('form_factor', 'mini-itx'))
            ->first();

        // 4. Слабый кулер на 130W для мощного процессора
        $cooler = Product::whereHas('category', fn($q) => $q->where('slug', 'cooling'))
            ->whereHas('coolingSpec', fn($q) => $q->where('tdp', '<=', 130))
            ->first();

        // 5. Слабый блок питания на 500W
        $psu = Product::whereHas('category', fn($q) => $q->where('slug', 'psu'))
            ->whereHas('psuSpec', fn($q) => $q->where('power', 500))
            ->first();

        // 6. Прожорливая видеокарта на 300W+
        $gpu = Product::whereHas('category', fn($q) => $q->where('slug', 'gpu'))
            ->whereHas('gpuSpec', fn($q) => $q->where('tdp', '>=', 320))
            ->first();

        $ids = array_filter([$cpu?->id, $mb?->id, $case?->id, $cooler?->id, $psu?->id, $gpu?->id]);

        $report = $builder->validateConfiguration($ids);

        $this->line("Проверяемые компоненты: {$cpu?->name} + {$mb?->name} + {$case?->name} + {$gpu?->name} + {$psu?->name}");
        $this->line("Совместимо: " . ($report['is_compatible'] ? '<info>ДА</info>' : '<error>НЕТ</error>'));

        $this->line("\nОбнаруженные критические ошибки (Errors):");
        foreach ($report['errors'] as $error) {
            $this->error(" - {$error}");
        }

        if (!empty($report['warnings'])) {
            $this->line("\nПредупреждения (Warnings):");
            foreach ($report['warnings'] as $warning) {
                $this->comment(" - {$warning}");
            }
        }
    }
}