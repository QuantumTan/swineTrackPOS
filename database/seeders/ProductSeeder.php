<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

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
    }
}
