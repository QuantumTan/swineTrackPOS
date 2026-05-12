<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ReportDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get or create demo users
        $users = [];
        $userEmails = [
            'staff@swinetrack.com',
            'cashier1@swinetrack.local',
            'cashier2@swinetrack.local',
            'cashier3@swinetrack.local',
        ];

        foreach ($userEmails as $email) {
            $userId = DB::table('user')->where('user_email', $email)->value('user_id');
            if (!$userId) {
                $userId = DB::table('user')->insertGetId([
                    'user_email' => $email,
                    'user_password_hash' => Hash::make('password123'),
                ], 'user_id');
            }
            $users[$email] = $userId;
        }

        $primaryUserId = $users['staff@swinetrack.com'];

        // Create comprehensive product list across all categories
        $allProducts = [
            // Premium Cuts
            ['name' => 'Pork Belly (Liempo)', 'category' => 'Premium Cuts', 'price' => 320, 'stock' => 32.000],
            ['name' => 'Pork Chop', 'category' => 'Premium Cuts', 'price' => 280, 'stock' => 45.000],
            ['name' => 'Pork Loin', 'category' => 'Premium Cuts', 'price' => 350, 'stock' => 28.000],
            ['name' => 'Tenderloin', 'category' => 'Premium Cuts', 'price' => 420, 'stock' => 18.000],
            
            // Ground Meat
            ['name' => 'Ground Pork', 'category' => 'Ground Meat', 'price' => 240, 'stock' => 38.000],
            ['name' => 'Lean Ground Pork', 'category' => 'Ground Meat', 'price' => 260, 'stock' => 22.000],
            
            // Standard Cuts
            ['name' => 'Pork Ribs', 'category' => 'Standard Cuts', 'price' => 260, 'stock' => 26.000],
            ['name' => 'Pork Shoulder', 'category' => 'Standard Cuts', 'price' => 200, 'stock' => 40.000],
            ['name' => 'Pork Leg', 'category' => 'Standard Cuts', 'price' => 180, 'stock' => 50.000],
            ['name' => 'Pork Neck', 'category' => 'Standard Cuts', 'price' => 160, 'stock' => 20.000],
            
            // Offal
            ['name' => 'Pork Liver', 'category' => 'Offal', 'price' => 120, 'stock' => 12.000],
            ['name' => 'Pork Kidney', 'category' => 'Offal', 'price' => 140, 'stock' => 8.000],
            ['name' => 'Pork Intestines', 'category' => 'Offal', 'price' => 100, 'stock' => 15.000],
            ['name' => 'Pork Heart', 'category' => 'Offal', 'price' => 130, 'stock' => 6.000],
            
            // Processed Meat
            ['name' => 'Pork Sausage', 'category' => 'Processed Meat', 'price' => 180, 'stock' => 24.000],
            ['name' => 'Pork Bacon', 'category' => 'Processed Meat', 'price' => 380, 'stock' => 14.000],
            ['name' => 'Hotdog Meat', 'category' => 'Processed Meat', 'price' => 160, 'stock' => 16.000],
            
            // Specialty Items
            ['name' => 'Pork Head (Ulo)', 'category' => 'Specialty Items', 'price' => 150, 'stock' => 10.000],
            ['name' => 'Pork Trotters', 'category' => 'Specialty Items', 'price' => 160, 'stock' => 18.000],
            ['name' => 'Pork Tail', 'category' => 'Specialty Items', 'price' => 140, 'stock' => 8.000],
            
            // Cured Meat
            ['name' => 'Tocino (Cured Pork)', 'category' => 'Cured Meat', 'price' => 280, 'stock' => 12.000],
            ['name' => 'Longganisa', 'category' => 'Cured Meat', 'price' => 220, 'stock' => 10.000],
        ];

        $categoryIds = DB::table('category')->pluck('category_id', 'category_name');
        
        foreach ($allProducts as $product) {
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
                    'last_updated_at' => '2026-05-10 08:00:00',
                ]
            );
        }

        // Get supplier IDs
        $suppliers = DB::table('supplier')->pluck('supplier_id', 'supplier_name');

        // Create multiple batches from different suppliers with extended date range
        $batchConfigs = [
            [
                'supplier' => 'Metro Cuts Trading',
                'date' => '2026-04-13 06:00:00',
                'items' => [
                    ['product' => 'Pork Belly (Liempo)', 'qty' => 45.000, 'cost' => 205],
                    ['product' => 'Pork Chop', 'qty' => 50.000, 'cost' => 190],
                    ['product' => 'Ground Pork', 'qty' => 30.000, 'cost' => 175],
                    ['product' => 'Pork Ribs', 'qty' => 25.000, 'cost' => 180],
                ],
            ],
            [
                'supplier' => 'South Valley Hog Farm',
                'date' => '2026-04-20 07:30:00',
                'items' => [
                    ['product' => 'Pork Shoulder', 'qty' => 60.000, 'cost' => 150],
                    ['product' => 'Pork Leg', 'qty' => 55.000, 'cost' => 140],
                    ['product' => 'Ground Pork', 'qty' => 35.000, 'cost' => 170],
                    ['product' => 'Pork Liver', 'qty' => 15.000, 'cost' => 80],
                    ['product' => 'Pork Intestines', 'qty' => 18.000, 'cost' => 70],
                ],
            ],
            [
                'supplier' => 'Green Pastures Livestock',
                'date' => '2026-05-01 08:00:00',
                'items' => [
                    ['product' => 'Pork Loin', 'qty' => 30.000, 'cost' => 320],
                    ['product' => 'Tenderloin', 'qty' => 20.000, 'cost' => 380],
                    ['product' => 'Lean Ground Pork', 'qty' => 22.000, 'cost' => 210],
                    ['product' => 'Pork Sausage', 'qty' => 25.000, 'cost' => 140],
                ],
            ],
            [
                'supplier' => 'Prime Pork Processing',
                'date' => '2026-05-05 06:30:00',
                'items' => [
                    ['product' => 'Pork Bacon', 'qty' => 16.000, 'cost' => 320],
                    ['product' => 'Pork Sausage', 'qty' => 22.000, 'cost' => 145],
                    ['product' => 'Ground Pork', 'qty' => 26.000, 'cost' => 172],
                    ['product' => 'Hotdog Meat', 'qty' => 20.000, 'cost' => 125],
                ],
            ],
            [
                'supplier' => 'Sunrise Farms Cooperative',
                'date' => '2026-05-08 07:00:00',
                'items' => [
                    ['product' => 'Pork Shoulder', 'qty' => 40.000, 'cost' => 155],
                    ['product' => 'Pork Kidney', 'qty' => 10.000, 'cost' => 110],
                    ['product' => 'Pork Intestines', 'qty' => 15.000, 'cost' => 75],
                    ['product' => 'Pork Head (Ulo)', 'qty' => 12.000, 'cost' => 120],
                    ['product' => 'Pork Trotters', 'qty' => 20.000, 'cost' => 130],
                ],
            ],
            [
                'supplier' => 'Valley Fresh Meats',
                'date' => '2026-05-10 06:15:00',
                'items' => [
                    ['product' => 'Pork Neck', 'qty' => 25.000, 'cost' => 130],
                    ['product' => 'Tocino (Cured Pork)', 'qty' => 14.000, 'cost' => 240],
                    ['product' => 'Longganisa', 'qty' => 12.000, 'cost' => 180],
                    ['product' => 'Pork Heart', 'qty' => 8.000, 'cost' => 100],
                ],
            ],
            [
                'supplier' => 'Heritage Pork House',
                'date' => '2026-05-11 07:45:00',
                'items' => [
                    ['product' => 'Pork Chop', 'qty' => 35.000, 'cost' => 195],
                    ['product' => 'Pork Ribs', 'qty' => 28.000, 'cost' => 185],
                    ['product' => 'Pork Tail', 'qty' => 10.000, 'cost' => 110],
                ],
            ],
        ];

        $batches = [];
        foreach ($batchConfigs as $config) {
            if (!isset($suppliers[$config['supplier']])) {
                continue;
            }

            $supplierId = $suppliers[$config['supplier']];
            
            $existingBatch = DB::table('batch')
                ->where('batch_date', $config['date'])
                ->where('supplier_id', $supplierId)
                ->first();

            if ($existingBatch) {
                $batchId = $existingBatch->batch_id;
            } else {
                $batchId = DB::table('batch')->insertGetId([
                    'supplier_id' => $supplierId,
                    'user_id' => $primaryUserId,
                    'batch_date' => $config['date'],
                    'source_type' => 'Supplier',
                    'batch_status' => 'Closed',
                ], 'batch_id');
            }

            $batches[] = $batchId;

            foreach ($config['items'] as $item) {
                $productId = DB::table('product')->where('product_name', $item['product'])->value('product_id');

                if (!DB::table('batch_item')->where('batch_id', $batchId)->where('product_id', $productId)->exists()) {
                    DB::table('batch_item')->insert([
                        'batch_id' => $batchId,
                        'product_id' => $productId,
                        'qty_in_kg' => $item['qty'],
                        'cost_per_kg' => $item['cost'],
                    ]);
                }
            }
        }

        // Create extensive sales data across multiple days and cashiers
        $salesConfigs = [
            // April 13
            ['date' => '2026-04-13 09:15:00', 'user' => 'staff@swinetrack.com', 'batch_idx' => 0, 'items' => [
                ['product' => 'Pork Chop', 'qty' => 2.500, 'price' => 280],
                ['product' => 'Ground Pork', 'qty' => 1.200, 'price' => 240],
            ]],
            ['date' => '2026-04-13 10:30:00', 'user' => 'cashier1@swinetrack.local', 'batch_idx' => 0, 'items' => [
                ['product' => 'Pork Belly (Liempo)', 'qty' => 3.500, 'price' => 320],
                ['product' => 'Pork Ribs', 'qty' => 2.000, 'price' => 260],
            ]],
            ['date' => '2026-04-13 13:45:00', 'user' => 'cashier2@swinetrack.local', 'batch_idx' => 0, 'items' => [
                ['product' => 'Ground Pork', 'qty' => 1.500, 'price' => 240],
                ['product' => 'Pork Chop', 'qty' => 1.800, 'price' => 280],
            ]],
            ['date' => '2026-04-13 16:20:00', 'user' => 'cashier3@swinetrack.local', 'batch_idx' => 0, 'items' => [
                ['product' => 'Pork Ribs', 'qty' => 2.200, 'price' => 260],
            ]],
            
            // April 20
            ['date' => '2026-04-20 08:30:00', 'user' => 'cashier1@swinetrack.local', 'batch_idx' => 1, 'items' => [
                ['product' => 'Pork Shoulder', 'qty' => 5.500, 'price' => 200],
                ['product' => 'Pork Leg', 'qty' => 4.200, 'price' => 180],
            ]],
            ['date' => '2026-04-20 11:15:00', 'user' => 'staff@swinetrack.com', 'batch_idx' => 1, 'items' => [
                ['product' => 'Pork Liver', 'qty' => 2.500, 'price' => 120],
                ['product' => 'Ground Pork', 'qty' => 2.200, 'price' => 240],
            ]],
            ['date' => '2026-04-20 14:45:00', 'user' => 'cashier2@swinetrack.local', 'batch_idx' => 1, 'items' => [
                ['product' => 'Pork Intestines', 'qty' => 3.000, 'price' => 100],
                ['product' => 'Pork Shoulder', 'qty' => 3.500, 'price' => 200],
            ]],
            
            // May 1
            ['date' => '2026-05-01 09:00:00', 'user' => 'cashier3@swinetrack.local', 'batch_idx' => 2, 'items' => [
                ['product' => 'Pork Loin', 'qty' => 2.800, 'price' => 350],
                ['product' => 'Tenderloin', 'qty' => 1.500, 'price' => 420],
            ]],
            ['date' => '2026-05-01 12:30:00', 'user' => 'cashier1@swinetrack.local', 'batch_idx' => 2, 'items' => [
                ['product' => 'Lean Ground Pork', 'qty' => 3.200, 'price' => 260],
                ['product' => 'Pork Sausage', 'qty' => 2.500, 'price' => 180],
            ]],
            ['date' => '2026-05-01 15:10:00', 'user' => 'staff@swinetrack.com', 'batch_idx' => 2, 'items' => [
                ['product' => 'Pork Loin', 'qty' => 1.800, 'price' => 350],
                ['product' => 'Pork Sausage', 'qty' => 1.200, 'price' => 180],
            ]],
            
            // May 5
            ['date' => '2026-05-05 10:00:00', 'user' => 'cashier2@swinetrack.local', 'batch_idx' => 3, 'items' => [
                ['product' => 'Pork Bacon', 'qty' => 1.800, 'price' => 380],
                ['product' => 'Pork Sausage', 'qty' => 2.800, 'price' => 180],
            ]],
            ['date' => '2026-05-05 13:20:00', 'user' => 'cashier1@swinetrack.local', 'batch_idx' => 3, 'items' => [
                ['product' => 'Ground Pork', 'qty' => 2.200, 'price' => 240],
                ['product' => 'Hotdog Meat', 'qty' => 1.500, 'price' => 160],
            ]],
            ['date' => '2026-05-05 16:45:00', 'user' => 'cashier3@swinetrack.local', 'batch_idx' => 3, 'items' => [
                ['product' => 'Pork Bacon', 'qty' => 0.800, 'price' => 380],
                ['product' => 'Hotdog Meat', 'qty' => 2.000, 'price' => 160],
            ]],
            
            // May 8
            ['date' => '2026-05-08 08:45:00', 'user' => 'staff@swinetrack.com', 'batch_idx' => 4, 'items' => [
                ['product' => 'Pork Shoulder', 'qty' => 4.500, 'price' => 200],
                ['product' => 'Pork Kidney', 'qty' => 1.800, 'price' => 140],
                ['product' => 'Pork Head (Ulo)', 'qty' => 2.500, 'price' => 150],
            ]],
            ['date' => '2026-05-08 11:30:00', 'user' => 'cashier1@swinetrack.local', 'batch_idx' => 4, 'items' => [
                ['product' => 'Pork Intestines', 'qty' => 2.800, 'price' => 100],
                ['product' => 'Pork Trotters', 'qty' => 3.200, 'price' => 160],
            ]],
            ['date' => '2026-05-08 14:15:00', 'user' => 'cashier2@swinetrack.local', 'batch_idx' => 4, 'items' => [
                ['product' => 'Pork Shoulder', 'qty' => 3.200, 'price' => 200],
                ['product' => 'Pork Trotters', 'qty' => 2.500, 'price' => 160],
            ]],
            
            // May 10
            ['date' => '2026-05-10 09:30:00', 'user' => 'cashier3@swinetrack.local', 'batch_idx' => 5, 'items' => [
                ['product' => 'Tocino (Cured Pork)', 'qty' => 2.200, 'price' => 280],
                ['product' => 'Pork Neck', 'qty' => 3.000, 'price' => 160],
            ]],
            ['date' => '2026-05-10 12:45:00', 'user' => 'staff@swinetrack.com', 'batch_idx' => 5, 'items' => [
                ['product' => 'Longganisa', 'qty' => 1.500, 'price' => 220],
                ['product' => 'Tocino (Cured Pork)', 'qty' => 1.800, 'price' => 280],
            ]],
            ['date' => '2026-05-10 15:20:00', 'user' => 'cashier1@swinetrack.local', 'batch_idx' => 5, 'items' => [
                ['product' => 'Pork Heart', 'qty' => 1.500, 'price' => 130],
                ['product' => 'Pork Neck', 'qty' => 2.200, 'price' => 160],
            ]],
            
            // May 11
            ['date' => '2026-05-11 09:15:00', 'user' => 'cashier2@swinetrack.local', 'batch_idx' => 6, 'items' => [
                ['product' => 'Pork Chop', 'qty' => 3.500, 'price' => 280],
                ['product' => 'Pork Ribs', 'qty' => 2.500, 'price' => 260],
            ]],
            ['date' => '2026-05-11 11:50:00', 'user' => 'cashier3@swinetrack.local', 'batch_idx' => 6, 'items' => [
                ['product' => 'Pork Tail', 'qty' => 1.500, 'price' => 140],
                ['product' => 'Pork Chop', 'qty' => 2.200, 'price' => 280],
            ]],
            ['date' => '2026-05-11 14:30:00', 'user' => 'staff@swinetrack.com', 'batch_idx' => 6, 'items' => [
                ['product' => 'Pork Ribs', 'qty' => 2.000, 'price' => 260],
                ['product' => 'Pork Tail', 'qty' => 1.200, 'price' => 140],
            ]],
        ];

        foreach ($salesConfigs as $config) {
            if (!isset($batches[$config['batch_idx']]) || !isset($users[$config['user']])) {
                continue;
            }

            $batchId = $batches[$config['batch_idx']];
            $userId = $users[$config['user']];

            if (DB::table('sale')->where('sale_date', $config['date'])->where('user_id', $userId)->exists()) {
                continue;
            }

            $saleId = DB::table('sale')->insertGetId([
                'batch_id' => $batchId,
                'user_id' => $userId,
                'sale_date' => $config['date'],
            ], 'sale_id');

            $totalAmount = 0;

            foreach ($config['items'] as $item) {
                $productId = DB::table('product')->where('product_name', $item['product'])->value('product_id');
                $itemTotal = $item['qty'] * $item['price'];
                $totalAmount += $itemTotal;

                DB::table('sale_item')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $productId,
                    'qty_sold_kg' => $item['qty'],
                    'price_per_kg' => $item['price'],
                ]);
            }

            DB::table('payment')->insert([
                'sale_id' => $saleId,
                'amount' => $totalAmount,
                'payment_status' => 'paid',
                'payment_date' => $config['date'],
            ]);
        }
    }
}
