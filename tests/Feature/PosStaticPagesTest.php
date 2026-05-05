<?php

use App\Models\User;

test('dashboard static page renders the operational preview sections', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Action Center');
    $response->assertSee('Stock Alerts');
    $response->assertSee('Inventory Health');
    $response->assertSee('Recent Sales');
    $response->assertSee('Receiving Review');
    $response->assertDontSee('vw_');
    $response->assertDontSee('SQL views');
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
    $response->assertSee('Reports &amp; Analytics', false);
    $response->assertSee('Report Filters');
    $response->assertSee('Sales Trend');
    $response->assertSee('Top Selling Products');
    $response->assertSee('Sales by Category');
    $response->assertSee('Product Sales Summary');
    $response->assertSee('Sales Activity');
    $response->assertDontSee('Sales Transactions');
});

test('sales activity report renders its own module', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('reports.sales-activity'));

    $response->assertOk();
    $response->assertSee('Sales Activity Ledger');
    $response->assertSee('Recent sold items');
    $response->assertDontSee('vw_');
});
