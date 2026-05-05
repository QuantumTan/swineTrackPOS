<x-app-layout pageTitle="Sales Activity">
    @include('pos.partials.page-header', [
        'title' => 'Sales Activity',
        'subtitle' => 'Recent sold items, payments, and cashier activity.',
        'actions' => new \Illuminate\Support\HtmlString(
            '<a class="btn btn-light border px-4" href="'.route('reports.index').'"><i class="bi bi-arrow-left me-2"></i>Reports</a>'
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

    <section class="content-card">
        @include('pos.partials.section-card-header', [
            'title' => 'Sales Activity Ledger',
            'subtitle' => 'Recent sold items with cashier, batch, quantity, price, and computed line total.',
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
                        <th>Price / kg</th>
                        <th>Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salesDetails as $row)
                        <tr>
                            <td class="text-secondary">{{ $row['sale_id'] }}</td>
                            <td>{{ $row['sale_date'] }}</td>
                            <td>{{ $row['batch_id'] }}</td>
                            <td>{{ $row['user_email'] }}</td>
                            <td class="fw-semibold">{{ $row['product_name'] }}</td>
                            <td>{{ $row['qty_sold_kg'] }}</td>
                            <td>{{ $row['price_per_kg'] }}</td>
                            <td class="fw-semibold">{{ $row['line_total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-4" colspan="8">No sales activity has been recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
