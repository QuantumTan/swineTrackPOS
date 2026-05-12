<?php

namespace App\Http\Controllers;

use App\Services\ReportDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReportDataService $reports) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'product_search' => ['nullable', 'string', 'max:100'],
            'product_category' => ['nullable', 'string', 'max:100'],
        ]);

        $reportType = 'monthly';
        $filters = [
            'product_search' => trim((string) ($filters['product_search'] ?? '')),
            'product_category' => trim((string) ($filters['product_category'] ?? '')),
        ];
        $period = $this->reports->periodFor($reportType);
        // $salesTrend = $this->reports->salesTrendForPeriod($reportType); // COMMENTED OUT - vw_daily_sales_summary view no longer exists
        $productSalesSummary = $this->reports->productSalesSummary($reportType);
        // $paginatedDailySalesSummary = $this->reports->paginatedDailySalesSummary($reportType, 10, $filters); // COMMENTED OUT - vw_daily_sales_summary view no longer exists
        $paginatedProductSalesSummary = $this->reports->paginatedProductSalesSummary($reportType, 10, $filters);
        $categorySalesSummary = $this->reports->categorySalesSummary($reportType);

        $totalSales = $this->reports->totalSalesAggregate();
        $transactionCount = $this->reports->totalTransactionCountAggregate();
        $totalSoldQuantity = collect($productSalesSummary)->sum(fn (array $row): float => $row['qty_sold_value']);
        $averageTransaction = $this->reports->averageTransactionAggregate();

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
                'monthly' => 'Monthly',
            ],
            'salesTrend' => [], // COMMENTED OUT - vw_daily_sales_summary view no longer exists
            'dailySalesSummary' => [], // COMMENTED OUT - vw_daily_sales_summary view no longer exists
            'topProductsGraph' => $this->reports->topProductsGraph($productSalesSummary),
            'categorySalesDonut' => $this->reports->categorySalesDonut($categorySalesSummary),
            'productSalesSummary' => $paginatedProductSalesSummary,
            'categorySalesSummary' => $categorySalesSummary,
            'productSalesCategories' => $this->reports->productSalesCategories($reportType),
            'filters' => $filters,
        ]);
    }
}
