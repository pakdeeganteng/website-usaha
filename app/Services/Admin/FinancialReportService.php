<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OperationalExpense;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * @return array{
     *     start: CarbonImmutable,
     *     end: CarbonImmutable,
     *     summary: array<string, int|float|bool|null>,
     *     previous: array<string, int|float|bool|null>|null,
     *     trend: Collection<int, array<string, mixed>>,
     *     bestSellers: Collection<int, array<string, mixed>>
     * }
     */
    public function build(string $period, CarbonImmutable $reference): array
    {
        [$start, $end] = $this->periodBounds($period, $reference);
        $summary = $this->summarize($start, $end);
        $previousReference = match ($period) {
            'monthly' => $start->subMonthNoOverflow(),
            'yearly' => $start->subYear(),
            default => null,
        };
        $previous = $previousReference === null
            ? null
            : $this->summarize(
                $period === 'monthly' ? $previousReference->startOfMonth() : $previousReference->startOfYear(),
                $period === 'monthly' ? $previousReference->endOfMonth() : $previousReference->endOfYear(),
            );

        return [
            'start' => $start,
            'end' => $end,
            'summary' => $summary,
            'previous' => $previous,
            'trend' => $this->trend($period, $reference),
            'bestSellers' => $period === 'yearly' ? $this->bestSellers($start, $end) : collect(),
        ];
    }

    /**
     * @return array<string, int|float|bool|null>
     */
    public function summarize(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $orders = $this->paidOrders()->whereBetween('paid_at', [$start, $end]);
        $revenue = (float) (clone $orders)->sum('total_price');
        $transactionCount = (int) (clone $orders)->count();

        $soldItems = OrderItem::query()
            ->whereHas('order', fn (Builder $query) => $this->applyPaidOrderConstraints($query, $start, $end));
        $productsSold = (int) (clone $soldItems)->sum('quantity');
        $missingSoldCost = (clone $soldItems)->whereNull('unit_cost')->exists();
        $costOfGoodsSold = (float) (clone $soldItems)
            ->whereNotNull('unit_cost')
            ->selectRaw('COALESCE(SUM(unit_cost * quantity), 0) as aggregate')
            ->value('aggregate');

        $refunds = $this->completedPaidRefunds()
            ->whereBetween('refunded_at', [$start, $end]);
        $refundTotal = (float) (clone $refunds)->sum('amount');
        $returnedQuantity = (int) (clone $refunds)->sum('quantity_returned');
        $missingReturnedCost = (clone $refunds)
            ->where('quantity_returned', '>', 0)
            ->where(function (Builder $query): void {
                $query->whereNull('order_item_id')
                    ->orWhereHas('orderItem', fn (Builder $itemQuery) => $itemQuery->whereNull('unit_cost'));
            })
            ->exists();
        $returnedCost = (float) (clone $refunds)
            ->where('quantity_returned', '>', 0)
            ->whereHas('orderItem', fn (Builder $query) => $query->whereNotNull('unit_cost'))
            ->join('order_items', 'order_refunds.order_item_id', '=', 'order_items.id')
            ->selectRaw('COALESCE(SUM(order_items.unit_cost * order_refunds.quantity_returned), 0) as aggregate')
            ->value('aggregate');

        $expenses = (float) OperationalExpense::query()
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->sum('amount');
        $netRevenue = $revenue - $refundTotal;
        $costAvailable = ! $missingSoldCost && ! $missingReturnedCost;
        $netCostOfGoods = $costAvailable ? $costOfGoodsSold - $returnedCost : null;
        $grossProfit = $netCostOfGoods === null ? null : $netRevenue - $netCostOfGoods;
        $netProfit = $grossProfit === null ? null : $grossProfit - $expenses;

        return [
            'transaction_count' => $transactionCount,
            'products_sold' => $productsSold,
            'products_returned' => $returnedQuantity,
            'gross_revenue' => $revenue,
            'refund_total' => $refundTotal,
            'revenue' => $netRevenue,
            'cost_of_goods' => $netCostOfGoods,
            'expenses' => $expenses,
            'gross_profit' => $grossProfit,
            'loss' => $netProfit === null ? null : ($netProfit < 0 ? abs($netProfit) : 0.0),
            'net_profit' => $netProfit,
            'cost_available' => $costAvailable,
        ];
    }

    public function paidOrdersBetween(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $this->paidOrders()->whereBetween('paid_at', [$start, $end]);
    }

    public function expensesBetween(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return OperationalExpense::query()
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString());
    }

    public function refundsBetween(CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $this->completedPaidRefunds()
            ->whereBetween('refunded_at', [$start, $end]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function periodBounds(string $period, CarbonImmutable $reference): array
    {
        return match ($period) {
            'daily' => [$reference->startOfDay(), $reference->endOfDay()],
            'monthly' => [$reference->startOfMonth(), $reference->endOfMonth()],
            'yearly' => [$reference->startOfYear(), $reference->endOfYear()],
            default => throw new \InvalidArgumentException('Unsupported financial report period.'),
        };
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function trend(string $period, CarbonImmutable $reference): Collection
    {
        $bucketCount = match ($period) {
            'daily' => 14,
            'monthly' => 12,
            'yearly' => 5,
            default => 1,
        };
        $first = match ($period) {
            'daily' => $reference->startOfDay()->subDays($bucketCount - 1),
            'monthly' => $reference->startOfMonth()->subMonthsNoOverflow($bucketCount - 1),
            'yearly' => $reference->startOfYear()->subYears($bucketCount - 1),
            default => $reference->startOfDay(),
        };
        $last = match ($period) {
            'daily' => $reference->endOfDay(),
            'monthly' => $reference->endOfMonth(),
            'yearly' => $reference->endOfYear(),
            default => $reference->endOfDay(),
        };
        $sold = $this->salesByBucket($period, $first, $last);
        $costs = $this->costsByBucket($period, $first, $last);
        $refunds = $this->refundsByBucket($period, $first, $last);
        $expenses = $this->expensesByBucket($period, $first, $last);

        return collect(range(0, $bucketCount - 1))->map(function (int $offset) use (
            $period,
            $first,
            $sold,
            $costs,
            $refunds,
            $expenses,
        ): array {
            $bucketDate = match ($period) {
                'daily' => $first->addDays($offset),
                'monthly' => $first->addMonthsNoOverflow($offset),
                'yearly' => $first->addYears($offset),
                default => $first,
            };
            $key = $bucketDate->toDateString();
            $sales = $sold->get($key, ['revenue' => 0.0, 'transactions' => 0]);
            $cost = $costs->get($key, ['cost' => 0.0, 'missing' => false]);
            $refund = $refunds->get($key, ['amount' => 0.0, 'returned_cost' => 0.0, 'missing' => false]);
            $expense = (float) $expenses->get($key, 0.0);
            $netRevenue = (float) $sales['revenue'] - (float) $refund['amount'];
            $costAvailable = ! $cost['missing'] && ! $refund['missing'];
            $netCost = $costAvailable ? (float) $cost['cost'] - (float) $refund['returned_cost'] : null;
            $netProfit = $netCost === null ? null : $netRevenue - $netCost - $expense;

            return [
                'label' => match ($period) {
                    'daily' => $bucketDate->format('d M'),
                    'monthly' => $bucketDate->format('M Y'),
                    'yearly' => $bucketDate->format('Y'),
                    default => $bucketDate->toDateString(),
                },
                'revenue' => $netRevenue,
                'expenses' => $expense,
                'profit' => $netProfit,
                'loss' => $netProfit === null ? null : ($netProfit < 0 ? abs($netProfit) : 0.0),
            ];
        });
    }

    /**
     * @return Collection<string, array{revenue: float, transactions: int}>
     */
    private function salesByBucket(string $period, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $bucket = $this->bucketExpression($period, 'paid_at');

        return $this->paidOrders()
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw("{$bucket} as bucket, COALESCE(SUM(total_price), 0) as revenue, COUNT(*) as transactions")
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket')
            ->map(fn ($row): array => [
                'revenue' => (float) $row->revenue,
                'transactions' => (int) $row->transactions,
            ]);
    }

    /**
     * @return Collection<string, array{cost: float, missing: bool}>
     */
    private function costsByBucket(string $period, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $bucket = $this->bucketExpression($period, 'orders.paid_at');

        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.payment_status', ['paid', 'partially_refunded', 'refunded'])
            ->whereNotIn('orders.status', ['canceled', 'cancelled'])
            ->whereNotNull('orders.paid_at')
            ->whereBetween('orders.paid_at', [$start, $end])
            ->selectRaw("{$bucket} as bucket, COALESCE(SUM(order_items.unit_cost * order_items.quantity), 0) as cost, SUM(CASE WHEN order_items.unit_cost IS NULL THEN 1 ELSE 0 END) as missing")
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket')
            ->map(fn ($row): array => [
                'cost' => (float) $row->cost,
                'missing' => (int) $row->missing > 0,
            ]);
    }

    /**
     * @return Collection<string, array{amount: float, returned_cost: float, missing: bool}>
     */
    private function refundsByBucket(string $period, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $bucket = $this->bucketExpression($period, 'order_refunds.refunded_at');

        return DB::table('order_refunds')
            ->leftJoin('order_items', 'order_items.id', '=', 'order_refunds.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_refunds.order_id')
            ->where('order_refunds.status', 'completed')
            ->whereNotNull('order_refunds.refunded_at')
            ->whereIn('orders.payment_status', ['paid', 'partially_refunded', 'refunded'])
            ->whereNotIn('orders.status', ['canceled', 'cancelled'])
            ->whereNotNull('orders.paid_at')
            ->whereBetween('order_refunds.refunded_at', [$start, $end])
            ->selectRaw("{$bucket} as bucket, COALESCE(SUM(order_refunds.amount), 0) as amount, COALESCE(SUM(order_items.unit_cost * order_refunds.quantity_returned), 0) as returned_cost, SUM(CASE WHEN order_refunds.quantity_returned > 0 AND order_items.unit_cost IS NULL THEN 1 ELSE 0 END) as missing")
            ->groupBy('bucket')
            ->get()
            ->keyBy('bucket')
            ->map(fn ($row): array => [
                'amount' => (float) $row->amount,
                'returned_cost' => (float) $row->returned_cost,
                'missing' => (int) $row->missing > 0,
            ]);
    }

    /**
     * @return Collection<string, float>
     */
    private function expensesByBucket(string $period, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $bucket = $this->bucketExpression($period, 'expense_date');

        return OperationalExpense::query()
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->selectRaw("{$bucket} as bucket, COALESCE(SUM(amount), 0) as amount")
            ->groupBy('bucket')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->bucket => (float) $row->amount]);
    }

    /**
     * @return Collection<int, array{name: string, quantity: int}>
     */
    private function bestSellers(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $sales = OrderItem::query()
            ->whereHas('order', fn (Builder $query) => $this->applyPaidOrderConstraints($query, $start, $end))
            ->selectRaw('product_name as name, SUM(quantity) as quantity')
            ->groupBy('product_name')
            ->orderByDesc('quantity')
            ->limit(10)
            ->get()
            ->keyBy('name');

        $returns = $this->completedPaidRefunds()
            ->whereBetween('refunded_at', [$start, $end])
            ->where('quantity_returned', '>', 0)
            ->whereHas('orderItem')
            ->join('order_items', 'order_refunds.order_item_id', '=', 'order_items.id')
            ->selectRaw('order_items.product_name as name, SUM(order_refunds.quantity_returned) as quantity')
            ->groupBy('order_items.product_name')
            ->get()
            ->keyBy('name');

        return $sales->map(fn ($sale): array => [
            'name' => (string) $sale->name,
            'quantity' => max(0, (int) $sale->quantity - (int) ($returns->get($sale->name)?->quantity ?? 0)),
        ])->sortByDesc('quantity')->take(5)->values();
    }

    private function paidOrders(): Builder
    {
        $query = Order::query();
        $this->applyPaidOrderStatus($query);

        return $query;
    }

    private function applyPaidOrderConstraints(Builder $query, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $this->applyPaidOrderStatus($query);
        $query->whereBetween('paid_at', [$start, $end]);
    }

    private function applyPaidOrderStatus(Builder $query): void
    {
        $query->whereIn('payment_status', ['paid', 'partially_refunded', 'refunded'])
            ->whereNotIn('status', ['canceled', 'cancelled'])
            ->whereNotNull('paid_at');
    }

    private function completedPaidRefunds(): Builder
    {
        return OrderRefund::query()
            ->where('status', 'completed')
            ->whereNotNull('refunded_at')
            ->whereHas('order', fn (Builder $query) => $this->applyPaidOrderStatus($query));
    }

    private function bucketExpression(string $period, string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return match ($period) {
                'daily' => "strftime('%Y-%m-%d', {$column})",
                'monthly' => "strftime('%Y-%m-01', {$column})",
                'yearly' => "strftime('%Y-01-01', {$column})",
                default => "strftime('%Y-%m-%d', {$column})",
            };
        }

        return match ($period) {
            'daily' => "DATE({$column})",
            'monthly' => "DATE_FORMAT({$column}, '%Y-%m-01')",
            'yearly' => "DATE_FORMAT({$column}, '%Y-01-01')",
            default => "DATE({$column})",
        };
    }
}
