<x-app-layout pageTitle="Sales Activity">
    @include('pos.partials.page-header', [
        'title' => 'Sales Activity',
        'subtitle' => 'Recent sold items, payments, and cashier activity.',
        'actions' => new \Illuminate\Support\HtmlString(
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

        <form method="GET" action="{{ route('reports.sales-activity') }}" class="row g-3 m-2">
            <input type="hidden" name="payment_search" value="{{ $filters['payment_search'] }}">
            <input type="hidden" name="payment_status" value="{{ $filters['payment_status'] }}">
            <input type="hidden" name="payment_date_from" value="{{ $filters['payment_date_from'] }}">
            <input type="hidden" name="payment_date_to" value="{{ $filters['payment_date_to'] }}">

            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Search</label>
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    value="{{ $filters['search'] }}"
                    placeholder="Sale ID, batch, user, product"
                >
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold">Category</label>
                <select name="category" class="form-select">
                    <option value="">All categories</option>
                    @foreach ($salesCategories as $category)
                        <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold">From</label>
                <input name="date_from" type="date" class="form-control" value="{{ $filters['date_from'] }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold">To</label>
                <input name="date_to" type="date" class="form-control" value="{{ $filters['date_to'] }}">
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-success w-100">Apply</button>
                <a href="{{ route('reports.sales-activity') }}" class="btn btn-light border w-100">Clear</a>
            </div>
        </form>
{{-- uses view payment salary as a source of data --}}
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
                            <td class="text-center text-secondary py-4" colspan="8">No sales activity matches the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3">
            {{ $salesDetails->links('pagination::bootstrap-5') }}
        </div>
    </section>

    <section class="content-card mt-4">
        @include('pos.partials.section-card-header', [
            'title' => 'Payment Summary',
            'subtitle' => 'Payment status, amount, item count, and total quantity sold by transaction.',
        ])

        <form method="GET" action="{{ route('reports.sales-activity') }}" class="row g-3 m-2">
            <input type="hidden" name="search" value="{{ $filters['search'] }}">
            <input type="hidden" name="category" value="{{ $filters['category'] }}">
            <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
            <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">

            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold">Search</label>
                <input
                    type="text"
                    name="payment_search"
                    class="form-control"
                    value="{{ $filters['payment_search'] }}"
                    placeholder="Sale ID, batch, user"
                >
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold">Status</label>
                <select name="payment_status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="paid" @selected($filters['payment_status'] === 'paid')>Paid</option>
                    <option value="pending" @selected($filters['payment_status'] === 'pending')>Pending</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold">From</label>
                <input name="payment_date_from" type="date" class="form-control" value="{{ $filters['payment_date_from'] }}">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold">To</label>
                <input name="payment_date_to" type="date" class="form-control" value="{{ $filters['payment_date_to'] }}">
            </div>
            <div class="col-12 col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-success w-100">Apply</button>
                <a href="{{ route('reports.sales-activity', [
                    'search' => $filters['search'],
                    'category' => $filters['category'],
                    'date_from' => $filters['date_from'],
                    'date_to' => $filters['date_to'],
                ]) }}" class="btn btn-light border w-100">Clear</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sale ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Items</th>
                        <th>Total Qty Sold</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paymentSummary as $row)
                        <tr>
                            <td class="text-secondary">{{ $row['sale_id'] }}</td>
                            <td>{{ $row['sale_date'] }}</td>
                            <td class="text-capitalize">{{ $row['payment_status'] }}</td>
                            <td class="fw-semibold">{{ $row['amount'] }}</td>
                            <td>{{ $row['item_count'] }} item(s)</td>
                            <td>{{ $row['total_qty_sold_kg'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center text-secondary py-4" colspan="6">No payment summary rows available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3">
            {{ $paymentSummary->links('pagination::bootstrap-5') }}
        </div>
    </section>
</x-app-layout>
