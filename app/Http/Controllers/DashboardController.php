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

        return view('dashboard', [
            'summaryCards' => [
                [
                    'label' => 'Paid Sales',
                    'value' => $this->reports->formatMoney($totalSales),
                    'trend' => $paymentSummary->count().' paid transaction(s)',
                    'icon' => 'bi-graph-up-arrow',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Low Stock',
                    'value' => (string) count($lowStockProducts),
                    'trend' => 'Products below the 20 kg threshold',
                    'icon' => 'bi-exclamation-triangle',
                    'tone' => 'orange',
                ],
                [
                    'label' => 'Inventory Snapshot',
                    'value' => (string) count($inventorySnapshot),
                    'trend' => $zeroStockCount.' item(s) at zero stock',
                    'icon' => 'bi-box-seam',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Receiving Costs',
                    'value' => $this->reports->formatMoney($totalBatchCosts),
                    'trend' => 'Recent intake line total',
                    'icon' => 'bi-clipboard-data',
                    'tone' => 'slate',
                ],
            ],
            'dailySalesSummary' => $dailySalesSummary,
            'lowStockProducts' => $lowStockProducts,
            'inventorySnapshot' => $inventorySnapshot,
            'batchDetails' => $batchDetails,
            'recentTransactions' => $recentTransactions,
            'inventoryStatusMix' => $this->reports->inventoryStatusMix($inventorySnapshot),
        ]);
    }
}
