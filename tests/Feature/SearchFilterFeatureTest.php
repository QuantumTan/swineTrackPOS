<?php

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

test('products page supports search and category filters', function () {
    $user = User::factory()->create();
    $premium = Category::query()->create(['category_name' => 'Premium Cuts']);
    $standard = Category::query()->create(['category_name' => 'Standard Cuts']);

    Product::query()->create([
        'category_id' => $premium->category_id,
        'product_name' => 'Pork Belly',
        'product_price_per_kilo' => 320,
    ]);
    Product::query()->create([
        'category_id' => $standard->category_id,
        'product_name' => 'Pork Kasim',
        'product_price_per_kilo' => 280,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('products.index', [
            'search' => 'Belly',
            'category_id' => $premium->category_id,
        ]));

    $response->assertOk();
    $response->assertSee('Pork Belly');
    $response->assertDontSee('Pork Kasim');
});

test('categories page supports usage filter', function () {
    $user = User::factory()->create();
    $withProducts = Category::query()->create(['category_name' => 'With Products']);
    $empty = Category::query()->create(['category_name' => 'Empty Category']);

    Product::query()->create([
        'category_id' => $withProducts->category_id,
        'product_name' => 'Ground Pork',
        'product_price_per_kilo' => 240,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('categories.index', ['usage' => 'empty']));

    $response->assertOk();
    $response->assertSee($empty->category_name);
    $response->assertDontSee($withProducts->category_name);
});

test('suppliers page supports status and delivery filters', function () {
    $user = User::factory()->create();
    $active = Supplier::query()->create([
        'supplier_name' => 'Active Supplier',
        'status' => 'Active',
    ]);
    $inactive = Supplier::query()->create([
        'supplier_name' => 'Inactive Supplier',
        'status' => 'Inactive',
    ]);

    Batch::query()->create([
        'supplier_id' => $inactive->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Supplier',
        'batch_status' => 'Closed',
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('suppliers.index', [
            'status' => 'Inactive',
            'delivery' => 'with_history',
        ]));

    $response->assertOk();
    $response->assertSee('Inactive Supplier');
    $response->assertDontSee('Active Supplier');
});

test('inventory page supports stock level filter', function () {
    $user = User::factory()->create();
    $category = Category::query()->create(['category_name' => 'Premium Cuts']);

    $lowStockProduct = Product::query()->create([
        'category_id' => $category->category_id,
        'product_name' => 'Pork Loin',
        'product_price_per_kilo' => 330,
    ]);
    $inStockProduct = Product::query()->create([
        'category_id' => $category->category_id,
        'product_name' => 'Pork Ribs',
        'product_price_per_kilo' => 300,
    ]);

    $lowStockProduct->inventory()->update(['current_stock' => 5.000]);
    $inStockProduct->inventory()->update(['current_stock' => Product::LOW_STOCK_THRESHOLD + 1]);

    $response = $this
        ->actingAs($user)
        ->get(route('inventory.index', ['stock_status' => 'low_stock']));

    $response->assertOk();
    $response->assertSee('Pork Loin');
    $response->assertDontSee('Pork Ribs');
});

test('stock-in page supports effective status filters', function () {
    $user = User::factory()->create();
    $supplier = Supplier::query()->create([
        'supplier_name' => 'Central Farm Supply',
        'status' => 'Active',
    ]);
    $category = Category::query()->create(['category_name' => 'Standard Cuts']);
    $product = Product::query()->create([
        'category_id' => $category->category_id,
        'product_name' => 'Pork Chop',
        'product_price_per_kilo' => 305,
    ]);

    $openBatch = Batch::query()->create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subHours(2),
        'source_type' => 'Supplier',
        'batch_status' => 'Open',
    ]);
    $soldOutBatch = Batch::query()->create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now()->subHour(),
        'source_type' => 'Supplier',
        'batch_status' => 'Open',
    ]);
    $closedBatch = Batch::query()->create([
        'supplier_id' => $supplier->supplier_id,
        'user_id' => $user->user_id,
        'batch_date' => now(),
        'source_type' => 'Supplier',
        'batch_status' => 'Closed',
    ]);

    BatchItem::query()->create([
        'batch_id' => $openBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 2.500,
        'cost_per_kg' => 210,
    ]);
    BatchItem::query()->create([
        'batch_id' => $soldOutBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 0,
        'cost_per_kg' => 210,
    ]);
    BatchItem::query()->create([
        'batch_id' => $closedBatch->batch_id,
        'product_id' => $product->product_id,
        'qty_in_kg' => 2.000,
        'cost_per_kg' => 210,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('stock-ins.index', ['batch_status' => 'sold_out']));

    $response->assertOk();
    $response->assertSee($soldOutBatch->display_id);
    $response->assertDontSee($openBatch->display_id);
    $response->assertDontSee($closedBatch->display_id);
});
