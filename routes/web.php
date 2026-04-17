<?php

use App\Http\Controllers\PosController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PosController::class, 'dashboard'])->name('dashboard');
    Route::get('/sales', [PosController::class, 'sales'])->name('sales.index');
    Route::get('/stock-ins', [PosController::class, 'stockIns'])->name('stock-ins.index');
    Route::get('/products', [PosController::class, 'products'])->name('products.index');
    Route::get('/suppliers', [PosController::class, 'suppliers'])->name('suppliers.index');
    Route::get('/inventory', [PosController::class, 'inventory'])->name('inventory.index');
    Route::get('/reports', [PosController::class, 'reports'])->name('reports.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
