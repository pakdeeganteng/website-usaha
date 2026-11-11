<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_tables_are_created(): void
    {
        foreach ([
            'users',
            'categories',
            'products',
            'orders',
            'order_items',
            'business_settings',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected the {$table} table to exist.");
        }
    }

    public function test_menu_seeder_can_be_run_more_than_once(): void
    {
        $this->seed(MenuSeeder::class);
        $this->seed(MenuSeeder::class);

        $this->assertDatabaseCount('categories', 3);
        $this->assertDatabaseCount('products', 4);
    }

    public function test_order_item_keeps_transaction_snapshot_after_product_is_deleted(): void
    {
        $category = Category::create([
            'name' => 'Makanan Utama',
            'slug' => 'makanan-utama',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Nasi Ayam Bakar',
            'price' => 28000,
            'cost_price' => 16000,
            'stock_limit' => 20,
        ]);
        $order = Order::create([
            'transaction_code' => 'DH-20261109-0001',
            'customer_name' => 'Pelanggan Uji',
            'customer_whatsapp' => '081234567890',
            'pickup_date' => '2026-11-10',
            'pickup_time' => '12:30:00',
            'total_price' => 56000,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 2,
            'subtotal' => 56000,
        ]);

        $this->assertTrue($order->items()->firstOrFail()->is($item));
        $this->assertTrue($item->product()->firstOrFail()->is($product));
        $this->assertTrue($category->products()->firstOrFail()->is($product));

        $product->delete();
        $item->refresh();

        $this->assertNull($item->product_id);
        $this->assertSame('Nasi Ayam Bakar', $item->product_name);
        $this->assertSame('28000.00', $item->unit_price);
        $this->assertSame('16000.00', $item->unit_cost);

        $product->update(['cost_price' => 18000]);
        $this->assertSame('16000.00', $item->refresh()->unit_cost);
    }
}
