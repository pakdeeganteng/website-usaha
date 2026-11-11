<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_authenticated_customer_can_create_a_pending_preorder_and_see_its_notification(): void
    {
        $this->travelTo(now()->startOfDay());
        $customer = User::factory()->create();
        $product = $this->createProduct();

        $this->actingAs($customer)
            ->post(route('orders.store', $product), [
                'customer_name' => 'Nadia Customer',
                'customer_whatsapp' => '08123456789',
                'quantity' => 2,
                'pickup_date' => today()->toDateString(),
                'pickup_time' => '12:30',
                'note' => 'Tidak pedas',
            ])
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'user_id' => $customer->id,
            'customer_name' => 'Nadia Customer',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_price' => '50000.00',
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_name' => 'Ayam Bakar',
            'unit_price' => '25000.00',
            'unit_cost' => '10000.00',
            'quantity' => 2,
            'subtotal' => '50000.00',
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'old_status' => null,
            'new_status' => 'pending',
            'changed_by' => $customer->id,
        ]);

        $this->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Pesanan Sedang Diproses.')
            ->assertSee('Pesanan Anda berhasil dikirim dan sedang menunggu konfirmasi admin.');
    }

    public function test_customer_cannot_order_more_than_the_remaining_preorder_limit(): void
    {
        $this->travelTo(now()->startOfDay());
        $customer = User::factory()->create();
        $product = $this->createProduct(stockLimit: 2);
        $payload = [
            'customer_name' => $customer->name,
            'customer_whatsapp' => '08123456789',
            'quantity' => 2,
            'pickup_date' => today()->toDateString(),
            'pickup_time' => '12:30',
        ];

        $this->actingAs($customer)
            ->post(route('orders.store', $product), $payload)
            ->assertRedirect(route('orders.index'));

        $this->from(route('menu.show', $product))
            ->post(route('orders.store', $product), [...$payload, 'quantity' => 1])
            ->assertSessionHasErrors([
                'quantity' => 'Jumlah pesanan melebihi stok yang masih tersedia.',
            ]);

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_guest_cannot_submit_an_order(): void
    {
        $this->travelTo(now()->startOfDay());
        $product = $this->createProduct();

        $this->post(route('orders.store', $product), [
            'customer_name' => 'Nadia Customer',
            'customer_whatsapp' => '08123456789',
            'quantity' => 1,
            'pickup_date' => today()->toDateString(),
            'pickup_time' => '12:30',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_history_only_displays_orders_owned_by_the_authenticated_customer(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $this->createOrder($customer, 'DH-CUSTOMER-001', 'Pesanan Saya');
        $this->createOrder($otherCustomer, 'DH-OTHER-001', 'Pesanan Rahasia');

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee('DH-CUSTOMER-001')
            ->assertSee('Pesanan Saya')
            ->assertDontSee('DH-OTHER-001')
            ->assertDontSee('Pesanan Rahasia');
    }

    public function test_order_submission_rejects_a_pickup_date_different_from_the_product_availability_date(): void
    {
        $this->travelTo(now()->startOfDay());
        $customer = User::factory()->create();
        $availableDate = today()->addDay()->toDateString();
        $product = $this->createProduct(availableDate: $availableDate);

        $this->actingAs($customer)
            ->from(route('menu.show', $product))
            ->post(route('orders.store', $product), [
                'customer_name' => $customer->name,
                'customer_whatsapp' => '08123456789',
                'quantity' => 1,
                'pickup_date' => today()->toDateString(),
                'pickup_time' => '12:30',
            ])
            ->assertSessionHasErrors([
                'pickup_date' => 'Tanggal pengambilan harus sesuai tanggal ketersediaan menu.',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_product_detail_displays_a_csrf_protected_preorder_form(): void
    {
        $customer = User::factory()->create();
        $product = $this->createProduct();

        $this->actingAs($customer)
            ->get(route('menu.show', $product))
            ->assertOk()
            ->assertSee('Buat pre-order')
            ->assertSee('name="_token"', false)
            ->assertSee(route('orders.store', $product), false);
    }

    private function createProduct(int $stockLimit = 5, ?string $availableDate = null): Product
    {
        $category = Category::query()->create([
            'name' => 'Menu Utama',
            'slug' => 'menu-utama',
            'description' => 'Menu harian',
            'is_active' => true,
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Ayam Bakar',
            'description' => 'Ayam bakar rumahan',
            'price' => '25000.00',
            'cost_price' => '10000.00',
            'available_date' => $availableDate,
            'stock_limit' => $stockLimit,
            'is_active' => true,
        ]);
    }

    private function createOrder(User $customer, string $transactionCode, string $productName): void
    {
        $order = $customer->orders()->create([
            'transaction_code' => $transactionCode,
            'customer_name' => $customer->name,
            'customer_whatsapp' => '08123456789',
            'pickup_date' => today()->toDateString(),
            'pickup_time' => '12:30',
            'total_price' => '25000.00',
            'status' => 'pending',
            'payment_status' => 'unpaid',
        ]);

        $order->items()->create([
            'product_name' => $productName,
            'unit_price' => '25000.00',
            'quantity' => 1,
            'subtotal' => '25000.00',
        ]);
        $order->statusHistories()->create([
            'changed_by' => $customer->id,
            'new_status' => 'pending',
        ]);
    }
}
