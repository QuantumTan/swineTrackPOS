<?php

namespace App\Http\Controllers;

use App\Models\BatchItem;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PosController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 20;

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

    public function inventory(): View
    {
        $latestSupplierSubquery = BatchItem::query()
            ->join('batches', 'batches.batch_id', '=', 'batch_item.batch_id')
            ->leftJoin('supplier', 'supplier.supplier_id', '=', 'batches.supplier_id')
            ->whereColumn('batch_item.product_id', 'product.product_id')
            ->orderByDesc('batches.batch_date')
            ->orderByDesc('batches.batch_id')
            ->selectRaw("
                CASE
                    WHEN batches.source_type = 'Own Livestock' THEN 'Own Livestock'
                    ELSE COALESCE(supplier.supplier_name, 'N/A')
                END
            ")
            ->limit(1);

        $inventoryRows = Product::query()
            ->leftJoin('inventory', 'inventory.product_id', '=', 'product.product_id')
            ->select('product.*', 'inventory.current_stock_kg', 'inventory.last_updated_at')
            ->selectSub($latestSupplierSubquery, 'latest_supplier')
            ->orderBy('product.product_id')
            ->paginate(10)
            ->withQueryString();

        $inventoryRows->through(function (Product $product) {
                $stock = (float) ($product->current_stock_kg ?? 0);

                return [
                    'id' => 'P'.str_pad((string) $product->product_id, 3, '0', STR_PAD_LEFT),
                    'name' => $product->product_name,
                    'category' => $product->product_category,
                    'stock' => number_format($stock, 3).' kg',
                    'status' => [
                        'label' => $stock <= 0 ? 'Out of Stock' : ($stock < self::LOW_STOCK_THRESHOLD ? 'Low Stock' : 'In Stock'),
                        'class' => $stock <= 0 ? 'danger' : ($stock < self::LOW_STOCK_THRESHOLD ? 'warning' : 'success'),
                    ],
                    'updated' => $product->last_updated_at
                        ? Carbon::parse($product->last_updated_at)->format('d M Y, h:i A')
                        : '-',
                    'latest_supplier' => $product->latest_supplier ?: '-',
                ];
            });

        $allInventoryRows = Product::query()
            ->leftJoin('inventory', 'inventory.product_id', '=', 'product.product_id')
            ->select('product.product_id', 'inventory.current_stock_kg')
            ->get();

        return view('pos.inventory', [
            'inventoryItems' => $inventoryRows,
            'summary' => [
                [
                    'label' => 'Total Products',
                    'value' => $allInventoryRows->count(),
                ],
                [
                    'label' => 'In Stock',
                    'value' => $allInventoryRows->filter(fn ($row) => (float) ($row->current_stock_kg ?? 0) > 0)->count(),
                ],
                [
                    'label' => 'Low Stock / Out',
                    'value' => $allInventoryRows->filter(
                        fn ($row) => (float) ($row->current_stock_kg ?? 0) < self::LOW_STOCK_THRESHOLD
                    )->count(),
                ],
            ],
        ]);
    }

    public function reports(): View
    {
        return view('pos.reports');
    }
}
