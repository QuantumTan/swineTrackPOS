<?php

use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;

test('authenticated users can update a product', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'product_name' => 'Pork Belly',
        'product_category' => 'Premium Cuts',
        'product_price_per_kilo' => 320.00,
    ]);

    Inventory::create([
        'product_id' => $product->product_id,
        'current_stock_kg' => 12.500,
        'last_updated_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->put(route('products.update', $product), [
            'product_name' => 'Seasoned Pork Belly',
            'product_category' => 'Standard Cuts',
            'product_price_per_kilo' => 345.75,
        ]);

    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Product updated successfully.');

    $product->refresh();

    expect($product->product_name)->toBe('Seasoned Pork Belly')
        ->and($product->product_category)->toBe('Standard Cuts')
        ->and((float) $product->product_price_per_kilo)->toBe(345.75);
});

test('authenticated users can delete a product and its inventory record', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'product_name' => 'Pork Shoulder',
        'product_category' => 'Standard Cuts',
        'product_price_per_kilo' => 280.00,
    ]);

    Inventory::create([
        'product_id' => $product->product_id,
        'current_stock_kg' => 0,
        'last_updated_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->delete(route('products.destroy', $product));

    $response
        ->assertRedirect(route('products.index'))
        ->assertSessionHas('status', 'Product deleted successfully.');

    $this->assertDatabaseMissing('product', [
        'product_id' => $product->product_id,
    ]);

    $this->assertDatabaseMissing('inventory', [
        'product_id' => $product->product_id,
    ]);
});

test('products page wires edit and delete actions to product-specific modals', function () {
    $user = User::factory()->create();

    $product = Product::create([
        'product_name' => 'Ground Pork',
        'product_category' => 'Ground Meat',
        'product_price_per_kilo' => 230.00,
    ]);

    Inventory::create([
        'product_id' => $product->product_id,
        'current_stock_kg' => 8.750,
        'last_updated_at' => now(),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('data-bs-target="#productEdit'.$product->product_id.'"', false);
    $response->assertSee('data-bs-target="#productDelete'.$product->product_id.'"', false);
});
