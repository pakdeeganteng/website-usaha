<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_available_menu_highlights(): void
    {
        $product = $this->createProduct('Nasi Ayam Rumahan');

        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Masakan rumahan yang')
            ->assertSee($product->name)
            ->assertSee(route('menu.index'));
    }

    public function test_menu_page_filters_products_by_active_category(): void
    {
        $selectedProduct = $this->createProduct('Sup Ayam', 'makanan-utama');
        $otherProduct = $this->createProduct('Es Teh', 'minuman');

        $this->actingAs(User::factory()->create())
            ->get(route('menu.index', ['kategori' => 'makanan-utama']))
            ->assertOk()
            ->assertSee($selectedProduct->name)
            ->assertDontSee($otherProduct->name);
    }

    public function test_product_detail_displays_product_information_and_contact_action(): void
    {
        $product = $this->createProduct('Nasi Bakar');

        $this->actingAs(User::factory()->create())
            ->get(route('menu.show', $product))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Tanyakan ketersediaan')
            ->assertSee(route('contact'));
    }

    public function test_inactive_product_detail_returns_not_found(): void
    {
        $product = $this->createProduct('Menu Tidak Aktif', active: false);

        $this->actingAs(User::factory()->create())
            ->get(route('menu.show', $product))
            ->assertNotFound();
    }

    public function test_contact_page_uses_saved_business_contact_settings(): void
    {
        BusinessSetting::create([
            'setting_key' => 'phone',
            'setting_value' => '081234567890',
        ]);
        BusinessSetting::create([
            'setting_key' => 'whatsapp',
            'setting_value' => '081234567890',
        ]);
        BusinessSetting::create([
            'setting_key' => 'address',
            'setting_value' => 'Bandung',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('contact'))
            ->assertOk()
            ->assertSee('081234567890')
            ->assertSee('Bandung')
            ->assertSee('https://wa.me/6281234567890');
    }

    public function test_about_page_renders_business_story(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('about'))
            ->assertOk()
            ->assertSee('Berawal dari rasa yang selalu dirindukan.')
            ->assertSee('Bahan pilihan');
    }

    private function createProduct(
        string $name,
        string $categorySlug = 'makanan-utama',
        bool $active = true,
    ): Product {
        $category = Category::firstOrCreate(
            ['slug' => $categorySlug],
            ['name' => ucfirst(str_replace('-', ' ', $categorySlug))],
        );

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'description' => 'Menu rumahan pilihan.',
            'price' => 25000,
            'stock_limit' => 10,
            'is_active' => $active,
        ]);
    }
}
