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
            'type' => ['nullable', 'in:daily,weekly,monthly,yearly'],
            'daily_date_from' => ['nullable', 'date'],
            'daily_date_to' => ['nullable', 'date', 'after_or_equal:daily_date_from'],
            'product_search' => ['nullable', 'string', 'max:100'],
            'product_category' => ['nullable', 'string', 'max:100'],
        ]);

        $reportType = $request->string('type')->toString() ?: 'monthly';
        $reportType = in_array($reportType, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $reportType : 'monthly';
        $filters = [
            'daily_date_from' => $filters['daily_date_from'] ?? '',
            'daily_date_to' => $filters['daily_date_to'] ?? '',
            'product_search' => trim((string) ($filters['product_search'] ?? '')),
            'product_category' => trim((string) ($filters['product_category'] ?? '')),
        ];
        $period = $this->reports->periodFor($reportType);
        $salesTrend = $this->reports->salesTrendForPeriod($reportType);
        $productSalesSummary = $this->reports->productSalesSummary($reportType);
        $paginatedDailySalesSummary = $this->reports->paginatedDailySalesSummary($reportType, 10, $filters);
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
                'daily' => 'Daily',
                'weekly' => 'Weekly',
                'monthly' => 'Monthly',
                'yearly' => 'Yearly',
            ],
            'salesTrend' => $this->reports->salesGraph($salesTrend),
            'dailySalesSummary' => $paginatedDailySalesSummary,
            'topProductsGraph' => $this->reports->topProductsGraph($productSalesSummary),
            'categorySalesDonut' => $this->reports->categorySalesDonut($categorySalesSummary),
            'productSalesSummary' => $paginatedProductSalesSummary,
            'categorySalesSummary' => $categorySalesSummary,
            'productSalesCategories' => $this->reports->productSalesCategories($reportType),
            'filters' => $filters,
        ]);
    }
}
