<x-app-layout pageTitle="Inventory">
    @include('pos.partials.page-header', [
        'title' => 'Inventory',
        'subtitle' => 'Current stock levels',
    ])

    @if (session('status'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4">{{ session('status') }}</div>
    @endif

    <div class="row g-4 mb-4">
        @if (count($summary))
            @foreach ($summary as $card)
                <div class="col-12 col-md-4">
                    <div class="summary-card h-100">
                        <div class="summary-label">{{ $card['label'] }}</div>
                        <div class="summary-value">{{ $card['value'] }}</div>
                    </div>
                </div>
            @endforeach
        @else
            @foreach (['Total Products', 'In Stock', 'Low Stock / Out'] as $label)
                <div class="col-12 col-md-4">
                    <div class="summary-card h-100">
                        <div class="summary-label">{{ $label }}</div>
                        <div class="summary-value">0</div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <section class="content-card">
        <div class="card-header-clean">
            <div>
                <h3 class="section-title mb-1">Inventory Snapshot</h3>
                <p class="section-subtitle mb-0">Detailed stock positions and the most recent update time for each product.</p>
            </div>
        </div>
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
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventoryItems as $item)
                        <tr>
                            <td class="text-secondary">{{ $item['id'] }}</td>
                            <td class="fw-semibold">{{ $item['name'] }}</td>
                            <td>{{ $item['category'] }}</td>
                            <td class="fw-semibold">{{ $item['stock'] }}</td>
                            <td>
                                @include('pos.partials.status-pill', [
                                    'label' => $item['status']['label'],
                                    'type' => $item['status']['class'],
                                ])
                            </td>
                            <td>{{ $item['updated'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty">No inventory records available yet.</td>
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
