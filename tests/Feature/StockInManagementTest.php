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
        ->and((float) $inventory->current_stock_kg)->toBe(3.5);
});

test('cannot create a new stock-in while the first batch is not sold out', function () {
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

    Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subDay(),
        'source_type' => 'Supplier',
        'batch_status' => 'Open',
    ]);

    $response = $this
        ->from(route('stock-ins.index'))
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
        ->assertSessionHasErrors([
            'stock_in_create' => 'Cannot record a new stock-in until the first batch is marked Sold Out.',
        ]);

    expect(Batch::query()->count())->toBe(1)
        ->and(Inventory::query()->count())->toBe(1)
        ->and((float) Inventory::query()->first()->current_stock_kg)->toBe(0.0);
});

test('can create a new stock-in after the first batch is sold out', function () {
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

    Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subDay(),
        'source_type' => 'Supplier',
        'batch_status' => 'Sold Out',
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

    expect(Batch::query()->count())->toBe(2)
        ->and((float) Inventory::query()->first()->current_stock_kg)->toBe(3.5);
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

test('updating a stock-in record recalculates inventory by product delta', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'North Ridge Meats',
        'supplier_phone_number' => '09175557777',
    ]);
    $productA = Product::create([
        'product_name' => 'Pork Belly',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 320.00,
    ]);
    $productB = Product::create([
        'product_name' => 'Ground Pork',
        'product_category' => 'Ground Meat',
        'product_price_per_kilo' => 250.00,
    ]);

    $productA->inventory()->update([
        'current_stock_kg' => 10.000,
        'last_updated_at' => now(),
    ]);

    $batch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subDay(),
        'source_type' => 'Supplier',
        'batch_status' => 'Open',
    ]);

    BatchItem::create([
        'batch_id' => $batch->batch_id,
        'product_id' => $productA->product_id,
        'qty_in_kg' => 2.000,
        'cost_per_kg' => 210.00,
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('stock-ins.update', $batch), [
            'batch_date' => now()->format('Y-m-d H:i:s'),
            'source_type' => 'Supplier',
            'supplier_id' => $supplier->supplier_id,
            'items' => [
                [
                    'product_id' => $productA->product_id,
                    'qty_in_kg' => 5.500,
                    'cost_per_kg' => 215.00,
                ],
                [
                    'product_id' => $productB->product_id,
                    'qty_in_kg' => 1.250,
                    'cost_per_kg' => 205.00,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('stock-ins.index'))
        ->assertSessionHas('status', 'Stock-in updated successfully.');

    $productAInventory = Inventory::query()->where('product_id', $productA->product_id)->first();
    $productBInventory = Inventory::query()->where('product_id', $productB->product_id)->first();

    expect($productAInventory)->not->toBeNull()
        ->and((float) $productAInventory->current_stock_kg)->toBe(13.5)
        ->and($productBInventory)->not->toBeNull()
        ->and((float) $productBInventory->current_stock_kg)->toBe(1.25);
});

test('deleting a stock-in record removes its quantities from inventory', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'Metro Cuts Trading',
        'supplier_phone_number' => '09176668888',
    ]);
    $product = Product::create([
        'product_name' => 'Pork Shoulder',
        'product_category' => 'Standard Cuts',
        'product_price_per_kilo' => 280.00,
    ]);

    $product->inventory()->update([
        'current_stock_kg' => 6.000,
        'last_updated_at' => now(),
    ]);

    $batch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Supplier',
        'batch_status' => 'Open',
    ]);

    BatchItem::create([
        'batch_id' => $batch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 2.250,
        'cost_per_kg' => 190.00,
    ]);

    $response = $this
        ->actingAs($user)
        ->delete(route('stock-ins.destroy', $batch));

    $response
        ->assertRedirect(route('stock-ins.index'))
        ->assertSessionHas('status', 'Stock-in deleted successfully.');

    $inventory = Inventory::query()->where('product_id', $product->product_id)->first();

    expect($inventory)->not->toBeNull()
        ->and((float) $inventory->current_stock_kg)->toBe(3.75);

    $this->assertDatabaseMissing('batches', [
        'batch_id' => $batch->batch_id,
    ]);
});

test('supplier source stock-in requires a supplier link', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'product_name' => 'Pork Ham',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 300.00,
    ]);

    $response = $this
        ->from(route('stock-ins.index'))
        ->actingAs($user)
        ->post(route('stock-ins.store'), [
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

    $response
        ->assertRedirect(route('stock-ins.index'))
        ->assertSessionHasErrors('supplier_id');

    expect(Batch::query()->count())->toBe(0);
});
