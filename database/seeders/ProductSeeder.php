<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'category_name' => 'Premium Cuts',
                'category_description' => 'Higher-value pork cuts sold by kilogram.',
            ],
            [
                'category_name' => 'Ground Meat',
                'category_description' => 'Ground pork products sold by kilogram.',
            ],
            [
                'category_name' => 'Standard Cuts',
                'category_description' => 'Everyday pork cuts sold by kilogram.',
            ],
            [
                'category_name' => 'Offal',
                'category_description' => 'Organ meats and related products sold by kilogram.',
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->firstOrCreate(
                ['category_name' => $category['category_name']],
                ['category_description' => $category['category_description']]
            );
        }

        // Add sample products
        $categoryIds = DB::table('category')->pluck('category_id', 'category_name')->toArray();

        $products = [
            [
                'product_name' => 'Pork Belly (Liempo)',
                'category_id' => $categoryIds['Premium Cuts'] ?? null,
                'product_price_per_kilo' => 320.00,
            ],
            [
                'product_name' => 'Pork Chop',
                'category_id' => $categoryIds['Premium Cuts'] ?? null,
                'product_price_per_kilo' => 280.00,
            ],
            [
                'product_name' => 'Pork Tenderloin',
                'category_id' => $categoryIds['Premium Cuts'] ?? null,
                'product_price_per_kilo' => 350.00,
            ],
            [
                'product_name' => 'Ground Pork',
                'category_id' => $categoryIds['Ground Meat'] ?? null,
                'product_price_per_kilo' => 240.00,
            ],
            [
                'product_name' => 'Pork Ribs',
                'category_id' => $categoryIds['Standard Cuts'] ?? null,
                'product_price_per_kilo' => 260.00,
            ],
            [
                'product_name' => 'Pork Shoulder',
                'category_id' => $categoryIds['Standard Cuts'] ?? null,
                'product_price_per_kilo' => 200.00,
            ],
            [
                'product_name' => 'Pork Liver',
                'category_id' => $categoryIds['Offal'] ?? null,
                'product_price_per_kilo' => 120.00,
            ],
            [
                'product_name' => 'Pork Kidney',
                'category_id' => $categoryIds['Offal'] ?? null,
                'product_price_per_kilo' => 100.00,
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['product_name' => $product['product_name']],
                [
                    'category_id' => $product['category_id'],
                    'product_price_per_kilo' => $product['product_price_per_kilo'],
                ]
            );
        }
    }
}
