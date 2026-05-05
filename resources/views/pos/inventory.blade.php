<x-app-layout pageTitle="Inventory">
    @include('pos.partials.page-header', [
        'title' => 'Inventory',
        'subtitle' => 'Current stock levels',
    ])

    @if (session('status'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4">{{ session('status') }}</div>
    @endif

    @php
        $inventorySummaryCards = count($summary)
            ? array_values($summary)
            : [
                ['label' => 'Total Products', 'value' => '0', 'meta' => 'Tracked catalog items'],
                ['label' => 'In Stock', 'value' => '0', 'meta' => 'Products ready for selling'],
                ['label' => 'Low Stock / Out', 'value' => '0', 'meta' => 'Items needing attention'],
            ];
        $inventorySummaryIcons = ['bi-box-seam', 'bi-check2-circle', 'bi-exclamation-triangle'];
    @endphp

    <div class="row g-4 mb-4">
        @foreach ($inventorySummaryCards as $index => $card)
            <div class="col-12 col-md-4">
                @include('pos.partials.summary-card', [
                    'label' => $card['label'],
                    'value' => $card['value'],
                    'meta' => $card['meta'] ?? null,
                    'icon' => $inventorySummaryIcons[$index] ?? 'bi-bar-chart-line',
                ])
            </div>
        @endforeach
    </div>

    <section class="content-card">
        @include('pos.partials.section-card-header', [
            'title' => 'Inventory Snapshot',
            'subtitle' => 'Detailed stock positions and the most recent update time for each product.',
        ])
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-3 m-2">
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold">Search</label>
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    value="{{ $filters['search'] }}"
                    placeholder="Product, supplier, or ID"
                >
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->category_id }}" @selected((string) $filters['category_id'] === (string) $category->category_id)>{{ $category->category_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold">Stock</label>
                <select name="stock_status" class="form-select">
                    <option value="">All stock levels</option>
                    <option value="in_stock" @selected($filters['stock_status'] === 'in_stock')>In Stock</option>
                    <option value="low_stock" @selected($filters['stock_status'] === 'low_stock')>Low Stock</option>
                    <option value="out_of_stock" @selected($filters['stock_status'] === 'out_of_stock')>Out of Stock</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-success w-100">Apply</button>
                <a href="{{ route('inventory.index') }}" class="btn btn-light border w-100">Clear</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Stock Status</th>
                        <th>Last Updated</th>
                        <th>Latest Stock-In Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventoryItems as $item)
                        <tr>
                            <td class="text-secondary">{{ $item->display_id }}</td>
                            <td class="fw-semibold">{{ $item->product_name }}</td>
                            <td>{{ $item->category_name }}</td>
                            <td class="fw-semibold">{{ $item->formatted_stock }}</td>
                            <td>
                                @include('pos.partials.status-pill', [
                                    'label' => $item->stock_status['label'],
                                    'type' => $item->stock_status['class'],
                                ])
                            </td>
                            <td>{{ $item->formatted_last_updated }}</td>
                            <td>{{ $item->latest_supplier_display }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-empty">No inventory records available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($inventoryItems->hasPages())
            <div class="d-flex justify-content-end mt-3">
                {{ $inventoryItems->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
</x-app-layout>
