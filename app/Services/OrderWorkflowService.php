<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderWorkflowService
{
    /**
     * @param array{
     *     customer_name: string,
     *     customer_whatsapp: string,
     *     quantity: int,
     *     pickup_date: string,
     *     pickup_time: string,
     *     note?: string|null
     * } $data
     */
    public function placeOrder(User $customer, Product $product, array $data): Order
    {
        return DB::transaction(function () use ($customer, $product, $data): Order {
            $product = Product::query()->lockForUpdate()->findOrFail($product->getKey());

            if (
                ! $product->is_active
                || ! $product->category()->where('is_active', true)->exists()
                || ($product->available_date !== null && $product->available_date->isPast() && ! $product->available_date->isToday())
            ) {
                throw ValidationException::withMessages([
                    'product' => 'Menu ini sedang tidak tersedia untuk dipesan.',
                ]);
            }

            if ($product->available_date !== null && $product->available_date->toDateString() !== $data['pickup_date']) {
                throw ValidationException::withMessages([
                    'pickup_date' => 'Tanggal pengambilan harus sesuai tanggal ketersediaan menu.',
                ]);
            }

            $reservedQuantity = (int) OrderItem::query()
                ->where('product_id', $product->getKey())
                ->whereHas('order', fn ($query) => $query
                    ->whereDate('pickup_date', $data['pickup_date'])
                    ->whereIn('status', [
                        OrderStatus::Pending->value,
                        OrderStatus::Confirmed->value,
                    ]))
                ->sum('quantity');

            $unitPriceCents = $this->amountInCents((string) $product->price);
            $maximumOrderTotalCents = 999_999_999_999;
            $maximumQuantityByOrderTotal = $unitPriceCents > 0
                ? intdiv($maximumOrderTotalCents, $unitPriceCents)
                : 0;
            $availableQuantity = min(
                max(0, $product->stock_limit - $reservedQuantity),
                $maximumQuantityByOrderTotal,
            );
            $quantity = (int) $data['quantity'];

            if ($availableQuantity === 0 || $quantity > $availableQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah pesanan melebihi stok yang masih tersedia.',
                ]);
            }

            $subtotal = $this->decimalFromCents($unitPriceCents * $quantity);
            $order = $customer->orders()->create([
                'transaction_code' => 'DH-'.Str::upper((string) Str::uuid()),
                'customer_name' => $data['customer_name'],
                'customer_whatsapp' => $data['customer_whatsapp'],
                'pickup_date' => $data['pickup_date'],
                'pickup_time' => $data['pickup_time'],
                'total_price' => $subtotal,
                'status' => OrderStatus::Pending->value,
                'payment_status' => 'unpaid',
                'note' => $data['note'] ?? null,
            ]);

            $order->items()->create([
                'product_id' => $product->getKey(),
                'product_name' => $product->name,
                'unit_price' => $product->price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ]);

            $order->statusHistories()->create([
                'changed_by' => $customer->getKey(),
                'old_status' => null,
                'new_status' => OrderStatus::Pending->value,
            ]);

            return $order;
        });
    }

    public function changeStatus(Order $order, User $administrator, OrderStatus $newStatus, ?string $reason): bool
    {
        return DB::transaction(function () use ($order, $administrator, $newStatus, $reason): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->getKey());
            $currentStatus = OrderStatus::tryFrom($order->status);

            if ($currentStatus === $newStatus) {
                return false;
            }

            if ($currentStatus !== OrderStatus::Pending) {
                throw ValidationException::withMessages([
                    'status' => 'Status hanya dapat diubah saat pesanan masih Pending.',
                ]);
            }

            if ($newStatus === OrderStatus::Pending) {
                return false;
            }

            if ($newStatus === OrderStatus::Rejected && $order->payment_status !== 'unpaid') {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan yang sudah dibayar tidak dapat ditolak sebelum pengembalian dana diproses.',
                ]);
            }

            $order->update(['status' => $newStatus->value]);
            $order->statusHistories()->create([
                'changed_by' => $administrator->getKey(),
                'old_status' => $currentStatus->value,
                'new_status' => $newStatus->value,
                'reason' => $newStatus === OrderStatus::Rejected ? $reason : null,
            ]);

            return true;
        });
    }

    private function amountInCents(string $amount): int
    {
        [$units, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $units * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function decimalFromCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
