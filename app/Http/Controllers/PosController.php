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
        $dailySalesSummary = $this->staticDailySalesSummary();
        $lowStockProducts = $this->staticLowStockProducts();
        $inventorySnapshot = $this->staticInventorySnapshot();
        $batchDetails = $this->staticBatchDetails();
        $triggerSummary = $this->staticTriggerSummary();

        return view('dashboard', [
            'summaryCards' => [
                [
                    'label' => 'Sales Summary',
                    'value' => 'P155,480.00',
                    'trend' => '4-day sales total',
                    'icon' => 'bi-graph-up-arrow',
                ],
                [
                    'label' => 'Low Stock',
                    'value' => '4',
                    'trend' => 'Products that need attention',
                    'icon' => 'bi-exclamation-triangle',
                ],
                [
                    'label' => 'Inventory Snapshot',
                    'value' => '4',
                    'trend' => 'Items shown in the stock preview',
                    'icon' => 'bi-box-seam',
                ],
                [
                    'label' => 'Starting Stock',
                    'value' => '0 kg',
                    'trend' => 'New products begin at zero stock',
                    'icon' => 'bi-lightning-charge',
                ],
            ],
            'viewTags' => [
                'vw_product_inventory',
                'vw_low_stock_products',
                'vw_batch_details',
                'vw_sales_details',
                'vw_daily_sales_summary',
            ],
            'dailySalesSummary' => $dailySalesSummary,
            'lowStockProducts' => $lowStockProducts,
            'inventorySnapshot' => $inventorySnapshot,
            'batchDetails' => $batchDetails,
            'triggerSummary' => $triggerSummary,
            'coverageItems' => $this->staticCoverageItems(),
            'salesGraph' => [
                [
                    'label' => 'Apr 17',
                    'total_sales' => 'P30,925.00',
                    'transactions' => 14,
                    'height' => 65,
                ],
                [
                    'label' => 'Apr 18',
                    'total_sales' => 'P34,480.00',
                    'transactions' => 16,
                    'height' => 73,
                ],
                [
                    'label' => 'Apr 19',
                    'total_sales' => 'P47,215.00',
                    'transactions' => 21,
                    'height' => 100,
                ],
                [
                    'label' => 'Apr 20',
                    'total_sales' => 'P42,860.00',
                    'transactions' => 18,
                    'height' => 91,
                ],
            ],
            'inventoryStatusGraph' => [
                [
                    'label' => 'In Stock',
                    'count' => 2,
                    'width' => 50,
                    'type' => 'success',
                ],
                [
                    'label' => 'Low Stock',
                    'count' => 1,
                    'width' => 25,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Out of Stock',
                    'count' => 1,
                    'width' => 25,
                    'type' => 'danger',
                ],
            ],
            'batchCostGraph' => [
                [
                    'label' => 'Pork Belly (Liempo)',
                    'value' => 'P3,690.00',
                    'width' => 79,
                    'type' => 'primary',
                ],
                [
                    'label' => 'Ground Pork',
                    'value' => 'P2,275.00',
                    'width' => 49,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Chop',
                    'value' => 'P4,656.00',
                    'width' => 100,
                    'type' => 'success',
                ],
            ],
            'lowStockGraph' => [
                [
                    'label' => 'Ground Pork',
                    'value' => '8.500 kg',
                    'width' => 87,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Ribs',
                    'value' => '4.250 kg',
                    'width' => 44,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Shoulder (Kasim)',
                    'value' => '0.000 kg',
                    'width' => 8,
                    'type' => 'danger',
                ],
                [
                    'label' => 'Pork Loin',
                    'value' => '9.750 kg',
                    'width' => 98,
                    'type' => 'warning',
                ],
            ],
        ]);
    }

    public function sales(): View
    {
        return view('pos.sales', [
            'terminalMeta' => [
                'title' => 'SALES MODE — Active Transaction',
                'subtitle' => 'Point of Sale Terminal',
                'time' => '10:43:37 PM',
                'date' => 'Apr 15, 2026',
            ],
            'catalogItems' => [
                [
                    'product_name' => 'Pork Belly (Liempo)',
                    'product_id' => 'P002',
                    'category' => 'Premium Cuts',
                    'price_per_kg' => 'P330.00',
                    'stock' => '32.00 kg',
                    'status' => ['label' => 'Ready', 'class' => 'success'],
                ],
                [
                    'product_name' => 'Pork Chop',
                    'product_id' => 'P001',
                    'category' => 'Premium Cuts',
                    'price_per_kg' => 'P280.00',
                    'stock' => '45.00 kg',
                    'status' => ['label' => 'Ready', 'class' => 'success'],
                ],
                [
                    'product_name' => 'Ground Pork',
                    'product_id' => 'P003',
                    'category' => 'Ground Meat',
                    'price_per_kg' => 'P240.00',
                    'stock' => '28.00 kg',
                    'status' => ['label' => 'Low Stock', 'class' => 'warning'],
                ],
                [
                    'product_name' => 'Pork Ribs',
                    'product_id' => 'P004',
                    'category' => 'Premium Cuts',
                    'price_per_kg' => 'P350.00',
                    'stock' => '15.00 kg',
                    'status' => ['label' => 'Ready', 'class' => 'success'],
                ],
                [
                    'product_name' => 'Pork Shoulder (Kasim)',
                    'product_id' => 'P005',
                    'category' => 'Standard Cuts',
                    'price_per_kg' => 'P260.00',
                    'stock' => '5.00 kg',
                    'status' => ['label' => 'Low Stock', 'class' => 'danger'],
                ],
                [
                    'product_name' => 'Pork Loin',
                    'product_id' => 'P006',
                    'category' => 'Premium Cuts',
                    'price_per_kg' => 'P300.00',
                    'stock' => '22.00 kg',
                    'status' => ['label' => 'Ready', 'class' => 'success'],
                ],
            ],
            'cartItems' => [
                [
                    'product_name' => 'Pork Belly (Liempo)',
                    'price_per_kg' => 'P320.00/kg',
                    'qty_display' => '0.5',
                    'line_total' => 'P160.00',
                ],
                [
                    'product_name' => 'Pork Chop',
                    'price_per_kg' => 'P280.00/kg',
                    'qty_display' => '0.5',
                    'line_total' => 'P140.00',
                ],
                [
                    'product_name' => 'Ground Pork',
                    'price_per_kg' => 'P240.00/kg',
                    'qty_display' => '0.5',
                    'line_total' => 'P120.00',
                ],
            ],
            'payment' => [
                'customer' => 'Walk-In Customer',
                'subtotal' => 'P420.00',
                'total' => 'P420.00',
                'cash_received' => '1000',
                'change' => 'P580.00',
            ],
            'cashShortcuts' => [
                'P100',
                'P200',
                'P500',
                'P1000',
            ],
            'numberKeys' => [
                '7', '8', '9',
                '4', '5', '6',
                '1', '2', '3',
                '00', '0', 'CLR',
            ],
        ]);
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
        $dailySalesSummary = $this->staticDailySalesSummary();
        $lowStockProducts = $this->staticLowStockProducts();
        $batchDetails = $this->staticBatchDetails();
        $salesDetails = $this->staticSalesDetails();
        $inventorySnapshot = $this->staticInventorySnapshot();

        return view('pos.reports', [
            'summaryCards' => [
                [
                    'label' => 'Sales Summary',
                    'value' => 'P155,480.00',
                    'trend' => 'Combined sales across the sample days',
                    'icon' => 'bi-file-earmark-bar-graph',
                ],
                [
                    'label' => 'Low Stock',
                    'value' => '4',
                    'trend' => 'Products currently at or below the warning level',
                    'icon' => 'bi-bell',
                ],
                [
                    'label' => 'Batch Costs',
                    'value' => 'P10,621.00',
                    'trend' => 'Total sample intake cost shown below',
                    'icon' => 'bi-clipboard-data',
                ],
                [
                    'label' => 'Starting Stock',
                    'value' => '0 kg',
                    'trend' => 'New products begin at zero stock',
                    'icon' => 'bi-lightning-charge',
                ],
            ],
            'viewTags' => [
                'vw_daily_sales_summary',
                'vw_low_stock_products',
                'vw_product_inventory',
                'vw_batch_details',
                'vw_sales_details',
            ],
            'dailySalesSummary' => $dailySalesSummary,
            'lowStockProducts' => $lowStockProducts,
            'batchDetails' => $batchDetails,
            'salesDetails' => $salesDetails,
            'inventorySnapshot' => $inventorySnapshot,
            'coverageItems' => $this->staticCoverageItems(),
            'triggerSummary' => $this->staticTriggerSummary(),
            'salesGraph' => [
                [
                    'label' => 'Apr 17',
                    'total_sales' => 'P30,925.00',
                    'transactions' => 14,
                    'height' => 65,
                ],
                [
                    'label' => 'Apr 18',
                    'total_sales' => 'P34,480.00',
                    'transactions' => 16,
                    'height' => 73,
                ],
                [
                    'label' => 'Apr 19',
                    'total_sales' => 'P47,215.00',
                    'transactions' => 21,
                    'height' => 100,
                ],
                [
                    'label' => 'Apr 20',
                    'total_sales' => 'P42,860.00',
                    'transactions' => 18,
                    'height' => 91,
                ],
            ],
            'lowStockGraph' => [
                [
                    'label' => 'Ground Pork',
                    'value' => '8.500 kg',
                    'width' => 87,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Ribs',
                    'value' => '4.250 kg',
                    'width' => 44,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Shoulder (Kasim)',
                    'value' => '0.000 kg',
                    'width' => 8,
                    'type' => 'danger',
                ],
                [
                    'label' => 'Pork Loin',
                    'value' => '9.750 kg',
                    'width' => 98,
                    'type' => 'warning',
                ],
            ],
            'inventoryGraph' => [
                [
                    'label' => 'Pork Chop',
                    'value' => '28.000 kg',
                    'width' => 100,
                    'type' => 'success',
                ],
                [
                    'label' => 'Pork Belly (Liempo)',
                    'value' => '16.500 kg',
                    'width' => 59,
                    'type' => 'success',
                ],
                [
                    'label' => 'Ground Pork',
                    'value' => '8.500 kg',
                    'width' => 30,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Shoulder (Kasim)',
                    'value' => '0.000 kg',
                    'width' => 6,
                    'type' => 'danger',
                ],
            ],
            'batchCostGraph' => [
                [
                    'label' => 'Pork Belly (Liempo)',
                    'value' => 'P3,690.00',
                    'width' => 79,
                    'type' => 'primary',
                ],
                [
                    'label' => 'Ground Pork',
                    'value' => 'P2,275.00',
                    'width' => 49,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Chop',
                    'value' => 'P4,656.00',
                    'width' => 100,
                    'type' => 'success',
                ],
            ],
            'salesMixGraph' => [
                [
                    'label' => 'Pork Belly (Liempo)',
                    'value' => 'P1,567.50',
                    'width' => 100,
                    'type' => 'primary',
                ],
                [
                    'label' => 'Ground Pork',
                    'value' => 'P446.25',
                    'width' => 29,
                    'type' => 'warning',
                ],
                [
                    'label' => 'Pork Chop',
                    'value' => 'P630.00',
                    'width' => 40,
                    'type' => 'success',
                ],
            ],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticCoverageItems(): array
    {
        return [
            [
                'kind' => 'View',
                'name' => 'vw_product_inventory',
                'description' => 'Inventory display for products, stock, price per kilo, update time, and computed stock status.',
                'detail' => 'product_id, product_name, product_category, product_price_per_kilo, current_stock_kg, last_updated_at, stock_status',
            ],
            [
                'kind' => 'View',
                'name' => 'vw_batch_details',
                'description' => 'Batch intake lines covering source, supplier, operator, quantities, and total cost.',
                'detail' => 'batch_id, batch_date, source_type, batch_status, supplier_name, user_email, batch_item_id, product_name, qty_in_kg, cost_per_kg, line_total_cost',
            ],
            [
                'kind' => 'View',
                'name' => 'vw_sales_details',
                'description' => 'Sale line ledger for cashier, batch, product, sold quantity, price, and line total.',
                'detail' => 'sale_id, sale_date, batch_id, user_email, sale_item_id, product_name, qty_sold_kg, price_per_kg, line_total',
            ],
            [
                'kind' => 'View',
                'name' => 'vw_daily_sales_summary',
                'description' => 'Day-level total transactions and total sales used for the sales trend graphs.',
                'detail' => 'sale_day, total_transactions, total_sales',
            ],
            [
                'kind' => 'View',
                'name' => 'vw_low_stock_products',
                'description' => 'Products at or below the low-stock threshold for watchlists and reorder prompts.',
                'detail' => 'product_id, product_name, current_stock_kg',
            ],
            [
                'kind' => 'Trigger',
                'name' => 'trg_product_after_insert',
                'description' => 'Creates the starting inventory row the moment a product is inserted.',
                'detail' => 'AFTER INSERT ON product -> inventory(product_id, current_stock_kg = 0, last_updated_at = NOW())',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticDailySalesSummary(): array
    {
        return [
            [
                'sale_day' => '20 Apr 2026',
                'total_transactions' => 18,
                'total_sales' => 'P42,860.00',
            ],
            [
                'sale_day' => '19 Apr 2026',
                'total_transactions' => 21,
                'total_sales' => 'P47,215.00',
            ],
            [
                'sale_day' => '18 Apr 2026',
                'total_transactions' => 16,
                'total_sales' => 'P34,480.00',
            ],
            [
                'sale_day' => '17 Apr 2026',
                'total_transactions' => 14,
                'total_sales' => 'P30,925.00',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticLowStockProducts(): array
    {
        return [
            [
                'product_id' => 'P003',
                'product_name' => 'Ground Pork',
                'current_stock_kg' => '8.500 kg',
                'status' => ['label' => 'Low Stock', 'class' => 'warning'],
            ],
            [
                'product_id' => 'P004',
                'product_name' => 'Pork Ribs',
                'current_stock_kg' => '4.250 kg',
                'status' => ['label' => 'Low Stock', 'class' => 'warning'],
            ],
            [
                'product_id' => 'P005',
                'product_name' => 'Pork Shoulder (Kasim)',
                'current_stock_kg' => '0.000 kg',
                'status' => ['label' => 'Out of Stock', 'class' => 'danger'],
            ],
            [
                'product_id' => 'P006',
                'product_name' => 'Pork Loin',
                'current_stock_kg' => '9.750 kg',
                'status' => ['label' => 'Low Stock', 'class' => 'warning'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticInventorySnapshot(): array
    {
        return [
            [
                'product_id' => 'P001',
                'product_name' => 'Pork Chop',
                'product_category' => 'Premium Cuts',
                'product_price_per_kilo' => 'P315.00',
                'current_stock_kg' => '28.000 kg',
                'last_updated_at' => '20 Apr 2026, 06:30 AM',
                'stock_status' => ['label' => 'In Stock', 'class' => 'success'],
            ],
            [
                'product_id' => 'P002',
                'product_name' => 'Pork Belly (Liempo)',
                'product_category' => 'Premium Cuts',
                'product_price_per_kilo' => 'P330.00',
                'current_stock_kg' => '16.500 kg',
                'last_updated_at' => '20 Apr 2026, 06:15 AM',
                'stock_status' => ['label' => 'In Stock', 'class' => 'success'],
            ],
            [
                'product_id' => 'P003',
                'product_name' => 'Ground Pork',
                'product_category' => 'Ground Meat',
                'product_price_per_kilo' => 'P255.00',
                'current_stock_kg' => '8.500 kg',
                'last_updated_at' => '20 Apr 2026, 05:55 AM',
                'stock_status' => ['label' => 'Low Stock', 'class' => 'warning'],
            ],
            [
                'product_id' => 'P005',
                'product_name' => 'Pork Shoulder (Kasim)',
                'product_category' => 'Standard Cuts',
                'product_price_per_kilo' => 'P285.00',
                'current_stock_kg' => '0.000 kg',
                'last_updated_at' => '20 Apr 2026, 05:40 AM',
                'stock_status' => ['label' => 'Out of Stock', 'class' => 'danger'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticBatchDetails(): array
    {
        return [
            [
                'batch_id' => 'B018',
                'batch_date' => '20 Apr 2026, 04:30 AM',
                'source_type' => 'Supplier',
                'batch_status' => ['label' => 'Open', 'class' => 'primary'],
                'supplier_name' => 'Metro Cuts Trading',
                'user_email' => 'cashier@swine-track.test',
                'batch_item_id' => 'BI053',
                'product_name' => 'Pork Belly (Liempo)',
                'qty_in_kg' => '18.000 kg',
                'cost_per_kg' => 'P205.00',
                'line_total_cost' => 'P3,690.00',
            ],
            [
                'batch_id' => 'B018',
                'batch_date' => '20 Apr 2026, 04:30 AM',
                'source_type' => 'Supplier',
                'batch_status' => ['label' => 'Open', 'class' => 'primary'],
                'supplier_name' => 'Metro Cuts Trading',
                'user_email' => 'cashier@swine-track.test',
                'batch_item_id' => 'BI054',
                'product_name' => 'Ground Pork',
                'qty_in_kg' => '12.500 kg',
                'cost_per_kg' => 'P182.00',
                'line_total_cost' => 'P2,275.00',
            ],
            [
                'batch_id' => 'B017',
                'batch_date' => '19 Apr 2026, 05:10 AM',
                'source_type' => 'Own Livestock',
                'batch_status' => ['label' => 'Closed', 'class' => 'neutral'],
                'supplier_name' => 'Own Livestock',
                'user_email' => 'admin@swine-track.test',
                'batch_item_id' => 'BI052',
                'product_name' => 'Pork Chop',
                'qty_in_kg' => '24.000 kg',
                'cost_per_kg' => 'P194.00',
                'line_total_cost' => 'P4,656.00',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staticSalesDetails(): array
    {
        return [
            [
                'sale_id' => 'S041',
                'sale_date' => '20 Apr 2026, 09:15 AM',
                'batch_id' => 'B018',
                'user_email' => 'cashier@swine-track.test',
                'sale_item_id' => 'SI118',
                'product_name' => 'Pork Belly (Liempo)',
                'qty_sold_kg' => '3.250 kg',
                'price_per_kg' => 'P330.00',
                'line_total' => 'P1,072.50',
            ],
            [
                'sale_id' => 'S042',
                'sale_date' => '20 Apr 2026, 09:42 AM',
                'batch_id' => 'B018',
                'user_email' => 'cashier@swine-track.test',
                'sale_item_id' => 'SI119',
                'product_name' => 'Ground Pork',
                'qty_sold_kg' => '1.750 kg',
                'price_per_kg' => 'P255.00',
                'line_total' => 'P446.25',
            ],
            [
                'sale_id' => 'S043',
                'sale_date' => '20 Apr 2026, 10:05 AM',
                'batch_id' => 'B017',
                'user_email' => 'cashier@swine-track.test',
                'sale_item_id' => 'SI120',
                'product_name' => 'Pork Chop',
                'qty_sold_kg' => '2.000 kg',
                'price_per_kg' => 'P315.00',
                'line_total' => 'P630.00',
            ],
            [
                'sale_id' => 'S044',
                'sale_date' => '20 Apr 2026, 10:22 AM',
                'batch_id' => 'B018',
                'user_email' => 'assistant@swine-track.test',
                'sale_item_id' => 'SI121',
                'product_name' => 'Pork Belly (Liempo)',
                'qty_sold_kg' => '1.500 kg',
                'price_per_kg' => 'P330.00',
                'line_total' => 'P495.00',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function staticTriggerSummary(): array
    {
        return [
            'name' => 'trg_product_after_insert',
            'title' => 'Inventory row auto-setup',
            'description' => 'Every new product immediately gets an inventory record with zero stock and the current timestamp.',
        ];
    }
}
