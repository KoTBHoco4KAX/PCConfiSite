<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public const STATUS_NEW            = 'new';
    public const STATUS_CHECKING       = 'checking';
    public const STATUS_AWAITING_PARTS = 'awaiting_parts';
    public const STATUS_ASSEMBLING     = 'assembling';
    public const STATUS_READY          = 'ready';
    public const STATUS_CANCELLED      = 'cancelled';

    public const ALLOWED_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_CHECKING,
        self::STATUS_AWAITING_PARTS,
        self::STATUS_ASSEMBLING,
        self::STATUS_READY,
        self::STATUS_CANCELLED,
    ];

    public function __construct(
        private readonly CompatibilityServiceInterface $compatibilityService,
    ) {
    }

    public function createOrder(
        User $user,
        array $productIds,
        ?string $customerNote = null,
    ): Order {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        if ($productIds === []) {
            throw ValidationException::withMessages([
                'product_ids' => ['Список товаров пуст.'],
            ]);
        }

        $validation = $this->compatibilityService->validateConfiguration($productIds);
        $isValid    = (bool) ($validation['is_valid'] ?? $validation['is_compatible'] ?? false);

        if (! $isValid) {
            throw ValidationException::withMessages([
                'compatibility' => $validation['errors'] ?? ['Конфигурация несовместима.'],
            ]);
        }

        return DB::transaction(function () use ($user, $productIds, $customerNote): Order {
            /** @var \Illuminate\Database\Eloquent\Collection<int, Product> $products */
            $products = Product::with([
                'category', 'brand',
                'cpuSpec', 'gpuSpec', 'motherboardSpec', 'ramSpec',
                'storageSpec', 'caseSpec', 'coolingSpec', 'psuSpec',
            ])->whereIn('id', $productIds)->get();

            if ($products->count() !== count($productIds)) {
                throw ValidationException::withMessages([
                    'product_ids' => ['Некоторые товары не найдены.'],
                ]);
            }

            $totalPrice = (float) $products->sum('price');

            /** @var Order $order */
            $order = Order::create([
                'user_id'       => $user->id,
                'order_number'  => $this->generateOrderNumber(),
                'total_price'   => $totalPrice,
                'status'        => self::STATUS_NEW,
                'customer_note' => $customerNote,
            ]);

            foreach ($products as $product) {
                $order->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'price'        => (float) $product->price,
                    'quantity'     => 1,
                    'snapshot'     => $product->getSnapshotData(),
                ]);
            }

            $order->updateStatus(self::STATUS_NEW, $user->id, 'Заказ создан');

            return $order->load(['items', 'statusHistories']);
        });
    }

    private function generateOrderNumber(): string
    {
        $year = now()->format('Y');

        do {
            $suffix = strtoupper(Str::random(5));
            $number = sprintf('ORD-%s-%s', $year, $suffix);
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}