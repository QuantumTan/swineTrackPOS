<x-app-layout pageTitle="Dashboard">
    @include('pos.partials.page-header', [
        'title' => 'Dashboard',
        'subtitle' => 'Operational overview for sales, stock, and receiving activity.',
        'actions' => new \Illuminate\Support\HtmlString(
            '<div class="d-flex flex-wrap gap-2"><a class="btn btn-success px-4" href="'.route('sales.index').'"><i class="bi bi-cart3 me-2"></i>New Sale</a><a class="btn btn-light border px-4" href="'.route('reports.index').'"><i class="bi bi-file-earmark-text me-2"></i>View Reports</a></div>'
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
                    'tone' => $card['tone'] ?? 'green',
                ])
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-4">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Action Center',
                    'subtitle' => 'Fast paths for daily store work.',
                ])

                <div class="dashboard-action-list p-4 pt-3">
                    <a class="dashboard-action" href="{{ route('sales.index') }}">
                        <span><i class="bi bi-cart3"></i></span>
                        <div>
                            <div class="fw-semibold">Open POS Terminal</div>
                            <div class="text-secondary small">Start a walk-in sale.</div>
                        </div>
                    </a>
                    <a class="dashboard-action" href="{{ route('stock-ins.index') }}">
                        <span><i class="bi bi-arrow-down-circle"></i></span>
                        <div>
                            <div class="fw-semibold">Record Stock-In</div>
                            <div class="text-secondary small">Add receiving quantities.</div>
                        </div>
                    </a>
                    <a class="dashboard-action" href="{{ route('inventory.index') }}">
                        <span><i class="bi bi-archive"></i></span>
                        <div>
                            <div class="fw-semibold">Review Inventory</div>
                            <div class="text-secondary small">Check live stock levels.</div>
                        </div>
                    </a>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Stock Alerts',
                    'subtitle' => 'Products that need attention soon.',
                ])

                <div class="p-4 pt-3 d-grid gap-3">
                    @forelse ($lowStockProducts as $product)
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
                    @empty
                        <div class="report-empty">No low-stock products.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Inventory Health',
                    'subtitle' => 'Current stock status across products.',
                ])

                <div class="p-4 pt-3">
                    <div class="donut-chart-wrap dashboard-donut-wrap">
                        <div class="donut-chart" style="--segments: {{ $inventoryStatusMix['gradient'] }};">
                            <div class="donut-center">
                                <div class="donut-value">{{ collect($inventoryStatusMix['segments'])->sum('count') }}</div>
                                <div class="donut-label">Products</div>
                            </div>
                        </div>
                        <div class="donut-legend">
                            @forelse ($inventoryStatusMix['segments'] as $segment)
                                <div class="donut-legend-row">
                                    <span class="donut-dot" style="--dot-color: {{ $segment['color'] }};"></span>
                                    <span class="fw-semibold">{{ $segment['label'] }}</span>
                                    <span class="text-secondary small">{{ $segment['count'] }}</span>
                                </div>
                            @empty
                                <div class="report-empty">No inventory rows yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Recent Sales',
                    'subtitle' => 'Latest completed sales transactions.',
                ])

                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Sale ID</th>
                                <th>Date & Time</th>
                                <th>Items</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentTransactions as $row)
                                <tr>
                                    <td class="text-secondary">{{ $row['sale_id'] }}</td>
                                    <td>{{ $row['sale_date'] }}</td>
                                    <td>{{ $row['item_count'] }} item(s)</td>
                                    <td class="fw-semibold">{{ $row['amount'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-secondary py-4" colspan="4">No recent sales available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="content-card h-100">
                @include('pos.partials.section-card-header', [
                    'title' => 'Receiving Review',
                    'subtitle' => 'Recent stock-in receiving activity.',
                ])

                <div class="table-responsive">
                    <table class="table app-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Batch</th>
                                <th>Product</th>
                                <th>Qty In</th>
                                <th>Line Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($batchDetails as $row)
                                <tr>
                                    <td class="text-secondary">{{ $row['batch_id'] }}</td>
                                    <td class="fw-semibold">{{ $row['product_name'] }}</td>
                                    <td>{{ $row['qty_in_kg'] }}</td>
                                    <td class="fw-semibold">{{ $row['line_total_cost'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-center text-secondary py-4" colspan="4">No receiving rows available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
