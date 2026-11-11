<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_administrator_can_confirm_an_order_and_customer_sees_the_latest_notification(): void
    {
        $this->travelTo('2026-01-15 09:00:00');
        $customer = User::factory()->create();
        $administrator = $this->createAdministrator();
        $order = $this->createOrder($customer);
        $this->travelTo('2026-01-15 13:15:00');

        $this->actingAs($administrator)
            ->patch(route('admin.orders.status', $order), [
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => 'pending',
            'new_status' => 'confirmed',
            'changed_by' => $administrator->id,
            'reason' => null,
        ]);
        $this->assertDatabaseCount('order_status_histories', 2);
        $history = OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('new_status', 'confirmed')
            ->firstOrFail();
        $this->assertSame('2026-01-15 13:15:00', $history->created_at->format('Y-m-d H:i:s'));

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Pemesanan Berhasil!')
            ->assertSee('Selamat! Pesanan Anda telah dikonfirmasi dan berhasil dipesan. Terima kasih telah menggunakan layanan kami.');
    }

    public function test_administrator_must_provide_a_reason_to_reject_an_order(): void
    {
        $this->travelTo(now()->startOfDay());
        $administrator = $this->createAdministrator();
        $order = $this->createOrder(User::factory()->create());

        $this->actingAs($administrator)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), [
                'status' => 'rejected',
            ])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertDatabaseCount('order_status_histories', 1);
    }

    public function test_administrator_can_reject_an_order_and_customer_sees_the_reason_and_notification(): void
    {
        $this->travelTo(now()->startOfDay());
        $customer = User::factory()->create();
        $administrator = $this->createAdministrator();
        $order = $this->createOrder($customer);
        $reason = '<script>alert("x")</script>Menu tidak tersedia';

        $this->actingAs($administrator)
            ->patch(route('admin.orders.status', $order), [
                'status' => 'rejected',
                'reason' => $reason,
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'old_status' => 'pending',
            'new_status' => 'rejected',
            'reason' => $reason,
        ]);

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Pemesanan Ditolak.')
            ->assertSee('Maaf, pemesanan Anda belum dapat disetujui. Silakan hubungi admin untuk informasi lebih lanjut.')
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("x")</script>', false);
    }

    public function test_repeating_the_current_status_does_not_add_history_or_duplicate_an_order(): void
    {
        $this->travelTo(now()->startOfDay());
        $administrator = $this->createAdministrator();
        $order = $this->createOrder(User::factory()->create());

        $this->actingAs($administrator)
            ->patch(route('admin.orders.status', $order), [
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->patch(route('admin.orders.status', $order), [
            'status' => 'confirmed',
        ])->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_status_histories', 2);
    }

    public function test_rejected_order_cannot_be_marked_as_paid(): void
    {
        $this->travelTo(now()->startOfDay());
        $administrator = $this->createAdministrator();
        $order = $this->createOrder(User::factory()->create(), 'rejected');

        $this->actingAs($administrator)
            ->patch(route('admin.reports.orders.payment', $order))
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'rejected',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_customer_cannot_access_administrator_order_management(): void
    {
        $this->travelTo(now()->startOfDay());
        $customer = User::factory()->create();
        $order = $this->createOrder($customer);

        $this->actingAs($customer)
            ->get(route('admin.orders.show', $order))
            ->assertRedirect(route('admin.login'));
    }

    public function test_administrator_can_view_and_filter_the_order_queue(): void
    {
        $this->travelTo(now()->startOfDay());
        $administrator = $this->createAdministrator();
        $customer = User::factory()->create();
        $order = $this->createOrder($customer);

        $this->actingAs($administrator)
            ->get(route('admin.orders.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee($order->transaction_code)
            ->assertSee($customer->name)
            ->assertSee('Pending');
    }

    public function test_confirmed_order_status_cannot_be_reopened_or_rejected(): void
    {
        $this->travelTo(now()->startOfDay());
        $administrator = $this->createAdministrator();
        $order = $this->createOrder(User::factory()->create(), 'confirmed');

        $this->actingAs($administrator)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), [
                'status' => 'rejected',
                'reason' => 'Pesanan sudah dikonfirmasi',
            ])
            ->assertSessionHasErrors([
                'status' => 'Status hanya dapat diubah saat pesanan masih Pending.',
            ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed']);
        $this->assertDatabaseCount('order_status_histories', 1);
    }

    private function createAdministrator(): User
    {
        return User::factory()->create(['email' => 'admin@usahaku.com']);
    }

    private function createOrder(User $customer, string $status = 'pending'): Order
    {
        $order = $customer->orders()->create([
            'transaction_code' => 'DH-'.strtoupper(str_replace('-', '', fake()->uuid())),
            'customer_name' => $customer->name,
            'customer_whatsapp' => '08123456789',
            'pickup_date' => today()->toDateString(),
            'pickup_time' => '12:30',
            'total_price' => '25000.00',
            'status' => $status,
            'payment_status' => 'unpaid',
        ]);

        $order->items()->create([
            'product_name' => 'Ayam Bakar',
            'unit_price' => '25000.00',
            'quantity' => 1,
            'subtotal' => '25000.00',
        ]);
        $order->statusHistories()->create([
            'changed_by' => $customer->id,
            'new_status' => $status,
        ]);

        return $order;
    }
}
