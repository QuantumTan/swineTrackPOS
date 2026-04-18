<?php

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

test('new stock-in is blocked when the latest batch is not sold out', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'Central Farm Supply',
        'supplier_phone_number' => '09171234567',
    ]);
    $product = Product::create([
        'product_name' => 'Pork Ham',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 300.00,
    ]);

    $latestBatch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Supplier',
        'batch_status' => 'Open',
    ]);

    BatchItem::create([
        'batch_id' => $latestBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 5.000,
        'cost_per_kg' => 200.00,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('stock-ins.store'), [
            'batch_date' => now()->addHour()->format('Y-m-d H:i:s'),
            'source_type' => 'Supplier',
            'supplier_id' => $supplier->supplier_id,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'qty_in_kg' => 3.500,
                    'cost_per_kg' => 210.00,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('stock-ins.index'))
        ->assertSessionHasErrors([
            'stock_in_create' => 'You can only record a new stock-in after the latest batch is marked Sold Out.',
        ]);

    expect(Batch::query()->count())->toBe(1);
});

test('new stock-in starts as open when the latest batch is sold out', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'North Ridge Meats',
        'supplier_phone_number' => '09179876543',
    ]);
    $product = Product::create([
        'product_name' => 'Pork Belly',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 320.00,
    ]);

    $previousBatch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subDay(),
        'source_type' => 'Supplier',
        'batch_status' => 'Sold Out',
    ]);

    BatchItem::create([
        'batch_id' => $previousBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 6.000,
        'cost_per_kg' => 205.00,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('stock-ins.store'), [
            'batch_date' => now()->format('Y-m-d H:i:s'),
            'source_type' => 'Supplier',
            'supplier_id' => $supplier->supplier_id,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'qty_in_kg' => 4.250,
                    'cost_per_kg' => 215.50,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('stock-ins.index'))
        ->assertSessionHas('status', 'Stock-in recorded successfully.');

    $newBatch = Batch::query()
        ->where('batch_id', '!=', $previousBatch->batch_id)
        ->latest('batch_id')
        ->first();

    expect($newBatch)->not->toBeNull()
        ->and($newBatch->batch_status)->toBe('Open');

    $inventory = Inventory::query()
        ->where('product_id', $product->product_id)
        ->first();

    expect($inventory)->not->toBeNull()
        ->and((float) $inventory->current_stock_kg)->toBe(4.25);
});

test('stock-in page shows status guidance and a status column in the records table', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'South Valley Foods',
        'supplier_phone_number' => '09170000000',
    ]);
    $product = Product::create([
        'product_name' => 'Ground Pork',
        'product_category' => 'Ground Meat',
        'product_price_per_kilo' => 240.00,
    ]);

    $batch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Supplier',
        'batch_status' => 'Sold Out',
    ]);

    BatchItem::create([
        'batch_id' => $batch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 2.000,
        'cost_per_kg' => 190.00,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('stock-ins.index'));

    $response->assertOk();
    $response->assertSee('New stock-in records always start as Open.', false);
    $response->assertSee('<th>Status</th>', false);
    $response->assertSee('Sold Out');
});
