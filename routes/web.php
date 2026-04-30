<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesActivityReportController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return auth::check() 
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {

    Route::controller(PosController::class)->group(function () {
        Route::get('/sales', 'sales')->name('sales.index');
        Route::post('/sales', 'storeSale')->name('sales.store');
        Route::get('/inventory', 'inventory')->name('inventory.index');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales-activity', [SalesActivityReportController::class, 'index'])->name('reports.sales-activity');

    Route::controller(ProductController::class)->prefix('products')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{product}', 'update')->name('update');
        Route::delete('/{product}', 'destroy')->name('destroy');
    });

    Route::controller(CategoryController::class)->prefix('categories')->name('categories.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{category}', 'update')->name('update');
        Route::delete('/{category}', 'destroy')->name('destroy');
    });

    Route::controller(StockInController::class)->prefix('stock-ins')->name('stock-ins.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{batch}', 'update')->name('update');
        Route::delete('/{batch}', 'destroy')->name('destroy');
    });

    Route::controller(SupplierController::class)->prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{supplier}', 'update')->name('update');
        Route::delete('/{supplier}', 'destroy')->name('destroy');
    });

    Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::patch('/', 'update')->name('update');
        Route::delete('/', 'destroy')->name('destroy');
    });


});

require __DIR__ . '/auth.php';
