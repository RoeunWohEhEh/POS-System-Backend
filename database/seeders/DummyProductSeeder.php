<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;

class DummyProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Hot Drinks' => Category::firstOrCreate(['name' => 'Hot Drinks'], ['status' => 1]),
            'Cold Drinks' => Category::firstOrCreate(['name' => 'Cold Drinks'], ['status' => 1]),
            'Pastries' => Category::firstOrCreate(['name' => 'Pastries'], ['status' => 1]),
            'Food' => Category::firstOrCreate(['name' => 'Food'], ['status' => 1]),
            'Others' => Category::firstOrCreate(['name' => 'Others'], ['status' => 1]),
        ];

        $products = [
            [
                'name' => 'Espresso',
                'category' => 'Hot Drinks',
                'cost' => 0.50,
                'price' => 2.50,
                'image' => 'https://images.unsplash.com/photo-1510591509098-f4fdc6d0ff04?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Cappuccino',
                'category' => 'Hot Drinks',
                'cost' => 1.00,
                'price' => 3.50,
                'image' => 'https://images.unsplash.com/photo-1534778101976-62847782c213?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Latte',
                'category' => 'Hot Drinks',
                'cost' => 1.20,
                'price' => 4.00,
                'image' => 'https://images.unsplash.com/photo-1497935586351-b67a49e012bf?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Americano',
                'category' => 'Hot Drinks',
                'cost' => 0.60,
                'price' => 3.00,
                'image' => 'https://images.unsplash.com/photo-1551030173-122aabc4489c?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Mocha',
                'category' => 'Hot Drinks',
                'cost' => 1.50,
                'price' => 4.50,
                'image' => 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Macchiato',
                'category' => 'Hot Drinks',
                'cost' => 1.10,
                'price' => 3.75,
                'image' => 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Flat White',
                'category' => 'Hot Drinks',
                'cost' => 1.30,
                'price' => 4.25,
                'image' => 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Iced Coffee',
                'category' => 'Cold Drinks',
                'cost' => 0.80,
                'price' => 3.25,
                'image' => 'https://images.unsplash.com/photo-1517701550927-30cf0b15b399?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Iced Latte',
                'category' => 'Cold Drinks',
                'cost' => 1.40,
                'price' => 4.25,
                'image' => 'https://images.unsplash.com/photo-1559525839-b184a4d698c7?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Cold Brew',
                'category' => 'Cold Drinks',
                'cost' => 1.20,
                'price' => 4.50,
                'image' => 'https://images.unsplash.com/photo-1461023058943-07cb14ea20e9?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Blueberry Muffin',
                'category' => 'Pastries',
                'cost' => 1.00,
                'price' => 3.50,
                'image' => 'https://images.unsplash.com/photo-1607958996333-41aef7caefaa?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Chocolate Chip Cookie',
                'category' => 'Pastries',
                'cost' => 0.50,
                'price' => 2.00,
                'image' => 'https://images.unsplash.com/photo-1499636136210-6f4ee915583e?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Croissant',
                'category' => 'Pastries',
                'cost' => 0.80,
                'price' => 3.00,
                'image' => 'https://images.unsplash.com/photo-1555507036-ab1f40ce88f9?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Cinnamon Roll',
                'category' => 'Pastries',
                'cost' => 1.20,
                'price' => 4.00,
                'image' => 'https://images.unsplash.com/photo-1509365465985-25d11c17e812?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Bagel with Cream Cheese',
                'category' => 'Food',
                'cost' => 1.50,
                'price' => 4.50,
                'image' => 'https://images.unsplash.com/photo-1588195538326-c5b1e9f80a1b?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Turkey Sandwich',
                'category' => 'Food',
                'cost' => 2.50,
                'price' => 6.50,
                'image' => 'https://images.unsplash.com/photo-1619860860774-1e2e17343432?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Veggie Wrap',
                'category' => 'Food',
                'cost' => 2.00,
                'price' => 6.00,
                'image' => 'https://images.unsplash.com/photo-1628840042765-356cda07504e?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Caesar Salad',
                'category' => 'Food',
                'cost' => 3.00,
                'price' => 7.50,
                'image' => 'https://images.unsplash.com/photo-1550304943-4f24f54ddde9?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Orange Juice',
                'category' => 'Cold Drinks',
                'cost' => 1.00,
                'price' => 3.50,
                'image' => 'https://images.unsplash.com/photo-1621506289937-a8e4df240d0b?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ],
            [
                'name' => 'Bottled Water',
                'category' => 'Others',
                'cost' => 0.30,
                'price' => 1.50,
                'image' => 'https://images.unsplash.com/photo-1548839140-29a749e1bc4e?ixlib=rb-1.2.1&auto=format&fit=crop&w=400&q=80',
            ]
        ];

        foreach ($products as $index => $item) {
            $sku = 'SKU-' . strtoupper(substr($item['name'], 0, 3)) . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);
            
            Product::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category_id' => $categories[$item['category']]->id,
                    'sku' => $sku,
                    'cost' => $item['cost'],
                    'price' => $item['price'],
                    'stock' => 100,
                    'minimum_stock' => 10,
                    'image' => $item['image'],
                    'status' => 1,
                ]
            );
        }
    }
}
