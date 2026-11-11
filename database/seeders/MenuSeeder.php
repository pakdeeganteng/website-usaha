<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            'makanan-utama' => [
                'name' => 'Makanan Utama',
                'products' => [
                    ['name' => 'Nasi Ayam Bakar', 'description' => 'Nasi dengan ayam bakar berbumbu dan sambal.', 'price' => 28000],
                    ['name' => 'Nasi Rendang', 'description' => 'Nasi dengan rendang sapi dan sayuran.', 'price' => 32000],
                ],
            ],
            'lauk' => [
                'name' => 'Lauk',
                'products' => [
                    ['name' => 'Ayam Bumbu Rujak', 'description' => 'Ayam berbumbu rujak khas rumahan.', 'price' => 24000],
                ],
            ],
            'minuman' => [
                'name' => 'Minuman',
                'products' => [
                    ['name' => 'Es Teh Manis', 'description' => 'Teh manis dingin.', 'price' => 7000],
                ],
            ],
        ];

        foreach ($menus as $categorySlug => $menu) {
            $category = Category::updateOrCreate(
                ['slug' => $categorySlug],
                ['name' => $menu['name'], 'is_active' => true],
            );

            foreach ($menu['products'] as $product) {
                Product::updateOrCreate(
                    ['category_id' => $category->id, 'name' => $product['name']],
                    [
                        ...$product,
                        'stock_limit' => 20,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
