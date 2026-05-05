<?php

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('stock-in validation rejects duplicate product lines', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'Central Farm Supply',
        'status' => 'Active',
    ]);

    $categoryId = DB::table('category')->insertGetId([
        'category_name' => 'Premium Cuts',
        'category_description' => null,
    ]);

    $product = Product::create([
        'category_id' => $categoryId,
        'product_name' => 'Pork Belly',
        'product_price_per_kilo' => 320.00,
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
                    'qty_in_kg' => 2.000,
                    'cost_per_kg' => 210.00,
                ],
                [
                    'product_id' => $product->product_id,
                    'qty_in_kg' => 1.000,
                    'cost_per_kg' => 215.00,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('stock-ins.index'))
        ->assertSessionHasErrors('items.0.product_id');

    $this->assertDatabaseCount('batch', 0);
    $this->assertDatabaseCount('batch_item', 0);
});

test('pos sale uses a batch with enough remaining quantity', function () {
    $user = User::factory()->create();

    $categoryId = DB::table('category')->insertGetId([
        'category_name' => 'Premium Cuts',
        'category_description' => null,
    ]);

    $product = Product::create([
        'category_id' => $categoryId,
        'product_name' => 'Pork Chop',
        'product_price_per_kilo' => 280.00,
    ]);

    $product->inventory()->update([
        'current_stock_kg' => 10.000,
        'last_updated_at' => now(),
    ]);

    $availableBatch = Batch::create([
        'supplier_id' => null,
        'user_id' => $user->user_id,
        'batch_date' => now()->subDay(),
        'source_type' => 'Own Livestock',
        'batch_status' => 'Open',
    ]);

    BatchItem::create([
        'batch_id' => $availableBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 10.000,
        'cost_per_kg' => 200.00,
    ]);

    $soldOutBatch = Batch::create([
        'supplier_id' => null,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Own Livestock',
        'batch_status' => 'Sold Out',
    ]);

    BatchItem::create([
        'batch_id' => $soldOutBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 0,
        'cost_per_kg' => 200.00,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('sales.store'), [
            'cash_received' => 560,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'qty_sold_kg' => 2.000,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('sales.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('sale', [
        'batch_id' => $availableBatch->batch_id,
    ]);

    expect((float) $availableBatch->items()->first()->qty_in_kg)->toBe(8.0)
        ->and((float) $soldOutBatch->items()->first()->qty_in_kg)->toBe(0.0)
        ->and((float) $product->inventory()->first()->current_stock_kg)->toBe(8.0);
});

test('supplier with stock-in history is not deleted', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'supplier_name' => 'History Farm Supply',
        'status' => 'Active',
    ]);

    Batch::create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Supplier',
        'batch_status' => 'Closed',
    ]);

    $response = $this
        ->from(route('suppliers.index'))
        ->actingAs($user)
        ->delete(route('suppliers.destroy', $supplier));

    $response
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHasErrors('supplier_delete');

    $this->assertDatabaseHas('supplier', [
        'supplier_id' => $supplier->supplier_id,
    ]);

    $this->assertDatabaseHas('batch', [
        'supplier_id' => $supplier->supplier_id,
    ]);
});
