<x-app-layout pageTitle="Dashboard">
    @include('pos.partials.page-header', [
        'title' => 'Dashboard',
        'subtitle' => 'Static dashboard for sales, stock, and receiving activity.',
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
                            <p class="section-subtitle mb-0">Daily totals and transactions shown as a static sales trend.</p>
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
                            <p class="section-subtitle mb-0">Products at or below the low-stock threshold.</p>
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
                            <h3 class="section-title mb-1">Stock Status Mix</h3>
                            <p class="section-subtitle mb-0">Status mix from the sample inventory snapshot.</p>
                        </div>
                    </div>

                    <div class="progress-list">
                        @foreach ($inventoryStatusGraph as $item)
                            <div class="progress-row">
                                <div class="progress-copy">
                                    <div class="fw-semibold">{{ $item['label'] }}</div>
                                    <div class="text-secondary small">{{ $item['count'] }} sample row(s)</div>
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
                            <h3 class="section-title mb-1">Batch Cost Comparison</h3>
                            <p class="section-subtitle mb-0">Line total cost comparison from the sample batch intake rows.</p>
                        </div>
                    </div>

                    <div class="progress-list">
                        @foreach ($batchCostGraph as $item)
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
        </div>
    </section>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Stock Snapshot',
                    'subtitle' => 'Sample rows from the inventory snapshot.',
                ])

                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($inventorySnapshot as $item)
                                <tr>
                                    <td class="text-secondary">{{ $item['product_id'] }}</td>
                                    <td class="fw-semibold">{{ $item['product_name'] }}</td>
                                    <td>{{ $item['current_stock'] }}</td>
                                    <td>
                                        @include('pos.partials.status-pill', [
                                            'label' => $item['stock_status']['label'],
                                            'type' => $item['stock_status']['class'],
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-7">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Receiving Cost Review',
                    'subtitle' => 'Sample intake lines for recent receiving activity.',
                ])

                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Source</th>
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
