<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\OperationalExpense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\Product;
use App\Services\Admin\FinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinancialReportController extends Controller
{
    public function index(Request $request, FinancialReportService $reportService): View
    {
        return view('admin.reports.index', $this->reportViewData($request, $reportService));
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'expense_date' => ['required', 'date'],
        ]);

        OperationalExpense::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Pengeluaran berhasil dicatat.');
    }

    public function storeRefund(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'order_item_id' => ['nullable', 'integer', 'exists:order_items,id'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'quantity_returned' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $request): void {
            $order = Order::query()->lockForUpdate()->findOrFail($validated['order_id']);

            if (
                ! in_array($order->payment_status, ['paid', 'partially_refunded'], true)
                || in_array($order->status, ['canceled', 'cancelled'], true)
            ) {
                throw ValidationException::withMessages([
                    'order_id' => 'Pengembalian hanya dapat dicatat untuk pesanan yang telah dibayar.',
                ]);
            }

            $item = null;
            if (! empty($validated['order_item_id'])) {
                $item = $order->items()->lockForUpdate()->find($validated['order_item_id']);

                if ($item === null) {
                    throw ValidationException::withMessages([
                        'order_item_id' => 'Produk tidak termasuk dalam pesanan yang dipilih.',
                    ]);
                }
            }

            $quantityReturned = (int) $validated['quantity_returned'];
            if ($quantityReturned > 0 && $item === null) {
                throw ValidationException::withMessages([
                    'order_item_id' => 'Pilih produk jika jumlah barang yang diretur lebih dari nol.',
                ]);
            }

            if ($item !== null && $quantityReturned > 0) {
                $alreadyReturned = (int) $order->refunds()
                    ->where('status', 'completed')
                    ->where('order_item_id', $item->id)
                    ->sum('quantity_returned');
                $alreadyRefundedForItemCents = $this->amountInCents(
                    (string) $order->refunds()
                        ->where('status', 'completed')
                        ->where('order_item_id', $item->id)
                        ->sum('amount')
                );

                if ($quantityReturned > $item->quantity - $alreadyReturned) {
                    throw ValidationException::withMessages([
                        'quantity_returned' => 'Jumlah retur melebihi sisa jumlah produk pada transaksi.',
                    ]);
                }

                $maximumItemRefundCents = $this->amountInCents((string) $item->unit_price)
                    * ($quantityReturned + $alreadyReturned);
                if ($alreadyRefundedForItemCents + $this->amountInCents((string) $validated['amount']) > $maximumItemRefundCents) {
                    throw ValidationException::withMessages([
                        'amount' => 'Nilai refund melebihi nilai produk yang telah diretur.',
                    ]);
                }
            }

            $alreadyRefundedCents = $this->amountInCents(
                (string) $order->refunds()->where('status', 'completed')->sum('amount')
            );
            $refundAmountCents = $this->amountInCents((string) $validated['amount']);
            $orderTotalCents = $this->amountInCents((string) $order->total_price);

            if ($refundAmountCents > $orderTotalCents - $alreadyRefundedCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Nilai pengembalian melebihi sisa nilai transaksi.',
                ]);
            }

            OrderRefund::create([
                'order_id' => $order->id,
                'order_item_id' => $item?->id,
                'created_by' => $request->user()->id,
                'amount' => $validated['amount'],
                'quantity_returned' => $quantityReturned,
                'status' => 'completed',
                'refunded_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->update([
                'payment_status' => $alreadyRefundedCents + $refundAmountCents >= $orderTotalCents
                    ? 'refunded'
                    : 'partially_refunded',
            ]);
        });

        return back()->with('status', 'Pengembalian dana berhasil dicatat.');
    }

    public function markPaid(Order $order): RedirectResponse
    {
        if (in_array($order->status, ['canceled', 'cancelled', 'rejected'], true)) {
            throw ValidationException::withMessages([
                'payment' => 'Pesanan yang dibatalkan atau ditolak tidak dapat ditandai lunas.',
            ]);
        }

        if ($order->payment_status !== 'unpaid') {
            throw ValidationException::withMessages([
                'payment' => 'Status pembayaran pesanan ini bukan belum dibayar.',
            ]);
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);

        return back()->with('status', 'Pembayaran berhasil ditandai lunas.');
    }

    public function updateProductCost(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'cost_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
        ]);

        $product->update(['cost_price' => $validated['cost_price'] ?? null]);

        return back()->with('status', 'Modal produk berhasil diperbarui.');
    }

    public function exportPdf(Request $request, FinancialReportService $reportService)
    {
        $data = $this->reportViewData($request, $reportService, false);

        return Pdf::loadView('admin.reports.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download('laporan-keuangan-'.$data['report']['start']->format('Ymd').'.pdf');
    }

    public function exportExcel(Request $request, FinancialReportService $reportService): BinaryFileResponse
    {
        $data = $this->reportViewData($request, $reportService, false, false);
        $path = tempnam(sys_get_temp_dir(), 'dapurharian-report-');

        if ($path === false) {
            throw new \RuntimeException('Tidak dapat menyiapkan file ekspor laporan.');
        }

        $writer = new Writer;
        $writer->openToFile($path);
        $summary = $data['report']['summary'];
        $writer->addRow(Row::fromValues(['Ringkasan periode', $data['report']['start']->format('Y-m-d').' - '.$data['report']['end']->format('Y-m-d')]));
        $writer->addRow(Row::fromValues(['Omzet bersih', (float) $summary['revenue']]));
        $writer->addRow(Row::fromValues(['Refund', (float) $summary['refund_total']]));
        $writer->addRow(Row::fromValues(['HPP', $summary['cost_of_goods'] === null ? 'Belum dapat dihitung' : (float) $summary['cost_of_goods']]));
        $writer->addRow(Row::fromValues(['Pengeluaran', (float) $summary['expenses']]));
        $writer->addRow(Row::fromValues(['Laba kotor', $summary['gross_profit'] === null ? 'Belum dapat dihitung' : (float) $summary['gross_profit']]));
        $writer->addRow(Row::fromValues(['Kerugian', $summary['loss'] === null ? 'Belum dapat dihitung' : (float) $summary['loss']]));
        $writer->addRow(Row::fromValues(['Laba bersih', $summary['net_profit'] === null ? 'Belum dapat dihitung' : (float) $summary['net_profit']]));
        $writer->addRow(Row::fromValues(['Jumlah transaksi', $summary['transaction_count']]));
        $writer->addRow(Row::fromValues(['Produk terjual', $summary['products_sold']]));
        $writer->addRow(Row::fromValues(['Produk diretur', $summary['products_returned']]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues([
            'Nomor transaksi',
            'Tanggal pembayaran',
            'Produk',
            'Jumlah',
            'Harga jual satuan',
            'Modal satuan',
            'Total penjualan',
            'Keuntungan/kerugian',
            'Status transaksi',
        ]));

        $orders = $this->filteredOrders($reportService, $data['report']['start'], $data['report']['end'], $data['search'])
            ->with('items')
            ->lazyById(500);

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $profit = $item->unit_cost === null
                    ? null
                    : ((float) $item->unit_price - (float) $item->unit_cost) * $item->quantity;

                $writer->addRow(Row::fromValues([
                    $order->transaction_code,
                    $order->paid_at?->format('Y-m-d H:i:s'),
                    $item->product_name,
                    $item->quantity,
                    (float) $item->unit_price,
                    $item->unit_cost === null ? null : (float) $item->unit_cost,
                    (float) $item->subtotal,
                    $profit,
                    $order->status,
                ]));
            }
        }

        foreach ($reportService->refundsBetween($data['report']['start'], $data['report']['end'])
            ->with(['order', 'orderItem'])
            ->lazyById(500) as $refund) {
            $writer->addRow(Row::fromValues([
                'REFUND · '.$refund->order->transaction_code,
                $refund->refunded_at?->format('Y-m-d H:i:s'),
                $refund->orderItem?->product_name ?? 'Pengembalian dana',
                $refund->quantity_returned,
                null,
                null,
                -1 * (float) $refund->amount,
                null,
                'completed',
            ]));
        }

        foreach ($reportService->expensesBetween($data['report']['start'], $data['report']['end'])->lazyById(500) as $expense) {
            $writer->addRow(Row::fromValues([
                'PENGELUARAN',
                $expense->expense_date->format('Y-m-d'),
                $expense->category.' · '.$expense->description,
                null,
                null,
                null,
                -1 * (float) $expense->amount,
                null,
                'tercatat',
            ]));
        }

        $writer->close();

        return response()->download(
            $path,
            'laporan-keuangan-'.$data['report']['start']->format('Ymd').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportViewData(
        Request $request,
        FinancialReportService $reportService,
        bool $paginate = true,
        bool $includeOrders = true,
    ): array {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,monthly,yearly'],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $period = $validated['period'] ?? 'daily';
        $reference = match ($period) {
            'monthly' => isset($validated['month'])
                ? CarbonImmutable::createFromFormat('Y-m', $validated['month'])
                : CarbonImmutable::now(),
            'yearly' => isset($validated['year'])
                ? CarbonImmutable::create((int) $validated['year'], 1, 1)
                : CarbonImmutable::now(),
            default => isset($validated['date'])
                ? CarbonImmutable::parse($validated['date'])
                : CarbonImmutable::now(),
        };
        $report = $reportService->build($period, $reference);
        $ordersQuery = $this->filteredOrders($reportService, $report['start'], $report['end'], $validated['search'] ?? null)
            ->with('items')
            ->orderByDesc('paid_at')
            ->orderByDesc('id');
        $orders = $paginate
            ? $ordersQuery->paginate(20)->withQueryString()
            : ($includeOrders ? $ordersQuery->get() : collect());

        return [
            'businessSettings' => BusinessSetting::query()->pluck('setting_value', 'setting_key'),
            'period' => $period,
            'reference' => $reference,
            'report' => $report,
            'orders' => $orders,
            'search' => $validated['search'] ?? '',
            'expenses' => $includeOrders
                ? $reportService->expensesBetween($report['start'], $report['end'])
                    ->with('creator')
                    ->latest('expense_date')
                    ->when($paginate, fn (Builder $query) => $query->limit(20))
                    ->get()
                : collect(),
            'refunds' => $includeOrders
                ? $reportService->refundsBetween($report['start'], $report['end'])
                    ->with(['order', 'orderItem'])
                    ->latest('refunded_at')
                    ->when($paginate, fn (Builder $query) => $query->limit(20))
                    ->get()
                : collect(),
            'unpaidOrders' => Order::query()
                ->where('payment_status', 'unpaid')
                ->whereNotIn('status', ['canceled', 'cancelled'])
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(['id', 'transaction_code', 'customer_name', 'total_price']),
            'productsWithoutCost' => Product::query()
                ->whereNull('cost_price')
                ->orderBy('name')
                ->get(['id', 'name']),
            'refundableOrders' => Order::query()
                ->whereIn('payment_status', ['paid', 'partially_refunded'])
                ->whereNotIn('status', ['canceled', 'cancelled'])
                ->orderByDesc('paid_at')
                ->limit(50)
                ->get(['id', 'transaction_code', 'customer_name', 'total_price']),
            'refundableItems' => OrderItem::query()
                ->whereHas('order', fn (Builder $query) => $query
                    ->whereIn('payment_status', ['paid', 'partially_refunded'])
                    ->whereNotIn('status', ['canceled', 'cancelled']))
                ->with('order')
                ->withSum(['refunds as returned_quantity' => fn (Builder $query) => $query->where('status', 'completed')], 'quantity_returned')
                ->orderByDesc('id')
                ->limit(200)
                ->get(),
        ];
    }

    private function filteredOrders(
        FinancialReportService $reportService,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?string $search,
    ): Builder {
        return $reportService->paidOrdersBetween($start, $end)
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('transaction_code', 'like', '%'.$search.'%')
                        ->orWhere('customer_name', 'like', '%'.$search.'%')
                        ->orWhereHas('items', fn (Builder $items) => $items->where('product_name', 'like', '%'.$search.'%'));
                });
            });
    }

    private function amountInCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', number_format((float) $amount, 2, '.', ''), 2), 2, '00');

        return ((int) $whole * 100) + (int) $fraction;
    }
}
