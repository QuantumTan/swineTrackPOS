<?php

use App\Models\User;

test('dashboard static page renders the view-based preview sections', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Sales Trend');
    $response->assertSee('Low Stock Levels');
    $response->assertSee('Receiving Cost Review');
});

test('sales static page renders the pos workstation preview', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('sales.index'));

    $response->assertOk();
    $response->assertSee('SALES MODE');
    $response->assertSee('Shopping Cart');
    $response->assertSee('Cash Received');
    $response->assertSee('Number Keys');
    $response->assertSee('Complete Sale');
});

test('reports static page renders the reporting preview sections', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('reports.index'));

    $response->assertOk();
    $response->assertSee('Sales Trend');
    $response->assertSee('Low Stock Watchlist');
    $response->assertSee('Batch Cost Review');
});
