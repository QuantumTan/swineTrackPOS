<?php

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

test('new stock-in records inventory without requiring a status field', function () {
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

    $response = $this
        ->actingAs($user)
        ->post(route('stock-ins.store'), [
            'batch_date' => now()->format('Y-m-d H:i:s'),
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
        ->assertSessionHas('status', 'Stock-in recorded successfully.');

    $batch = Batch::query()->latest('batch_id')->first();
    $inventory = Inventory::query()
        ->where('product_id', $product->product_id)
        ->first();

    expect($batch)->not->toBeNull()
        ->and($batch->source_type)->toBe('Supplier')
        ->and($batch->batch_status)->toBe('Open')
        ->and($inventory)->not->toBeNull()
        ->and((float) $inventory->current_stock_kg)->toBe(4.25);
});

test('stock-in page does not show a manual status field or status column', function () {
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
    $response->assertDontSee('name="batch_status"', false);
    $response->assertDontSee('<th>Status</th>', false);
});
