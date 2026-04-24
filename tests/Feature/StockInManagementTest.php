<?php

use App\Enums\BatchStatus;
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
        ->and($batch->batch_status)->toBe(BatchStatus::Open)
        ->and($inventory)->not->toBeNull()
        ->and((float) $inventory->current_stock_kg)->toBe(3.5);
});

test('cannot create a new stock-in while another batch remains open', function () {
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
        'batch_date' => now()->subDays(2),
        'source_type' => 'Supplier',
        'batch_status' => 'Sold Out',
    ]);

    $openBatch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subDay(),
        'source_type' => 'Supplier',
        'batch_status' => 'Open',
    ]);

    BatchItem::create([
        'batch_id' => $openBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 3.500,
        'cost_per_kg' => 210.00,
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
            'stock_in_create' => 'Cannot record a new stock-in while another batch still has remaining quantity. It will become Sold Out automatically at zero, or you can mark it Closed.',
        ]);

    expect(Batch::query()->count())->toBe(2)
        ->and(Inventory::query()->count())->toBe(1)
        ->and((float) Inventory::query()->first()->current_stock_kg)->toBe(0.0);
});

test('can create a new stock-in after prior batches are terminal', function () {
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
        'batch_status' => 'Closed',
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

test('stock-in page shows batch status and exposes it on edit', function () {
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
    $response->assertSee('Batch Status');
    $response->assertSee('Sold Out');
    $response->assertSee('name="batch_status"', false);
    $response->assertDontSee('>Sold Out</option>', false);
    $response->assertSee('"Sold Out" is automatic when all batch quantities reach zero.', false);
});

test('updating a stock-in record can change its manual status to closed', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'West Farm Supply',
        'supplier_phone_number' => '09173334444',
    ]);
    $product = Product::create([
        'product_name' => 'Pork Kasim',
        'product_category' => 'Standard Cuts',
        'product_price_per_kilo' => 275.00,
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
        'product_id' => $product->product_id,
        'qty_in_kg' => 1.500,
        'cost_per_kg' => 190.00,
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('stock-ins.update', $batch), [
            'batch_status' => 'Closed',
            'batch_date' => now()->format('Y-m-d H:i:s'),
            'source_type' => 'Supplier',
            'supplier_id' => $supplier->supplier_id,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'qty_in_kg' => 1.500,
                    'cost_per_kg' => 190.00,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('stock-ins.index'))
        ->assertSessionHas('status', 'Stock-in updated successfully.');

    expect($batch->refresh()->batch_status)->toBe(BatchStatus::Closed);
});

test('cannot mark a batch open while another batch is already open', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'East Farm Supply',
        'supplier_phone_number' => '09174445555',
    ]);
    $product = Product::create([
        'product_name' => 'Pork Belly',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 320.00,
    ]);

    $openBatch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subDays(2),
        'source_type' => 'Supplier',
        'batch_status' => 'Open',
    ]);

    BatchItem::create([
        'batch_id' => $openBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 2.000,
        'cost_per_kg' => 200.00,
    ]);

    $closedBatch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subDay(),
        'source_type' => 'Supplier',
        'batch_status' => 'Closed',
    ]);

    BatchItem::create([
        'batch_id' => $closedBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 1.000,
        'cost_per_kg' => 210.00,
    ]);

    $response = $this
        ->from(route('stock-ins.index'))
        ->actingAs($user)
        ->put(route('stock-ins.update', $closedBatch), [
            'batch_status' => 'Open',
            'batch_date' => now()->format('Y-m-d H:i:s'),
            'source_type' => 'Supplier',
            'supplier_id' => $supplier->supplier_id,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'qty_in_kg' => 1.000,
                    'cost_per_kg' => 210.00,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('stock-ins.index'))
        ->assertSessionHasErrors([
            'batch_status' => 'Only one batch with remaining quantity can stay Open at a time.',
        ]);

    expect($closedBatch->refresh()->batch_status)->toBe(BatchStatus::Closed);
});

test('zero-quantity batches are shown as sold out automatically', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'Delta Farm Supply',
        'supplier_phone_number' => '09178889999',
    ]);
    $product = Product::create([
        'product_name' => 'Pork Loin',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 310.00,
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
        'qty_in_kg' => 0,
        'cost_per_kg' => 190.00,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('stock-ins.index'));

    $response->assertOk();
    $response->assertSee('Sold Out');
    $response->assertDontSee('>Open</span>', false);
});

test('sold-out batches no longer block creating a new stock-in', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'Harvest Farm Supply',
        'supplier_phone_number' => '09179990000',
    ]);
    $product = Product::create([
        'product_name' => 'Pork Chop',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 305.00,
    ]);

    $soldOutBatch = Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subDay(),
        'source_type' => 'Supplier',
        'batch_status' => 'Open',
    ]);

    BatchItem::create([
        'batch_id' => $soldOutBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 0,
        'cost_per_kg' => 190.00,
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
                    'qty_in_kg' => 2.250,
                    'cost_per_kg' => 205.00,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('stock-ins.index'))
        ->assertSessionHas('status', 'Stock-in recorded successfully.');

    expect(Batch::query()->count())->toBe(2)
        ->and(Batch::query()->latest('batch_id')->first()?->batch_status)->toBe(BatchStatus::Open);
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
            'batch_status' => 'Open',
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

test('new stock-in form only lists active suppliers', function () {
    $user = User::factory()->create();

    Supplier::create([
        'supplier_name' => 'Active Farm Supply',
        'supplier_status' => 'Active',
    ]);

    Supplier::create([
        'supplier_name' => 'Inactive Farm Supply',
        'supplier_status' => 'Inactive',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('stock-ins.index'));

    $response->assertOk();
    $response->assertSee('Active Farm Supply');
    $response->assertDontSee('Inactive Farm Supply');
});

test('cannot create stock-in with an inactive supplier', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'Inactive Farm Supply',
        'supplier_status' => 'Inactive',
    ]);
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
        ->assertSessionHasErrors('supplier_id');

    expect(Batch::query()->count())->toBe(0);
});
