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
            'payment_search' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', 'in:paid,pending'],
            'payment_date_from' => ['nullable', 'date'],
            'payment_date_to' => ['nullable', 'date', 'after_or_equal:payment_date_from'],
        ]);

        $filters = [
            'search' => trim((string) ($filters['search'] ?? '')),
            'category' => trim((string) ($filters['category'] ?? '')),
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
            'payment_search' => trim((string) ($filters['payment_search'] ?? '')),
            'payment_status' => trim((string) ($filters['payment_status'] ?? '')),
            'payment_date_from' => $filters['payment_date_from'] ?? '',
            'payment_date_to' => $filters['payment_date_to'] ?? '',
        ];

        $salesDetails = $this->reports->paginatedSalesDetails(10, $filters);
        $paymentSummary = $this->reports->paginatedPaymentSummary(10, $filters);
        $paidPaymentSummary = $this->reports->paymentSummary();
        $visibleSalesDetails = collect($salesDetails->items());
        $visibleSaleIds = $visibleSalesDetails->pluck('sale_id')->unique();
        $totalSales = $visibleSalesDetails->sum(fn (array $row): float => $row['line_total_value']);
        $totalQuantity = $visibleSalesDetails->sum(fn (array $row): float => $row['qty_sold_value']);

        return view('pos.reports.sales-activity', [
            'summaryCards' => [
                [
                    'label' => 'Sales Activity',
                    'value' => (string) $salesDetails->total(),
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
                    'value' => (string) $paidPaymentSummary->whereIn('sale_id', $visibleSaleIds)->count(),
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
