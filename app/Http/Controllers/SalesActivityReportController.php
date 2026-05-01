<?php

namespace App\Http\Controllers;

use App\Services\ReportDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesActivityReportController extends Controller
{
    public function __construct(private readonly ReportDataService $reports) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $filters = [
            'search' => trim((string) ($filters['search'] ?? '')),
            'category' => trim((string) ($filters['category'] ?? '')),
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
        ];

        $salesDetails = $this->reports->salesDetails(50, $filters);
        $paymentSummary = $this->reports->paymentSummary();
        $visibleSaleIds = collect($salesDetails)->pluck('sale_id')->unique();
        $totalSales = collect($salesDetails)->sum(fn (array $row): float => $row['line_total_value']);
        $totalQuantity = collect($salesDetails)->sum(fn (array $row): float => $row['qty_sold_value']);

        return view('pos.reports.sales-activity', [
            'summaryCards' => [
                [
                    'label' => 'Sales Activity',
                    'value' => (string) count($salesDetails),
                    'trend' => 'Recent sold item lines',
                    'icon' => 'bi-receipt',
                ],
                [
                    'label' => 'Line Sales',
                    'value' => $this->reports->formatMoney($totalSales),
                    'trend' => 'Total value of visible activity rows',
                    'icon' => 'bi-cash-stack',
                ],
                [
                    'label' => 'Paid Sales',
                    'value' => (string) $paymentSummary->whereIn('sale_id', $visibleSaleIds)->count(),
                    'trend' => 'Visible paid transactions',
                    'icon' => 'bi-check2-circle',
                ],
                [
                    'label' => 'Quantity Sold',
                    'value' => number_format($totalQuantity, 3).' kg',
                    'trend' => 'Visible quantity total',
                    'icon' => 'bi-speedometer2',
                ],
            ],
            'salesDetails' => $salesDetails,
            'paymentSummary' => $paymentSummary,
            'salesCategories' => $this->reports->salesActivityCategories(),
            'filters' => $filters,
        ]);
    }
}
