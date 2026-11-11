<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OperationalExpense;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_report_uses_paid_sales_and_accounts_for_refunds_and_expenses(): void
    {
        $administrator = $this->signInAsAdministrator();
        $product = $this->createProduct(40);
        [$paidOrder, $paidItem] = $this->createOrder('DH-PAID-001', $product);
        $paidOrder->update(['payment_status' => 'partially_refunded']);
        $this->createRefund($paidOrder, $paidItem, $administrator, 50, 1);
        OperationalExpense::create([
            'created_by' => $administrator->id,
            'category' => 'Bahan baku',
            'description' => 'Belanja harian',
            'amount' => 30,
            'expense_date' => '2026-04-15',
        ]);
        $this->assertDatabaseHas('operational_expenses', [
            'expense_date' => '2026-04-15 00:00:00',
            'amount' => 30,
        ]);
        [$cancelledOrder] = $this->createOrder('DH-CANCEL-001', $product);
        $cancelledOrder->update(['status' => 'cancelled']);
        $this->createRefund(
            $cancelledOrder,
            $cancelledOrder->items()->firstOrFail(),
            $administrator,
            100,
            1,
        );
        [$unpaidOrder] = $this->createOrder('DH-UNPAID-001', $product, 'unpaid', null);

        $response = $this->get(route('admin.reports.index', [
            'period' => 'daily',
            'date' => '2026-04-15',
        ]));

        $response->assertOk()
            ->assertSee('Rp 150')
            ->assertSee('Rp 40')
            ->assertSee('Rp 30')
            ->assertSee('Rp 80')
            ->assertSee('DH-PAID-001')
            ->assertDontSee('DH-CANCEL-001')
            ->assertViewHas('orders', fn ($orders): bool => $orders->count() === 1
                && $orders->contains('transaction_code', 'DH-PAID-001'));
    }

    public function test_report_marks_profit_unavailable_when_a_sold_item_has_no_cost_snapshot(): void
    {
        $this->signInAsAdministrator();
        [$order] = $this->createOrder('DH-NO-COST-001', $this->createProduct(null));

        $this->get(route('admin.reports.index', [
            'period' => 'daily',
            'date' => '2026-04-15',
        ]))
            ->assertOk()
            ->assertSee('HPP dan laba belum dapat dihitung secara akurat')
            ->assertSee('Belum dapat dihitung');

        $this->assertSame('paid', $order->payment_status);
    }

    public function test_expenses_greater_than_sales_are_reported_as_a_loss(): void
    {
        $administrator = $this->signInAsAdministrator();
        $this->createOrder('DH-LOSS-001', $this->createProduct(40));
        OperationalExpense::create([
            'created_by' => $administrator->id,
            'category' => 'Operasional',
            'description' => 'Biaya di atas penjualan',
            'amount' => 300,
            'expense_date' => '2026-04-15',
        ]);

        $this->get(route('admin.reports.index', [
            'period' => 'daily',
            'date' => '2026-04-15',
        ]))
            ->assertOk()
            ->assertSee('Rp 180')
            ->assertSee('Total kerugian')
            ->assertSee('Laba bersih');
    }

    public function test_empty_period_renders_zero_totals_without_inventing_sales(): void
    {
        $this->signInAsAdministrator();

        $this->get(route('admin.reports.index', [
            'period' => 'monthly',
            'month' => '2026-02',
        ]))
            ->assertOk()
            ->assertSee('Tidak ada transaksi yang sesuai dengan periode')
            ->assertSee('Rp 0')
            ->assertViewHas('report', fn (array $report): bool => $report['summary']['transaction_count'] === 0);
    }

    public function test_report_filter_escapes_search_input(): void
    {
        $this->signInAsAdministrator();

        $this->get(route('admin.reports.index', [
            'period' => 'daily',
            'date' => '2026-04-15',
            'search' => '<script>alert(1)</script>',
        ]))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_period_filter_limits_details_to_selected_month_and_year(): void
    {
        $this->signInAsAdministrator();
        $product = $this->createProduct(40);
        $this->createOrder('DH-APRIL-001', $product, 'paid', '2026-04-15 10:00:00');
        $this->createOrder('DH-MAY-001', $product, 'paid', '2026-05-01 10:00:00');

        $monthlyResponse = $this->get(route('admin.reports.index', [
            'period' => 'monthly',
            'month' => '2026-04',
        ]));
        $monthlyResponse->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->count() === 1
                && $orders->contains('transaction_code', 'DH-APRIL-001'));

        $yearlyResponse = $this->get(route('admin.reports.index', [
            'period' => 'yearly',
            'year' => '2026',
        ]));
        $yearlyResponse->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->count() === 2
                && $orders->contains('transaction_code', 'DH-APRIL-001')
                && $orders->contains('transaction_code', 'DH-MAY-001'));
    }

    public function test_monthly_and_yearly_reports_compare_revenue_with_previous_period(): void
    {
        $this->signInAsAdministrator();
        $product = $this->createProduct(40);
        $this->createOrder('DH-MARCH-001', $product, 'paid', '2026-03-15 10:00:00');
        $this->createOrder('DH-APRIL-001', $product, 'paid', '2026-04-15 10:00:00');
        $this->createOrder('DH-LAST-YEAR-001', $product, 'paid', '2025-04-15 10:00:00');

        $this->get(route('admin.reports.index', [
            'period' => 'monthly',
            'month' => '2026-04',
        ]))
            ->assertOk()
            ->assertSee('0,0%')
            ->assertSee('bulan sebelumnya');

        $this->get(route('admin.reports.index', [
            'period' => 'yearly',
            'year' => '2026',
        ]))
            ->assertOk()
            ->assertSee('+100,0%')
            ->assertSee('tahun sebelumnya');
    }

    public function test_guest_and_non_administrator_cannot_access_financial_reports(): void
    {
        $this->get(route('admin.reports.index'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.reports.index'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_administrator_can_record_an_operational_expense(): void
    {
        $administrator = $this->signInAsAdministrator();

        $this->post(route('admin.reports.expenses.store'), [
            'category' => 'Operasional',
            'description' => 'Gas untuk dapur',
            'amount' => '12500.00',
            'expense_date' => '2026-04-15',
        ])
            ->assertRedirect();

        $this->assertDatabaseHas('operational_expenses', [
            'created_by' => $administrator->id,
            'category' => 'Operasional',
            'amount' => '12500.00',
        ]);

        $this->from(route('admin.reports.index'))
            ->post(route('admin.reports.expenses.store'), [
                'category' => 'Operasional',
                'description' => 'Nilai tidak valid',
                'amount' => '0',
                'expense_date' => '2026-04-15',
            ])
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('operational_expenses', 1);
    }

    public function test_refund_is_recorded_and_payment_status_changes(): void
    {
        $administrator = $this->signInAsAdministrator();
        [$order, $item] = $this->createOrder('DH-REFUND-001', $this->createProduct(40));

        $this->post(route('admin.reports.refunds.store'), [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'amount' => '50.00',
            'quantity_returned' => 1,
            'notes' => 'Satu item diretur',
        ])->assertRedirect();

        $this->assertDatabaseHas('order_refunds', [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'created_by' => $administrator->id,
            'amount' => '50.00',
            'quantity_returned' => 1,
            'status' => 'completed',
        ]);
        $this->assertSame('partially_refunded', $order->refresh()->payment_status);
    }

    public function test_refund_cannot_exceed_transaction_value_or_returned_quantity(): void
    {
        $this->signInAsAdministrator();
        [$order, $item] = $this->createOrder('DH-REFUND-LIMIT-001', $this->createProduct(40));
        $this->createRefund($order, $item, User::query()->where('email', 'admin@usahaku.com')->firstOrFail(), 50, 1);
        $order->update(['payment_status' => 'partially_refunded']);

        $this->from(route('admin.reports.index'))
            ->post(route('admin.reports.refunds.store'), [
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'amount' => '150.00',
                'quantity_returned' => 2,
            ])
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHasErrors('quantity_returned');

        $this->assertDatabaseCount('order_refunds', 1);

        $this->from(route('admin.reports.index'))
            ->post(route('admin.reports.refunds.store'), [
                'order_id' => $order->id,
                'amount' => '150.01',
                'quantity_returned' => 0,
            ])
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('order_refunds', 1);
    }

    public function test_full_refund_marks_the_order_refunded_and_reverses_revenue_and_cost(): void
    {
        $this->travelTo('2026-04-15 16:00:00');
        $this->signInAsAdministrator();
        [$order, $item] = $this->createOrder('DH-FULL-REFUND-001', $this->createProduct(40));

        $this->post(route('admin.reports.refunds.store'), [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'amount' => '200.00',
            'quantity_returned' => 2,
        ])->assertRedirect();

        $this->assertSame('refunded', $order->refresh()->payment_status);

        $this->get(route('admin.reports.index', [
            'period' => 'daily',
            'date' => '2026-04-15',
        ]))
            ->assertOk()
            ->assertSee('HPP produk')
            ->assertViewHas('report', function (array $report): bool {
                $this->assertSame(0.0, $report['summary']['revenue']);
                $this->assertSame(0.0, $report['summary']['cost_of_goods']);

                return true;
            });
    }

    public function test_administrator_can_mark_an_order_paid(): void
    {
        $this->signInAsAdministrator();
        [$order] = $this->createOrder('DH-MARK-PAID-001', $this->createProduct(40), 'unpaid', null);

        $this->patch(route('admin.reports.orders.payment', $order))
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
        ]);
        $this->assertNotNull($order->refresh()->paid_at);
    }

    public function test_report_exports_pdf_and_excel_downloads(): void
    {
        $this->signInAsAdministrator();

        $this->get(route('admin.reports.export.pdf', [
            'period' => 'daily',
            'date' => '2026-04-15',
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('admin.reports.export.excel', [
            'period' => 'daily',
            'date' => '2026-04-15',
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    private function signInAsAdministrator(): User
    {
        $administrator = User::factory()->create([
            'email' => 'admin@usahaku.com',
        ]);

        $this->actingAs($administrator);

        return $administrator;
    }

    private function createProduct(?int $costPrice): Product
    {
        $category = Category::create([
            'name' => 'Menu Uji',
            'slug' => 'menu-uji',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Menu Laporan',
            'price' => 100,
            'cost_price' => $costPrice,
            'stock_limit' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{Order, OrderItem}
     */
    private function createOrder(
        string $transactionCode,
        Product $product,
        string $paymentStatus = 'paid',
        ?string $paidAt = '2026-04-15 10:00:00',
    ): array {
        $order = Order::create([
            'transaction_code' => $transactionCode,
            'customer_name' => 'Pelanggan Uji',
            'customer_whatsapp' => '628123456789',
            'pickup_date' => '2026-04-16',
            'pickup_time' => '12:00:00',
            'total_price' => 200,
            'status' => 'completed',
            'payment_status' => $paymentStatus,
            'paid_at' => $paidAt,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => 100,
            'quantity' => 2,
            'subtotal' => 200,
        ]);

        return [$order, $item];
    }

    private function createRefund(
        Order $order,
        OrderItem $item,
        User $administrator,
        int $amount,
        int $quantityReturned,
    ): OrderRefund {
        return OrderRefund::create([
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'created_by' => $administrator->id,
            'amount' => $amount,
            'quantity_returned' => $quantityReturned,
            'status' => 'completed',
            'refunded_at' => '2026-04-15 15:00:00',
        ]);
    }
}
