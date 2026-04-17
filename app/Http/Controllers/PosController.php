<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PosController extends Controller
{
    public function dashboard(): View
    {
        return view('dashboard');
    }

    public function sales(): View
    {
        return view('pos.sales');
    }

    public function stockIns(): View
    {
        return view('pos.stock-ins', [
            'stockIns' => [],
            'summary' => [],
            'stockInProducts' => [
                'Pork Chop',
                'Pork Belly (Liempo)',
                'Ground Pork',
                'Pork Ribs',
                'Pork Shoulder (Kasim)',
                'Pork Loin',
            ],
            'suppliers' => [],
        ]);
    }

    public function products(): View
    {
        return view('pos.products', [
            'products' => [],
            'categories' => [
                'Premium Cuts',
                'Ground Meat',
                'Standard Cuts',
                'Offal',
            ],
        ]);
    }

    public function suppliers(): View
    {
        return view('pos.suppliers', [
            'suppliers' => [],
        ]);
    }

    public function inventory(): View
    {
        return view('pos.inventory', [
            'inventoryItems' => [],
            'summary' => [],
        ]);
    }

    public function reports(): View
    {
        return view('pos.reports');
    }
}
