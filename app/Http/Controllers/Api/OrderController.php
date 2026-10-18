<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
    ) {
    }

    /**
     * POST /api/orders
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_ids'   => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = $this->orderService->createOrder(
            $request->user(),
            $validated['product_ids'],
            $validated['customer_note'] ?? null,
        );

        return response()->json([
            'order' => $this->serializeOrder($order),
        ], 201);
    }

    /**
     * GET /api/orders — история заказов текущего пользователя
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items', 'statusHistories'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'orders' => $orders->map(fn (Order $o) => $this->serializeOrder($o))->all(),
        ]);
    }

    /**
     * GET /api/manage/orders — реестр для персонала
     */
    public function manageIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status'   => ['sometimes', 'string', Rule::in(OrderService::ALLOWED_STATUSES)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Order::query()
            ->with(['items', 'statusHistories', 'user'])
            ->orderByDesc('created_at');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $orders = $query->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'data' => $orders->getCollection()
                ->map(fn (Order $o) => $this->serializeOrder($o, withUser: true))
                ->all(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
                'last_page'    => $orders->lastPage(),
            ],
        ]);
    }

    /**
     * PATCH /api/manage/orders/{id}/status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status'  => ['required', 'string', Rule::in(OrderService::ALLOWED_STATUSES)],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var Order $order */
        $order = Order::with(['items', 'statusHistories'])->findOrFail($id);

        $order->updateStatus(
            $validated['status'],
            $request->user()->id,
            $validated['comment'] ?? null,
        );

        return response()->json([
            'order' => $this->serializeOrder($order->fresh(['items', 'statusHistories'])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrder(Order $order, bool $withUser = false): array
    {
        $payload = [
            'id'               => $order->id,
            'order_number'     => $order->order_number,
            'status'           => $order->status,
            'total_price'      => (float) $order->total_price,
            'customer_note'    => $order->customer_note,
            'created_at'       => $order->created_at?->toIso8601String(),
            'updated_at'       => $order->updated_at?->toIso8601String(),
            'items'            => $order->relationLoaded('items')
                ? $order->items->map(fn ($i) => [
                    'id'         => $i->id,
                    'product_id' => $i->product_id,
                    'name'       => $i->name,
                    'price'      => (float) $i->price,
                    'quantity'   => $i->quantity,
                    'snapshot'   => $i->snapshot,
                ])->all()
                : [],
            'status_history'   => $order->relationLoaded('statusHistories')
                ? $order->statusHistories->map(fn ($h) => [
                    'status'     => $h->status,
                    'comment'    => $h->comment,
                    'user_id'    => $h->user_id,
                    'created_at' => $h->created_at?->toIso8601String(),
                ])->all()
                : [],
        ];

        if ($withUser && $order->relationLoaded('user') && $order->user) {
            $payload['user'] = [
                'id'    => $order->user->id,
                'name'  => $order->user->name,
                'email' => $order->user->email,
            ];
        }

        return $payload;
    }
}