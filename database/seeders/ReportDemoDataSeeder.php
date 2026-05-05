<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ReportDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $userId = DB::table('user')->where('user_email', 'staff@swinetrack.com')->value('user_id');

        if (! $userId) {
            $userId = DB::table('user')->insertGetId([
                'user_email' => 'staff@swinetrack.com',
                'user_password_hash' => Hash::make('password123'),
            ], 'user_id');
        }

        $categories = [
            'Premium Cuts' => 'Higher-value pork cuts sold by kilogram.',
            'Ground Meat' => 'Ground pork products sold by kilogram.',
            'Standard Cuts' => 'Everyday pork cuts sold by kilogram.',
        ];

        foreach ($categories as $name => $description) {
            DB::table('category')->updateOrInsert(
                ['category_name' => $name],
                ['category_description' => $description]
            );
        }

        $categoryIds = DB::table('category')->pluck('category_id', 'category_name');
        $products = [
            ['name' => 'Pork Belly (Liempo)', 'category' => 'Premium Cuts', 'price' => 320, 'stock' => 18.000],
            ['name' => 'Pork Chop', 'category' => 'Premium Cuts', 'price' => 280, 'stock' => 24.000],
            ['name' => 'Ground Pork', 'category' => 'Ground Meat', 'price' => 240, 'stock' => 9.000],
            ['name' => 'Pork Ribs', 'category' => 'Standard Cuts', 'price' => 260, 'stock' => 7.500],
        ];

        foreach ($products as $product) {
            DB::table('product')->updateOrInsert(
                ['product_name' => $product['name']],
                [
                    'category_id' => $categoryIds[$product['category']],
                    'product_price_per_kilo' => $product['price'],
                ]
            );

            $productId = DB::table('product')->where('product_name', $product['name'])->value('product_id');

            DB::table('inventory')->updateOrInsert(
                ['product_id' => $productId],
                [
                    'current_stock_kg' => $product['stock'],
                    'last_updated_at' => '2026-04-30 08:00:00',
                ]
            );
        }

        $supplierId = DB::table('supplier')->where('supplier_name', 'Metro Cuts Trading')->value('supplier_id');

        if (! $supplierId) {
            $supplierId = DB::table('supplier')->insertGetId([
                'supplier_name' => 'Metro Cuts Trading',
                'contact_person_first_name' => 'Ana',
                'contact_person_last_name' => 'Ramos',
                'contact_number' => '0917 555 0188',
                'status' => 'Active',
                'email_address' => 'orders@metrocuts.test',
                'business_address' => 'Cabanatuan City, Nueva Ecija',
            ], 'supplier_id');
        }

        $batchId = DB::table('batch')
            ->where('batch_date', '2026-04-13 06:00:00')
            ->where('user_id', $userId)
            ->value('batch_id');

        if (! $batchId) {
            $batchId = DB::table('batch')->insertGetId([
                'supplier_id' => $supplierId,
                'user_id' => $userId,
                'batch_date' => '2026-04-13 06:00:00',
                'source_type' => 'Supplier',
                'batch_status' => 'Open',
            ], 'batch_id');
        }

        $batchItems = [
            ['product' => 'Pork Belly (Liempo)', 'qty' => 35.000, 'cost' => 205],
            ['product' => 'Pork Chop', 'qty' => 28.000, 'cost' => 190],
            ['product' => 'Ground Pork', 'qty' => 20.000, 'cost' => 175],
            ['product' => 'Pork Ribs', 'qty' => 18.000, 'cost' => 180],
        ];

        foreach ($batchItems as $item) {
            $productId = DB::table('product')->where('product_name', $item['product'])->value('product_id');

            DB::table('batch_item')->updateOrInsert(
                ['batch_id' => $batchId, 'product_id' => $productId],
                ['qty_in_kg' => $item['qty'], 'cost_per_kg' => $item['cost']]
            );
        }

        $sales = [
            [
                'date' => '2026-04-13 09:15:00',
                'items' => [
                    ['product' => 'Pork Chop', 'qty' => 2.000, 'price' => 280],
                    ['product' => 'Ground Pork', 'qty' => 1.000, 'price' => 240],
                ],
            ],
            [
                'date' => '2026-04-13 10:30:00',
                'items' => [
                    ['product' => 'Pork Belly (Liempo)', 'qty' => 3.000, 'price' => 320],
                ],
            ],
            [
                'date' => '2026-04-14 11:10:00',
                'items' => [
                    ['product' => 'Pork Ribs', 'qty' => 2.500, 'price' => 260],
                    ['product' => 'Ground Pork', 'qty' => 1.500, 'price' => 240],
                ],
            ],
        ];

        foreach ($sales as $sale) {
            if (DB::table('sale')->where('sale_date', $sale['date'])->where('user_id', $userId)->exists()) {
                continue;
            }

            $saleId = DB::table('sale')->insertGetId([
                'batch_id' => $batchId,
                'user_id' => $userId,
                'sale_date' => $sale['date'],
            ], 'sale_id');

            $amount = 0;

            foreach ($sale['items'] as $item) {
                $productId = DB::table('product')->where('product_name', $item['product'])->value('product_id');
                $amount += $item['qty'] * $item['price'];

                DB::table('sale_item')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $productId,
                    'qty_sold_kg' => $item['qty'],
                    'price_per_kg' => $item['price'],
                ]);
            }

            DB::table('payment')->insert([
                'sale_id' => $saleId,
                'amount' => $amount,
                'payment_status' => 'paid',
                'payment_date' => $sale['date'],
            ]);
        }
    }
}
