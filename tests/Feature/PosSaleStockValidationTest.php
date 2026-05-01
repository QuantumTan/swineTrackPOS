<?php

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('pos sale cannot exceed current stock', function () {
    $user = User::factory()->create();

    $categoryId = DB::table('category')->insertGetId([
        'category_name' => 'Premium Cuts',
        'category_description' => null,
    ]);

    $product = Product::create([
        'category_id' => $categoryId,
        'product_name' => 'Perna Test',
        'product_price_per_kilo' => 280.00,
    ]);

    $product->inventory()->update([
        'current_stock_kg' => 20.000,
        'last_updated_at' => now(),
    ]);

    $batch = Batch::create([
        'supplier_id' => null,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Own Livestock',
        'batch_status' => 'Open',
    ]);

    BatchItem::create([
        'batch_id' => $batch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 20.000,
        'cost_per_kg' => 200.00,
    ]);

    $response = $this
        ->from(route('sales.index'))
        ->actingAs($user)
        ->post(route('sales.store'), [
            'cash_received' => 28000,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'qty_sold_kg' => 100.000,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('sales.index'))
        ->assertSessionHasErrors('items')
        ->assertSessionHas('error');

    $this->assertDatabaseCount('sale', 0);
    $this->assertDatabaseCount('sale_item', 0);
    $this->assertDatabaseCount('payment', 0);

    expect((float) $product->inventory()->first()->current_stock_kg)->toBe(20.0);
    expect((float) $batch->items()->first()->qty_in_kg)->toBe(20.0);
});

test('pos sale deducts current stock when successful', function () {
    $user = User::factory()->create();

    $categoryId = DB::table('category')->insertGetId([
        'category_name' => 'Premium Cuts',
        'category_description' => null,
    ]);

    $product = Product::create([
        'category_id' => $categoryId,
        'product_name' => 'Perna Test',
        'product_price_per_kilo' => 280.00,
    ]);

    $product->inventory()->update([
        'current_stock_kg' => 20.000,
        'last_updated_at' => now(),
    ]);

    $batch = Batch::create([
        'supplier_id' => null,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Own Livestock',
        'batch_status' => 'Open',
    ]);

    BatchItem::create([
        'batch_id' => $batch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 20.000,
        'cost_per_kg' => 200.00,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('sales.store'), [
            'cash_received' => 1400,
            'items' => [
                [
                    'product_id' => $product->product_id,
                    'qty_sold_kg' => 5.000,
                ],
            ],
        ]);

    $response
        ->assertRedirect(route('sales.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseCount('sale', 1);
    $this->assertDatabaseCount('sale_item', 1);
    $this->assertDatabaseCount('payment', 1);

    expect((float) $product->inventory()->first()->current_stock_kg)->toBe(15.0);
    expect((float) $batch->items()->first()->qty_in_kg)->toBe(15.0);
});
