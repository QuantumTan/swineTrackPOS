<?php

namespace App\Http\Controllers;

use App\Services\ReportDataService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ReportDataService $reports)
    {
    }

    public function index(): View
    {
        $dailySalesSummary = $this->reports->dailySalesSummary();
        $lowStockProducts = $this->reports->lowStockProducts(6);
        $inventorySnapshot = $this->reports->inventorySnapshot(8);
        $batchDetails = $this->reports->batchDetails(6);
        $paymentSummary = $this->reports->paymentSummary();
        $recentTransactions = $this->reports->salesTransactions('monthly')->take(5);

        $totalSales = $paymentSummary->sum(fn (array $row): float => $row['amount_value']);
        $totalBatchCosts = collect($batchDetails)->sum(fn (array $row): float => $row['line_total_cost_value']);
        $zeroStockCount = collect($inventorySnapshot)
            ->filter(fn (array $row): bool => (float) $row['current_stock_value'] <= 0)
            ->count();
        $lowStockCount = $this->reports->lowStockProductsCount();

        return view('dashboard', [
            'summaryCards' => [
                [
                    'label' => 'Paid Sales',
                    'value' => $this->reports->formatMoney($totalSales),
                    'icon' => 'bi-graph-up-arrow',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Low Stock',
                    'value' => (string) $lowStockCount,
                    'icon' => 'bi-exclamation-triangle',
                    'tone' => 'orange',
                ],
                [
                    'label' => 'Inventory Snapshot',
                    'value' => (string) count($inventorySnapshot),
                    'icon' => 'bi-box-seam',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Receiving Costs',
                    'value' => $this->reports->formatMoney($totalBatchCosts),
                    'icon' => 'bi-clipboard-data',
                    'tone' => 'slate',
                ],
            ],
            'dailySalesSummary' => $dailySalesSummary,
            'lowStockProducts' => $lowStockProducts,
            'inventorySnapshot' => $inventorySnapshot,
            'batchDetails' => $batchDetails,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
