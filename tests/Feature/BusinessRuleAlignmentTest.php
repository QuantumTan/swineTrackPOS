<?php

use App\Http\Requests\StockIn\StoreStockInRequest;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

test('supplier stock-in validation requires a supplier id', function () {
    $product = Product::create([
        'product_name' => 'Pork Ham',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 300.00,
    ]);

    $request = StoreStockInRequest::create('/stock-ins', 'POST', [
        'batch_date' => now()->format('Y-m-d H:i:s'),
        'source_type' => 'Supplier',
        'items' => [
            [
                'product_id' => $product->product_id,
                'qty_in_kg' => 3.500,
                'cost_per_kg' => 210.00,
            ],
        ],
    ]);

    $validator = Validator::make($request->all(), $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('supplier_id'))->toBeTrue();
});

test('sales domain models expose the documented relationships', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'Central Farm Supply',
        'supplier_phone_number' => '09171234567',
    ]);
    $product = Product::create([
        'product_name' => 'Pork Belly',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 320.00,
    ]);

    $batch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Supplier',
        'batch_status' => 'Sold Out',
    ]);

    $sale = Sale::create([
        'batch_id' => $batch->batch_id,
        'user_id' => $user->user_id,
        'sale_date' => now(),
    ]);

    $saleItem = SaleItem::create([
        'sale_id' => $sale->sale_id,
        'product_id' => $product->product_id,
        'qty_sold_kg' => 1.500,
        'price_per_kg' => 320.00,
    ]);

    expect($supplier->batches()->count())->toBe(1)
        ->and($user->batches()->count())->toBe(1)
        ->and($user->sales()->count())->toBe(1)
        ->and($batch->sales()->count())->toBe(1)
        ->and($sale->items()->count())->toBe(1)
        ->and($product->saleItems()->count())->toBe(1)
        ->and($sale->batch->is($batch))->toBeTrue()
        ->and($sale->user->is($user))->toBeTrue()
        ->and($saleItem->sale->is($sale))->toBeTrue()
        ->and($saleItem->product->is($product))->toBeTrue();
});
