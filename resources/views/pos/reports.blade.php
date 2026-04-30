<x-app-layout pageTitle="Reports">
    @include('pos.partials.page-header', [
        'title' => 'Reports',
        'subtitle' => 'Static report view for sales, stock, and receiving activity.',
        'actions' => new \Illuminate\Support\HtmlString(
            '<div class="d-flex flex-wrap gap-2"><button class="btn btn-success px-4" type="button"><i class="bi bi-download me-2"></i>Export Summary</button><button class="btn btn-light border px-4" type="button"><i class="bi bi-printer me-2"></i>Print Preview</button></div>'
        ),
    ])

    <div class="row g-4 mb-4">
        @foreach ($summaryCards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                @include('pos.partials.summary-card', [
                    'label' => $card['label'],
                    'value' => $card['value'],
                    'meta' => $card['trend'],
                    'icon' => $card['icon'],
                ])
            </div>
        @endforeach
    </div>

    <section class="content-card mb-4">
        <div class="row g-4 p-4">
            <div class="col-12 col-xl-6">
                <div class="graph-card h-100">
                    <div class="graph-header">
                        <div>
                            <div class="graph-eyebrow">Graph</div>
                            <h3 class="section-title mb-1">Sales Trend</h3>
                            <p class="section-subtitle mb-0">Daily sales totals and transaction counts.</p>
                        </div>
                    </div>

                    <div class="bar-graph">
                        @foreach ($salesGraph as $bar)
                            <div class="bar-column">
                                <div class="bar-track">
                                    <div class="bar-fill graph-fill-primary" style="--value: {{ $bar['height'] }};"></div>
                                </div>
                                <div class="bar-value">{{ $bar['total_sales'] }}</div>
                                <div class="bar-label">{{ $bar['label'] }}</div>
                                <div class="bar-meta">{{ $bar['transactions'] }} transactions</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="graph-card h-100">
                    <div class="graph-header">
                        <div>
                            <div class="graph-eyebrow">Graph</div>
                            <h3 class="section-title mb-1">Low Stock Levels</h3>
                            <p class="section-subtitle mb-0">Current low-stock quantities used for the reorder watchlist.</p>
                        </div>
                    </div>

                    <div class="progress-list">
                        @foreach ($lowStockGraph as $item)
                            <div class="progress-row">
                                <div class="progress-copy">
                                    <div class="fw-semibold">{{ $item['label'] }}</div>
                                    <div class="text-secondary small">{{ $item['value'] }}</div>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill progress-fill-{{ $item['type'] }}" style="--value: {{ $item['width'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="graph-card h-100">
                    <div class="graph-header">
                        <div>
                            <div class="graph-eyebrow">Graph</div>
                            <h3 class="section-title mb-1">Inventory Quantities</h3>
                            <p class="section-subtitle mb-0">Stock quantity comparison across the sample inventory rows.</p>
                        </div>
                    </div>

                    <div class="progress-list">
                        @foreach ($inventoryGraph as $item)
                            <div class="progress-row">
                                <div class="progress-copy">
                                    <div class="fw-semibold">{{ $item['label'] }}</div>
                                    <div class="text-secondary small">{{ $item['value'] }}</div>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill progress-fill-{{ $item['type'] }}" style="--value: {{ $item['width'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-6">
                <div class="graph-card h-100">
                    <div class="graph-header">
                        <div>
                            <div class="graph-eyebrow">Graph</div>
                            <h3 class="section-title mb-1">Cost and Sales Comparison</h3>
                            <p class="section-subtitle mb-0">Batch intake cost and sold-line value comparisons.</p>
                        </div>
                    </div>

                    <div class="progress-list mb-4">
                        @foreach ($batchCostGraph as $item)
                            <div class="progress-row">
                                <div class="progress-copy">
                                    <div class="fw-semibold">Batch: {{ $item['label'] }}</div>
                                    <div class="text-secondary small">{{ $item['value'] }}</div>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill progress-fill-{{ $item['type'] }}" style="--value: {{ $item['width'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="progress-list">
                        @foreach ($salesMixGraph as $item)
                            <div class="progress-row">
                                <div class="progress-copy">
                                    <div class="fw-semibold">Sales: {{ $item['label'] }}</div>
                                    <div class="text-secondary small">{{ $item['value'] }}</div>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill progress-fill-{{ $item['type'] }}" style="--value: {{ $item['width'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-5">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Low Stock Watchlist',
                    'subtitle' => 'Products currently below the safe stock level.',
                ])

                <div class="p-4 pt-3 d-grid gap-3">
                    @foreach ($lowStockProducts as $product)
                        <div class="alert-item">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $product['product_name'] }}</div>
                                    <div class="text-secondary small">{{ $product['product_id'] }} | {{ $product['current_stock'] }}</div>
                                </div>
                                @include('pos.partials.status-pill', [
                                    'label' => $product['status']['label'],
                                    'type' => $product['status']['class'],
                                ])
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-7">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Daily Sales Breakdown',
                    'subtitle' => 'Sample rows for sale day, total transactions, and total sales.',
                ])

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
                            @foreach ($dailySalesSummary as $row)
                                <tr>
                                    <td class="fw-semibold">{{ $row['sale_day'] }}</td>
                                    <td>{{ $row['total_transactions'] }}</td>
                                    <td class="fw-semibold">{{ $row['total_sales'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Sales Activity',
                    'subtitle' => 'Recent sold items and line totals.',
                ])

                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Date</th>
                                <th>Batch</th>
                                <th>User</th>
                                <th>Product</th>
                                <th>Qty Sold</th>
                                <th>Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($salesDetails as $row)
                                <tr>
                                    <td class="text-secondary">{{ $row['sale_id'] }}</td>
                                    <td>{{ $row['sale_date'] }}</td>
                                    <td>{{ $row['batch_id'] }}</td>
                                    <td>{{ $row['user_email'] }}</td>
                                    <td class="fw-semibold">{{ $row['product_name'] }}</td>
                                    <td>{{ $row['qty_sold_kg'] }}</td>
                                    <td class="fw-semibold">{{ $row['line_total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Batch Cost Review',
                    'subtitle' => 'Rows for recent receiving cost activity.',
                ])

                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Source Type</th>
                                <th>Product</th>
                                <th>Qty In</th>
                                <th>Cost / kg</th>
                                <th>Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($batchDetails as $row)
                                <tr>
                                    <td class="text-secondary">{{ $row['batch_id'] }}</td>
                                    <td>{{ $row['source_type'] }}</td>
                                    <td class="fw-semibold">{{ $row['product_name'] }}</td>
                                    <td>{{ $row['qty_in_kg'] }}</td>
                                    <td>{{ $row['cost_per_kg'] }}</td>
                                    <td class="fw-semibold">{{ $row['line_total_cost'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
