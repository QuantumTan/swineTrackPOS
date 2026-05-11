<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportDataService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function dailySalesSummary(int $limit = 7): array
    {
        return DB::table('vw_daily_sales_summary')
            ->orderByDesc('sale_day')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'sale_day' => $this->formatDate($row->sale_day),
                'total_transactions' => (int) $row->total_transactions,
                'total_sales' => $this->formatMoney($row->total_sales),
                'total_sales_value' => (float) $row->total_sales,
            ])
            ->all();
    }

    /**
     * @return array{start: Carbon|null, end: Carbon|null, label: string}
     */
    public function periodFor(string $reportType): array
    {
        $now = now();

        return match ($reportType) {
            'daily' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => $now->format('d M Y'),
            ],
            'weekly' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'label' => $now->copy()->startOfWeek()->format('d M Y').' to '.$now->copy()->endOfWeek()->format('d M Y'),
            ],
            'yearly' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear(),
                'label' => $now->format('Y'),
            ],
            default => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->format('F Y'),
            ],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function salesTrendForPeriod(string $reportType): array
    {
        $period = $this->periodFor($reportType);

        return $this->dailySalesSummaryQuery($reportType)
            ->orderBy('sale_day')
            ->get()
            ->map(fn (object $row): array => $this->formatDailySalesSummaryRow($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatedDailySalesSummary(string $reportType, int $perPage = 10, array $filters = [], string $pageName = 'daily_page'): LengthAwarePaginator
    {
        return $this->dailySalesSummaryQuery($reportType, $filters)
            ->orderByDesc('sale_day')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString()
            ->through(fn (object $row): array => $this->formatDailySalesSummaryRow($row));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function lowStockProducts(int $limit = 10): array
    {
        return DB::table('vw_low_stock_products')
            ->orderBy('current_stock')
            ->orderBy('product_name')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'product_id' => $this->formatProductId($row->product_id),
                'product_name' => $row->product_name,
                'current_stock' => $this->formatWeight($row->current_stock),
                'current_stock_value' => (float) $row->current_stock,
                'status' => $this->statusForStockLabel($row->stock_status),
            ])
            ->all();
    }

    /**
     * Get total count of low stock products from vw_low_stock_products view
     */
    public function lowStockProductsCount(): int
    {
        $row = DB::table('vw_low_stock_products')->first();
        return (int) ($row?->total_count ?? 0);
    }

    /**
     * Get total sales from vw_sales_details view
     */
    public function totalSalesAggregate(): float
    {
        $row = DB::table('vw_sales_details')->first();
        return (float) ($row?->total_sales ?? 0);
    }

    /**
     * Get total transaction count from vw_sales_details view
     */
    public function totalTransactionCountAggregate(): int
    {
        $row = DB::table('vw_sales_details')->first();
        return (int) ($row?->total_transactions ?? 0);
    }

    /**
     * Get average transaction amount from vw_payment_summary view
     */
    public function averageTransactionAggregate(): float
    {
        $row = DB::table('vw_payment_summary')->first();
        return (float) ($row?->average_transaction_amount ?? 0);
    }

    /**
     * Get total quantity sold from vw_sales_details view
     */
    public function inventorySnapshot(int $limit = 10): array
    {
        return DB::table('vw_product_inventory')
            ->orderBy('product_id')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'product_id' => $this->formatProductId($row->product_id),
                'product_name' => $row->product_name,
                'category_name' => $row->category_name ?? '-',
                'product_price_per_kilo' => $this->formatMoney($row->product_price_per_kilo),
                'current_stock' => $this->formatWeight($row->current_stock),
                'current_stock_value' => (float) $row->current_stock,
                'last_updated_at' => $this->formatDateTime($row->last_updated_at),
                'stock_status' => $this->statusForStockLabel($row->stock_status),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function batchDetails(int $limit = 10): array
    {
        return DB::table('vw_batch_details')
            ->orderByDesc('batch_date')
            ->orderByDesc('batch_id')
            ->limit($limit)
            ->get()
            ->map(fn (object $row): array => [
                'batch_id' => $this->formatBatchId($row->batch_id),
                'batch_date' => $this->formatDateTime($row->batch_date),
                'source_type' => $row->source_type,
                'batch_status' => $this->statusForBatchLabel($row->batch_status),
                'supplier_name' => $row->supplier_name,
                'user_email' => $row->user_email,
                'batch_item_id' => $this->formatBatchItemId($row->batch_item_id),
                'product_name' => $row->product_name,
                'qty_in_kg' => $this->formatWeight($row->qty_in_kg),
                'cost_per_kg' => $this->formatMoney($row->cost_per_kg),
                'line_total_cost' => $this->formatMoney($row->line_total_cost),
                'line_total_cost_value' => (float) $row->line_total_cost,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatedSalesDetails(int $perPage = 10, array $filters = [], string $pageName = 'sales_page'): LengthAwarePaginator
    {
        return $this->salesDetailsQuery($filters)
            ->orderByDesc('sale_date')
            ->orderByDesc('sale_id')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString()
            ->through(fn (object $row): array => $this->formatSalesDetailRow($row));
    }

    /**
     * @return array<int, string>
     */
    public function salesActivityCategories(): array
    {
        return DB::table('vw_sales_details')
            ->select('category_name')
            ->whereNotNull('category_name')
            ->distinct()
            ->orderBy('category_name')
            ->pluck('category_name')
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function productSalesSummary(string $reportType): array
    {
        return $this->productSalesSummaryQuery($reportType)
            ->orderByDesc('revenue')
            ->get()
            ->map(fn (object $row): array => $this->formatProductSalesSummaryRow($row))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatedProductSalesSummary(string $reportType, int $perPage = 10, array $filters = [], string $pageName = 'products_page'): LengthAwarePaginator
    {
        return $this->productSalesSummaryQuery($reportType, $filters)
            ->orderByDesc('revenue')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString()
            ->through(fn (object $row): array => $this->formatProductSalesSummaryRow($row));
    }

    /**
     * @return array<int, string>
     */
    public function productSalesCategories(string $reportType): array
    {
        $period = $this->periodFor($reportType);

        return DB::table('vw_sales_details')
            ->when($period['start'], fn ($query) => $query->whereBetween('sale_date', [$period['start'], $period['end']]))
            ->select('category_name')
            ->whereNotNull('category_name')
            ->distinct()
            ->orderBy('category_name')
            ->pluck('category_name')
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function categorySalesSummary(string $reportType): array
    {
        $period = $this->periodFor($reportType);

        return DB::table('vw_sales_details')
            ->when($period['start'], fn ($query) => $query->whereBetween('sale_date', [$period['start'], $period['end']]))
            ->selectRaw("COALESCE(category_name, 'Uncategorized') AS category_name")
            ->selectRaw('SUM(line_total) AS revenue')
            ->groupBy('category_name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn (object $row, int $index): array => [
                'category_name' => $row->category_name,
                'revenue' => $this->formatMoney($row->revenue),
                'revenue_value' => (float) $row->revenue,
                'color' => ['#16a34a', '#2563eb', '#f97316', '#7c3aed', '#dc2626'][$index % 5],
            ])
            ->all();
    }

    public function salesTransactions(string $reportType): Collection
    {
        $period = $this->periodFor($reportType);

        return DB::table('vw_payment_summary')
            ->when($period['start'], fn ($query) => $query->whereBetween('sale_date', [$period['start'], $period['end']]))
            ->orderByDesc('sale_date')
            ->get()
            ->map(fn (object $row): array => [
                'sale_id' => $this->formatSaleId($row->sale_id),
                'sale_date' => $this->formatDateTime($row->sale_date),
                'customer' => 'Walk-in Customer',
                'item_count' => (int) $row->item_count,
                'amount' => $this->formatMoney($row->amount),
                'amount_value' => (float) $row->amount,
                'total_qty_sold_value' => (float) $row->total_qty_sold_kg,
            ]);
    }

    public function paymentSummary(): Collection
    {
        return DB::table('vw_payment_summary')
            ->where('payment_status', 'paid')
            ->get()
            ->map(fn (object $row): array => $this->formatPaymentSummaryRow($row));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginatedPaymentSummary(int $perPage = 10, array $filters = [], string $pageName = 'payments_page'): LengthAwarePaginator
    {
        return $this->paymentSummaryQuery($filters)
            ->orderByDesc('sale_date')
            ->orderByDesc('sale_id')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString()
            ->through(fn (object $row): array => $this->formatPaymentSummaryRow($row));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function salesDetailsQuery(array $filters)
    {
        $query = DB::table('vw_sales_details');

        $search = trim((string) ($filters['search'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));

        if ($search !== '') {
            $like = "%{$search}%";
            $saleId = $this->prefixedIdSearchValue($search, 's');
            $batchId = $this->prefixedIdSearchValue($search, 'b');
            $saleItemId = $this->prefixedIdSearchValue($search, 'si');

            $query->where(function ($query) use ($like, $saleId, $batchId, $saleItemId): void {
                $query
                    ->where('user_email', 'like', $like)
                    ->orWhere('product_name', 'like', $like)
                    ->orWhere('category_name', 'like', $like);

                if ($saleId !== null) {
                    $query->orWhere('sale_id', $saleId);
                }

                if ($batchId !== null) {
                    $query->orWhere('batch_id', $batchId);
                }

                if ($saleItemId !== null) {
                    $query->orWhere('sale_item_id', $saleItemId);
                }
            });
        }

        if ($category !== '') {
            $query->where('category_name', $category);
        }

        if ($dateFrom !== '') {
            $query->where('sale_date', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo !== '') {
            $query->where('sale_date', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function dailySalesSummaryQuery(string $reportType, array $filters = [])
    {
        $period = $this->periodFor($reportType);
        $query = DB::table('vw_daily_sales_summary')
            ->when($period['start'], fn ($query) => $query->whereBetween('sale_day', [
                $period['start']->toDateString(),
                $period['end']->toDateString(),
            ]));

        $dateFrom = trim((string) ($filters['daily_date_from'] ?? ''));
        $dateTo = trim((string) ($filters['daily_date_to'] ?? ''));

        if ($dateFrom !== '') {
            $query->where('sale_day', '>=', Carbon::parse($dateFrom)->toDateString());
        }

        if ($dateTo !== '') {
            $query->where('sale_day', '<=', Carbon::parse($dateTo)->toDateString());
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function productSalesSummaryQuery(string $reportType, array $filters = [])
    {
        $period = $this->periodFor($reportType);
        $query = DB::table('vw_sales_details')
            ->when($period['start'], fn ($query) => $query->whereBetween('sale_date', [$period['start'], $period['end']]));

        $search = trim((string) ($filters['product_search'] ?? ''));
        $category = trim((string) ($filters['product_category'] ?? ''));

        if ($search !== '') {
            $like = "%{$search}%";

            $query->where(function ($query) use ($like): void {
                $query
                    ->where('product_name', 'like', $like)
                    ->orWhere('category_name', 'like', $like);
            });
        }

        if ($category !== '') {
            $query->where('category_name', $category);
        }

        return $query
            ->select('product_name', 'category_name')
            ->selectRaw('SUM(qty_sold_kg) AS total_qty_sold')
            ->selectRaw('SUM(line_total) AS revenue')
            ->groupBy('product_name', 'category_name');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function paymentSummaryQuery(array $filters)
    {
        $query = DB::table('vw_payment_summary');

        $search = trim((string) ($filters['payment_search'] ?? ''));
        $status = trim((string) ($filters['payment_status'] ?? ''));
        $dateFrom = trim((string) ($filters['payment_date_from'] ?? ''));
        $dateTo = trim((string) ($filters['payment_date_to'] ?? ''));

        if ($search !== '') {
            $like = "%{$search}%";
            $saleId = $this->prefixedIdSearchValue($search, 's');
            $batchId = $this->prefixedIdSearchValue($search, 'b');

            $query->where(function ($query) use ($like, $saleId, $batchId): void {
                $query->where('user_email', 'like', $like);

                if ($saleId !== null) {
                    $query->orWhere('sale_id', $saleId);
                }

                if ($batchId !== null) {
                    $query->orWhere('batch_id', $batchId);
                }
            });
        }

        if ($status !== '') {
            $query->where('payment_status', $status);
        }

        if ($dateFrom !== '') {
            $query->where('sale_date', '>=', Carbon::parse($dateFrom)->startOfDay());
        }

        if ($dateTo !== '') {
            $query->where('sale_date', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSalesDetailRow(object $row): array
    {
        return [
            'sale_id' => $this->formatSaleId($row->sale_id),
            'sale_date' => $this->formatDateTime($row->sale_date),
            'batch_id' => $this->formatBatchId($row->batch_id),
            'user_email' => $row->user_email,
            'sale_item_id' => $this->formatSaleItemId($row->sale_item_id),
            'product_name' => $row->product_name,
            'category_name' => $row->category_name ?? '-',
            'qty_sold_kg' => $this->formatWeight($row->qty_sold_kg),
            'qty_sold_value' => (float) $row->qty_sold_kg,
            'price_per_kg' => $this->formatMoney($row->price_per_kg),
            'line_total' => $this->formatMoney($row->line_total),
            'line_total_value' => (float) $row->line_total,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPaymentSummaryRow(object $row): array
    {
        return [
            'sale_id' => $this->formatSaleId($row->sale_id),
            'sale_date' => $this->formatDateTime($row->sale_date),
            'payment_status' => $row->payment_status ?? 'pending',
            'amount' => $this->formatMoney($row->amount),
            'amount_value' => (float) $row->amount,
            'item_count' => (int) $row->item_count,
            'total_qty_sold_kg' => $this->formatWeight($row->total_qty_sold_kg),
            'total_qty_sold_value' => (float) $row->total_qty_sold_kg,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDailySalesSummaryRow(object $row): array
    {
        return [
            'label' => $this->formatDate($row->sale_day),
            'total_sales' => $this->formatMoney($row->total_sales),
            'total_sales_value' => (float) $row->total_sales,
            'transactions' => (int) $row->total_transactions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatProductSalesSummaryRow(object $row): array
    {
        return [
            'product_name' => $row->product_name,
            'category_name' => $row->category_name ?? '-',
            'qty_sold_kg' => $this->formatWeight($row->total_qty_sold),
            'qty_sold_value' => (float) $row->total_qty_sold,
            'revenue' => $this->formatMoney($row->revenue),
            'revenue_value' => (float) $row->revenue,
        ];
    }

    private function prefixedIdSearchValue(string $search, string $prefix): ?int
    {
        $normalized = preg_replace('/\s+/', '', strtolower($search));
        $quotedPrefix = preg_quote($prefix, '/');

        if (! preg_match("/^{$quotedPrefix}0*(\d+)$/", $normalized, $matches)) {
            return ctype_digit($normalized) && $prefix === 's' ? (int) $normalized : null;
        }

        return (int) $matches[1];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function coverageItems(): array
    {
        return [
            [
                'kind' => 'View',
                'name' => 'vw_product_inventory',
                'description' => 'Inventory display for products, stock, price per kilo, update time, and computed stock status.',
                'detail' => 'product_id, product_name, category_name, product_price_per_kilo, current_stock, last_updated_at, stock_status',
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
                'detail' => 'product_id, product_name, current_stock, stock_status',
            ],
            [
                'kind' => 'View',
                'name' => 'vw_payment_summary',
                'description' => 'Payment-backed sale totals for report cards and paid transaction counts.',
                'detail' => 'sale_id, sale_date, batch_id, user_email, payment_status, payment_date, amount, item_count, total_qty_sold_kg, total_line_sales',
            ],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function salesGraph(array $rows): array
    {
        $maxSales = max(collect($rows)->max('total_sales_value') ?? 0, 1);

        return collect($rows)
            ->reverse()
            ->values()
            ->map(fn (array $row): array => [
                'label' => $row['label'] ?? $row['sale_day'],
                'total_sales' => $row['total_sales'],
                'transactions' => $row['transactions'] ?? $row['total_transactions'],
                'height' => $this->graphWidth((float) $row['total_sales_value'], $maxSales),
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function topProductsGraph(array $rows): array
    {
        $maxRevenue = max(collect($rows)->max('revenue_value') ?? 0, 1);

        return collect($rows)
            ->take(5)
            ->map(fn (array $row): array => [
                'label' => $row['product_name'],
                'value' => $row['revenue'],
                'width' => $this->graphWidth((float) $row['revenue_value'], $maxRevenue),
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{segments: array<int, array<string, mixed>>, gradient: string}
     */
    public function categorySalesDonut(array $rows): array
    {
        $total = max(collect($rows)->sum('revenue_value'), 1);
        $cursor = 0.0;
        $gradientParts = [];

        $segments = collect($rows)
            ->map(function (array $row) use ($total, &$cursor, &$gradientParts): array {
                $percent = ($row['revenue_value'] / $total) * 100;
                $start = $cursor;
                $cursor += $percent;
                $gradientParts[] = "{$row['color']} {$start}% {$cursor}%";

                return [
                    ...$row,
                    'percent' => round($percent),
                ];
            })
            ->all();

        return [
            'segments' => $segments,
            'gradient' => $gradientParts ? implode(', ', $gradientParts) : '#ecf2f8 0% 100%',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function stockGraph(array $rows): array
    {
        $maxStock = max(collect($rows)->max('current_stock_value') ?? 0, 1);

        return collect($rows)
            ->take(4)
            ->map(fn (array $row): array => [
                'label' => $row['product_name'],
                'value' => $row['current_stock'],
                'width' => $this->graphWidth((float) $row['current_stock_value'], $maxStock),
                'type' => ($row['stock_status'] ?? $row['status'])['class'],
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function lowStockRiskGraph(array $rows, float $threshold = 20): array
    {
        return collect($rows)
            ->take(6)
            ->map(fn (array $row): array => [
                'label' => $row['product_name'],
                'value' => $row['current_stock'],
                'width' => min(100, $this->graphWidth((float) $row['current_stock_value'], max($threshold, 1))),
                'type' => $row['status']['class'],
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{segments: array<int, array<string, mixed>>, gradient: string}
     */
    public function inventoryStatusMix(array $rows): array
    {
        $segments = collect($rows)
            ->groupBy(fn (array $row): string => $row['stock_status']['label'])
            ->map(function (Collection $group, string $label): array {
                $status = $this->statusForStockLabel($label);

                return [
                    'label' => $label,
                    'count' => $group->count(),
                    'class' => $status['class'],
                    'color' => match ($status['class']) {
                        'danger' => '#d24c4c',
                        'warning' => '#df7b2f',
                        default => '#1f9b4b',
                    },
                ];
            })
            ->sortBy(fn (array $segment): int => match ($segment['label']) {
                'In Stock' => 1,
                'Low Stock' => 2,
                default => 3,
            })
            ->values();

        $total = max($segments->sum('count'), 1);
        $cursor = 0.0;
        $gradientParts = [];

        $segments = $segments
            ->map(function (array $segment) use ($total, &$cursor, &$gradientParts): array {
                $percent = ($segment['count'] / $total) * 100;
                $start = $cursor;
                $cursor += $percent;
                $gradientParts[] = "{$segment['color']} {$start}% {$cursor}%";

                return [
                    ...$segment,
                    'percent' => round($percent),
                ];
            })
            ->all();

        return [
            'segments' => $segments,
            'gradient' => $gradientParts ? implode(', ', $gradientParts) : '#ecf2f8 0% 100%',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function moneyGraph(array $rows, string $valueKey, string $labelValueKey): array
    {
        $maxValue = max(collect($rows)->max($valueKey) ?? 0, 1);

        return collect($rows)
            ->take(4)
            ->map(fn (array $row, int $index): array => [
                'label' => $row['product_name'],
                'value' => $row[$labelValueKey],
                'width' => $this->graphWidth((float) $row[$valueKey], $maxValue),
                'type' => ['primary', 'warning', 'success', 'danger'][$index % 4],
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $batchRows
     * @param  array<int, array<string, mixed>>  $salesRows
     * @return array<int, array<string, mixed>>
     */
    public function costSalesComparison(array $batchRows, array $salesRows): array
    {
        $costs = collect($batchRows)
            ->groupBy('product_name')
            ->map(fn (Collection $rows): float => $rows->sum('line_total_cost_value'));

        $sales = collect($salesRows)
            ->groupBy('product_name')
            ->map(fn (Collection $rows): float => $rows->sum('line_total_value'));

        $products = $costs->keys()
            ->merge($sales->keys())
            ->unique()
            ->values();

        $maxValue = max($costs->max() ?? 0, $sales->max() ?? 0, 1);

        return $products
            ->map(fn (string $product): array => [
                'label' => $product,
                'cost' => $this->formatMoney($costs->get($product, 0)),
                'sales' => $this->formatMoney($sales->get($product, 0)),
                'cost_width' => $this->graphWidth($costs->get($product, 0), $maxValue),
                'sales_width' => $this->graphWidth($sales->get($product, 0), $maxValue),
            ])
            ->sortByDesc(fn (array $row): int => max($row['cost_width'], $row['sales_width']))
            ->take(6)
            ->values()
            ->all();
    }

    public function formatMoney(float|int|string|null $value): string
    {
        return 'P'.number_format((float) ($value ?? 0), 2);
    }

    private function graphWidth(float $value, float $maxValue): int
    {
        if ($value <= 0) {
            return 6;
        }

        return max(8, (int) round(($value / $maxValue) * 100));
    }

    private function formatProductId(int|string $id): string
    {
        return 'P'.str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    private function formatBatchId(int|string $id): string
    {
        return 'B'.str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    private function formatBatchItemId(int|string $id): string
    {
        return 'BI'.str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    private function formatSaleId(int|string $id): string
    {
        return 'S'.str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    private function formatSaleItemId(int|string $id): string
    {
        return 'SI'.str_pad((string) $id, 3, '0', STR_PAD_LEFT);
    }

    private function formatWeight(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), 3).' kg';
    }

    private function formatDate(?string $value): string
    {
        return $value ? Carbon::parse($value)->format('d M Y') : '-';
    }

    private function formatDateTime(?string $value): string
    {
        return $value ? Carbon::parse($value)->format('d M Y, h:i A') : '-';
    }

    /**
     * @return array{label: string, class: string}
     */
    private function statusForStockLabel(string $label): array
    {
        return [
            'label' => $label,
            'class' => match ($label) {
                'Out of Stock' => 'danger',
                'Low Stock' => 'warning',
                default => 'success',
            },
        ];
    }

    /**
     * @return array{label: string, class: string}
     */
    private function statusForBatchLabel(string $label): array
    {
        return [
            'label' => $label,
            'class' => match ($label) {
                'Open' => 'primary',
                'Sold Out' => 'warning',
                default => 'neutral',
            },
        ];
    }
}
