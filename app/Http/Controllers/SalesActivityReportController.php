<?php

namespace App\Http\Controllers;

use App\Services\ReportDataService;
use Illuminate\View\View;

class SalesActivityReportController extends Controller
{
    public function __construct(private readonly ReportDataService $reports)
    {
    }

    public function index(): View
    {
        $salesDetails = $this->reports->salesDetails(50);
        $paymentSummary = $this->reports->paymentSummary();
        $totalSales = collect($salesDetails)->sum(fn (array $row): float => $row['line_total_value']);
        $totalQuantity = $paymentSummary->sum(fn (array $row): float => $row['total_qty_sold_value']);

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
                    'value' => (string) $paymentSummary->count(),
                    'trend' => 'Completed paid transactions',
                    'icon' => 'bi-check2-circle',
                ],
                [
                    'label' => 'Quantity Sold',
                    'value' => number_format($totalQuantity, 3).' kg',
                    'trend' => 'Paid quantity total',
                    'icon' => 'bi-speedometer2',
                ],
            ],
            'salesDetails' => $salesDetails,
            'paymentSummary' => $paymentSummary,
        ]);
    }
}
