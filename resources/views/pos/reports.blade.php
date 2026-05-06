<x-app-layout pageTitle="Reports">
    @include('pos.partials.page-header', [
        'title' => $reportMeta['title'],
        'subtitle' => 'Review sales performance summaries, product movement, and category trends.',
        'actions' => new \Illuminate\Support\HtmlString(
            '<a class="btn btn-light border px-4" href="'.route('reports.sales-activity').'"><i class="bi bi-receipt me-2"></i>Sales Activity</a>'
        ),
    ])

    <section class="content-card report-filter-card mb-4">
        <form method="GET" action="{{ route('reports.index') }}" class="report-filter-form">
            <input type="hidden" name="daily_date_from" value="{{ $filters['daily_date_from'] }}">
            <input type="hidden" name="daily_date_to" value="{{ $filters['daily_date_to'] }}">
            <input type="hidden" name="product_search" value="{{ $filters['product_search'] }}">
            <input type="hidden" name="product_category" value="{{ $filters['product_category'] }}">

            <div>
                <h3 class="section-title mb-3">Report Filters</h3>
                <label class="form-label fw-semibold" for="report-type">Report Type</label>
                <select id="report-type" name="type" class="form-select report-type-select">
                    @foreach ($reportTypes as $value => $label)
                        <option value="{{ $value }}" @selected($reportMeta['type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="section-subtitle mt-2">Period: {{ $reportMeta['period'] }}</div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-play-fill me-2"></i>Generate Report
                </button>
                <button type="button" class="btn btn-light border px-4" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Print
                </button>
            </div>
        </form>
    </section>

    <div class="row g-4 mb-4">
        @foreach ($summaryCards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                @include('pos.partials.summary-card', [
                    'label' => $card['label'],
                    'value' => $card['value'],
                    'meta' => $card['trend'],
                    'icon' => $card['icon'],
                    'tone' => $card['tone'] ?? 'green',
                ])
            </div>
        @endforeach
    </div>

    <section class="content-card report-analytics-section mb-4">
        <div class="card-header-clean">
            <h3 class="section-title mb-0">Sales Trend</h3>
        </div>

        <div class="report-chart-body">
            <div class="bar-graph report-sales-trend">
                @forelse ($salesTrend as $bar)
                    <div class="bar-column">
                        <div class="bar-track report-trend-track">
                            <div class="bar-fill graph-fill-revenue" style="--value: {{ $bar['height'] }};"></div>
                        </div>
                        <div class="bar-label">{{ $bar['label'] }}</div>
                        <div class="bar-value report-revenue-text">{{ $bar['total_sales'] }}</div>
                        <div class="bar-meta">{{ $bar['transactions'] }} transaction(s)</div>
                    </div>
                @empty
                    <div class="report-empty">No sales trend data for this period.</div>
                @endforelse
            </div>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-6">
            <section class="content-card h-100">
                <div class="card-header-clean">
                    <h3 class="section-title mb-0">Top Selling Products</h3>
                </div>

                <div class="report-chart-body">
                    <div class="top-products-chart">
                        @forelse ($topProductsGraph as $item)
                            <div class="top-product-row">
                                <div class="top-product-label">{{ $item['label'] }}</div>
                                <div class="top-product-track">
                                    <div class="top-product-fill" style="--value: {{ $item['width'] }};"></div>
                                </div>
                                <div class="top-product-value">{{ $item['value'] }}</div>
                            </div>
                        @empty
                            <div class="report-empty">No product sales for this period.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-card h-100">
                <div class="card-header-clean">
                    <h3 class="section-title mb-0">Sales by Category</h3>
                </div>

                <div class="report-chart-body">
                    <div class="donut-chart-wrap">
                        <div class="donut-chart" style="--segments: {{ $categorySalesDonut['gradient'] }};">
                            <div class="donut-center">
                                <div class="donut-value">{{ count($categorySalesDonut['segments']) }}</div>
                                <div class="donut-label">Categories</div>
                            </div>
                        </div>
                        <div class="donut-legend">
                            @forelse ($categorySalesDonut['segments'] as $segment)
                                <div class="donut-legend-row">
                                    <span class="donut-dot" style="--dot-color: {{ $segment['color'] }};"></span>
                                    <span class="fw-semibold">{{ $segment['category_name'] }}</span>
                                    <span class="text-secondary small">{{ $segment['revenue'] }} | {{ $segment['percent'] }}%</span>
                                </div>
                            @empty
                                <div class="report-empty">No category sales for this period.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <section class="content-card mb-4">
        <div class="card-header-clean">
            <h3 class="section-title mb-0">Daily Sales Summary</h3>
            <div class="section-subtitle mt-2">Daily transaction totals for the selected report period.</div>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="row g-3 m-2">
            <input type="hidden" name="type" value="{{ $reportMeta['type'] }}">
            <input type="hidden" name="product_search" value="{{ $filters['product_search'] }}">
            <input type="hidden" name="product_category" value="{{ $filters['product_category'] }}">

            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">From</label>
                <input name="daily_date_from" type="date" class="form-control" value="{{ $filters['daily_date_from'] }}">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">To</label>
                <input name="daily_date_to" type="date" class="form-control" value="{{ $filters['daily_date_to'] }}">
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-success w-100">Apply</button>
                <a href="{{ route('reports.index', [
                    'type' => $reportMeta['type'],
                    'product_search' => $filters['product_search'],
                    'product_category' => $filters['product_category'],
                ]) }}" class="btn btn-light border w-100">Clear</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sale Day</th>
                        <th>Total Transactions</th>
                        <th>Total Sales</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dailySalesSummary as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['label'] }}</td>
                            <td>{{ $row['transactions'] }} transaction(s)</td>
                            <td class="fw-semibold report-revenue-text">{{ $row['total_sales'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-4" colspan="3">No daily sales summary rows for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3">
            {{ $dailySalesSummary->links('pagination::bootstrap-5') }}
        </div>
    </section>

    <section class="content-card mb-4">
        <div class="card-header-clean">
            <h3 class="section-title mb-0">Product Sales Summary</h3>
            <div class="section-subtitle mt-2">Aggregated by product for the selected report period.</div>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="row g-3 m-2">
            <input type="hidden" name="type" value="{{ $reportMeta['type'] }}">
            <input type="hidden" name="daily_date_from" value="{{ $filters['daily_date_from'] }}">
            <input type="hidden" name="daily_date_to" value="{{ $filters['daily_date_to'] }}">

            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Search</label>
                <input
                    type="text"
                    name="product_search"
                    class="form-control"
                    value="{{ $filters['product_search'] }}"
                    placeholder="Product or category"
                >
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Category</label>
                <select name="product_category" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($productSalesCategories as $category)
                        <option value="{{ $category }}" @selected($filters['product_category'] === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-success w-100">Apply</button>
                <a href="{{ route('reports.index', [
                    'type' => $reportMeta['type'],
                    'daily_date_from' => $filters['daily_date_from'],
                    'daily_date_to' => $filters['daily_date_to'],
                ]) }}" class="btn btn-light border w-100">Clear</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Qty Sold (kg)</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productSalesSummary as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['product_name'] }}</td>
                            <td>{{ $row['category_name'] }}</td>
                            <td>{{ $row['qty_sold_kg'] }}</td>
                            <td class="fw-semibold report-revenue-text">{{ $row['revenue'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-4" colspan="4">No product sales for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3">
            {{ $productSalesSummary->links('pagination::bootstrap-5') }}
        </div>
    </section>
</x-app-layout>
