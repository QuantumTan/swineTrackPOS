<?php

namespace App\Http\Controllers;

use App\Services\ReportDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportDataService $reports)
    {
    }

    public function index(Request $request): View
    {
        $reportType = $request->string('type')->toString() ?: 'monthly';
        $reportType = in_array($reportType, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $reportType : 'monthly';
        $period = $this->reports->periodFor($reportType);
        $salesTrend = $this->reports->salesTrendForPeriod($reportType);
        $productSalesSummary = $this->reports->productSalesSummary($reportType);
        $categorySalesSummary = $this->reports->categorySalesSummary($reportType);

        $totalSales = collect($productSalesSummary)->sum(fn (array $row): float => $row['revenue_value']);
        $transactionCount = collect($salesTrend)->sum(fn (array $row): int => $row['transactions']);
        $totalSoldQuantity = collect($productSalesSummary)->sum(fn (array $row): float => $row['qty_sold_value']);
        $averageTransaction = $transactionCount > 0 ? $totalSales / $transactionCount : 0;

        return view('pos.reports', [
            'reportMeta' => [
                'title' => 'Reports & Analytics',
                'period' => $period['label'],
                'type' => $reportType,
                'generated_at' => now()->format('d M Y, h:i A'),
                'source' => 'Sales, product, and category activity',
            ],
            'summaryCards' => [
                [
                    'label' => 'Total Sales',
                    'value' => $this->reports->formatMoney($totalSales),
                    'trend' => $period['label'],
                    'icon' => 'bi-currency-dollar',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Transactions',
                    'value' => (string) $transactionCount,
                    'trend' => 'Completed sales',
                    'icon' => 'bi-graph-up-arrow',
                    'tone' => 'violet',
                ],
                [
                    'label' => 'Avg. Transaction',
                    'value' => $this->reports->formatMoney($averageTransaction),
                    'trend' => 'Sales average',
                    'icon' => 'bi-cash',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Total Qty Sold',
                    'value' => number_format($totalSoldQuantity, 2).' kg',
                    'trend' => 'Sold quantity',
                    'icon' => 'bi-box-seam',
                    'tone' => 'orange',
                ],
            ],
            'reportTypes' => [
                'daily' => 'Daily',
                'weekly' => 'Weekly',
                'monthly' => 'Monthly',
                'yearly' => 'Yearly',
            ],
            'salesTrend' => $this->reports->salesGraph($salesTrend),
            'topProductsGraph' => $this->reports->topProductsGraph($productSalesSummary),
            'categorySalesDonut' => $this->reports->categorySalesDonut($categorySalesSummary),
            'productSalesSummary' => $productSalesSummary,
            'categorySalesSummary' => $categorySalesSummary,
        ]);
    }
}
