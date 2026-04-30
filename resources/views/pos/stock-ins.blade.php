<x-app-layout pageTitle="Stock-In">
    @php
        $canCreateStockIn = $canCreateStockIn ?? ($blockingBatch === null);
        $stockInActions = $canCreateStockIn
            ? new \Illuminate\Support\HtmlString(
                '<button class="btn btn-success px-4" type="button" data-bs-toggle="collapse" data-bs-target="#stockInCreatePanel" aria-expanded="false" aria-controls="stockInCreatePanel"><i class="bi bi-plus-lg me-2"></i>New Stock-In</button>')
            : new \Illuminate\Support\HtmlString(
                '<button class="btn btn-success px-4" type="button" disabled aria-disabled="true" title="Close or sell out the current batch before creating a new stock-in"><i class="bi bi-plus-lg me-2"></i>New Stock-In</button>');
    @endphp

    @include('pos.partials.page-header', [
        'title' => 'Stock-In',
        'subtitle' => 'Record incoming inventory',
        'actions' => $stockInActions,
    ])

    @if (session('status'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4">{{ session('status') }}</div>
    @endif

    @if ($errors->has('stock_in_delete'))
        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">{{ $errors->first('stock_in_delete') }}</div>
    @endif

    @if ($errors->has('stock_in_create'))
        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">{{ $errors->first('stock_in_create') }}</div>
    @endif

    @if (! $canCreateStockIn && $blockingBatch)
        <div class="alert alert-warning rounded-4 border-0 shadow-sm mb-4">
            <strong>Active Batch:</strong> Batch #{{ $blockingBatch->batch_id }} still has remaining quantity. 
            Please finish selling out or mark it as Closed before creating another stock-in.
        </div>
    @endif

    @php
        $stockInSummaryCards = count($summary)
            ? array_values($summary)
            : [
                ['label' => 'Today Entries', 'value' => '0', 'meta' => 'No receiving posted today'],
                ['label' => 'This Week Cost', 'value' => '0', 'meta' => 'Incoming cost total for the week'],
                ['label' => 'Primary Source', 'value' => 'N/A', 'meta' => 'Main receiving source so far'],
            ];
        $stockInSummaryIcons = ['bi-calendar-check', 'bi-cash-stack', 'bi-truck'];
    @endphp

    <div class="row g-4 mb-4">
        @foreach ($stockInSummaryCards as $index => $card)
            <div class="col-12 col-md-4">
                @include('pos.partials.summary-card', [
                    'label' => $card['label'],
                    'value' => $card['value'],
                    'meta' => $card['meta'] ?? null,
                    'icon' => $stockInSummaryIcons[$index] ?? 'bi-bar-chart-line',
                ])
            </div>
        @endforeach
    </div>

    <div class="collapse mb-4 {{ $canCreateStockIn && $errors->any() ? 'show' : '' }}" id="stockInCreatePanel">
        @component('pos.partials.panel-card', [
            'title' => 'Record Stock-In',
            'subtitle' => 'Capture delivery details and itemized incoming stock.',
            'dismissLabel' => 'Close stock-in form',
            'dismissTarget' => 'stockInCreatePanel',
        ])
            <form class="d-grid gap-4" method="POST" action="{{ route('stock-ins.store') }}" data-item-composer>
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Date</label>
                        <input
                            type="datetime-local"
                            name="batch_date"
                            class="form-control @error('batch_date') is-invalid @enderror"
                            value="{{ old('batch_date', now()->format('Y-m-d\TH:i')) }}"
                        >
                        @error('batch_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Source Type</label>
                        <select name="source_type" class="form-select @error('source_type') is-invalid @enderror">
                            <option value="Supplier" @selected(old('source_type', 'Supplier') === 'Supplier')>Supplier</option>
                            <option value="Own Livestock" @selected(old('source_type') === 'Own Livestock')>Own Livestock</option>
                        </select>
                        @error('source_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12" data-supplier-field>
                        <label class="form-label fw-semibold">Supplier</label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                            <option selected>Select a supplier</option>
                            @foreach ($activeSuppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}" @selected((string) old('supplier_id') === (string) $supplier->supplier_id)>{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="panel-divider"></div>

                <div>
                    <div class="fw-semibold mb-3">Items</div>
                    @php
                        $productMap = $products->keyBy('product_id');
                        $createItems = old('items', []);
                    @endphp

                    <div class="row g-3 align-items-end" data-item-entry>
                        <div class="col-12 col-lg-4">
                            <label class="form-label fw-semibold">Product</label>
                            <select class="form-select" data-item-input-product>
                                <option value="">Select product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->product_id }}">{{ $product->product_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label fw-semibold">Quantity (kg)</label>
                            <input type="number" step="0.001" class="form-control" placeholder="0.000" data-item-input-qty>
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label fw-semibold">Cost per kg (P)</label>
                            <input type="number" step="0.01" class="form-control" placeholder="0.00" data-item-input-cost>
                        </div>
                        <div class="col-12 col-lg-2">
                            <button type="button" class="btn btn-light border w-100" data-item-add>Add</button>
                        </div>
                    </div>

                    @if ($errors->has('items') || $errors->has('items.*.product_id') || $errors->has('items.*.qty_in_kg') || $errors->has('items.*.cost_per_kg'))
                        <div class="text-danger small mt-2">Please add at least one valid item before saving stock-in.</div>
                    @endif

                    <div class="table-responsive mt-3">
                        <table class="table app-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity (kg)</th>
                                    <th>Cost per kg</th>
                                    <th>Line Total</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody data-item-table-body>
                                @forelse ($createItems as $index => $item)
                                    @php
                                        $itemProductId = (int) ($item['product_id'] ?? 0);
                                        $itemQty = (float) ($item['qty_in_kg'] ?? 0);
                                        $itemCost = (float) ($item['cost_per_kg'] ?? 0);
                                        $itemName = $productMap->get($itemProductId)?->product_name ?? 'Unknown Product';
                                    @endphp
                                    <tr data-item-row>
                                        <td data-item-label>{{ $itemName }}</td>
                                        <td data-item-qty>{{ number_format($itemQty, 3) }}</td>
                                        <td data-item-cost>P{{ number_format($itemCost, 2) }}</td>
                                        <td data-item-total>P{{ number_format($itemQty * $itemCost, 2) }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm" data-item-remove>Remove</button>
                                        </td>
                                        <input type="hidden" value="{{ $itemProductId }}" data-item-hidden="product_id">
                                        <input type="hidden" value="{{ $itemQty }}" data-item-hidden="qty_in_kg">
                                        <input type="hidden" value="{{ $itemCost }}" data-item-hidden="cost_per_kg">
                                    </tr>
                                @empty
                                    <tr data-item-empty-row>
                                        <td colspan="5" class="table-empty">No items added yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel-divider"></div>

                @if ($errors->has('stock_in_create'))
                    <div class="text-danger small">{{ $errors->first('stock_in_create') }}</div>
                @endif

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success px-4">Save Stock-In</button>
                    <button type="button" class="btn btn-light border px-4" data-bs-toggle="collapse"
                        data-bs-target="#stockInCreatePanel" aria-controls="stockInCreatePanel">Cancel</button>
                </div>
            </form>
        @endcomponent
    </div>

    <section class="content-card">
        @include('pos.partials.section-card-header', [
            'title' => 'Stock-In Records',
            'subtitle' => 'Review recent inventory arrivals and trace each incoming batch.',
        ])
        <form method="GET" action="{{ route('stock-ins.index') }}" class="row g-3 m-2">
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold">Search</label>
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    value="{{ $filters['search'] }}"
                    placeholder="Batch ID or supplier name"
                >
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold">Source</label>
                <select name="source_type" class="form-select">
                    <option value="">All sources</option>
                    <option value="Supplier" @selected($filters['source_type'] === 'Supplier')>Supplier</option>
                    <option value="Own Livestock" @selected($filters['source_type'] === 'Own Livestock')>Own Livestock</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label fw-semibold">Batch Status</label>
                <select name="batch_status" class="form-select">
                    <option value="">All statuses</option>
                    <option value="open" @selected($filters['batch_status'] === 'open')>Open</option>
                    <option value="sold_out" @selected($filters['batch_status'] === 'sold_out')>Sold Out</option>
                    <option value="closed" @selected($filters['batch_status'] === 'closed')>Closed</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-success w-100">Apply</button>
                <a href="{{ route('stock-ins.index') }}" class="btn btn-light border w-100">Clear</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Stock-In ID</th>
                        <th>Date</th>
                        <th>Batch Status</th>
                        <th>Source Type</th>
                        <th>Supplier</th>
                        <th>Items</th>
                        <th>Total Qty</th>
                        <th>Total Cost</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockIns as $stockIn)
                        <tr>
                            <td class="fw-semibold">{{ $stockIn->display_id }}</td>
                            <td>{{ $stockIn->formatted_date }}</td>
                            <td>
                                @include('pos.partials.status-pill', [
                                    'label' => $stockIn->status_presentation['label'],
                                    'type' => $stockIn->status_presentation['class'],
                                ])
                            </td>
                            <td>
                                @include('pos.partials.status-pill', [
                                    'label' => $stockIn->source_presentation['label'],
                                    'type' => $stockIn->source_presentation['class'],
                                ])
                            </td>
                            <td>{{ $stockIn->supplier_display }}</td>
                            <td>{{ $stockIn->items_count }}</td>
                            <td class="fw-semibold">{{ $stockIn->formatted_total_qty }}</td>
                            <td class="fw-semibold">{{ $stockIn->formatted_total_cost }}</td>
                            <td class="text-center">
                                @include('pos.partials.table-actions', [
                                    'view' => 'stockInView' . $stockIn->batch_id,
                                    'edit' => 'stockInEdit' . $stockIn->batch_id,
                                    'delete' => 'stockInDelete' . $stockIn->batch_id,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-empty">No stock-in records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($stockIns->hasPages())
            <div class="d-flex justify-content-end mt-3">
                {{ $stockIns->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>

    @foreach ($stockIns as $stockIn)
        @component('pos.partials.modal', [
            'id' => 'stockInView' . $stockIn->batch_id,
            'title' => 'Stock-In Details',
            'subtitle' => $stockIn->display_id . ' | ' . $stockIn->formatted_date,
        ])
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="modal-detail-label">Batch Status</div>
                    @include('pos.partials.status-pill', [
                        'label' => $stockIn->status_presentation['label'],
                        'type' => $stockIn->status_presentation['class'],
                    ])
                </div>
                <div class="col-md-6">
                    <div class="modal-detail-label">Source Type</div>
                    @include('pos.partials.status-pill', [
                        'label' => $stockIn->source_presentation['label'],
                        'type' => $stockIn->source_presentation['class'],
                    ])
                </div>
                <div class="col-md-6">
                    <div class="modal-detail-label">Supplier</div>
                    <div class="fw-semibold">{{ $stockIn->supplier_display }}</div>
                </div>
                <div class="col-md-6">
                    <div class="modal-detail-label">Total Cost</div>
                    <div class="fw-semibold">{{ $stockIn->formatted_total_cost }}</div>
                </div>
                <div class="col-md-6">
                    <div class="modal-detail-label">Items Received</div>
                    <div class="fw-semibold">{{ $stockIn->items_count }} item{{ $stockIn->items_count === 1 ? '' : 's' }} / {{ $stockIn->formatted_total_qty }}</div>
                </div>
                <div class="col-12">
                    <div class="modal-detail-label">Items</div>
                    <div class="d-grid gap-2">
                        @foreach ($stockIn->items as $item)
                            <div class="d-flex justify-content-between align-items-center rounded-3 border px-3 py-2">
                                <div>
                                    <div class="fw-semibold">{{ $item->product_display_name }}</div>
                                    <div class="text-secondary small">{{ $item->formatted_qty }} kg at {{ $item->formatted_cost }}/kg</div>
                                </div>
                                <div class="fw-semibold">{{ $item->formatted_line_total }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="modal-detail-label">Remarks</div>
                    <div class="text-secondary">Recorded stock-in details.</div>
                </div>
            </div>
        @endcomponent

        @component('pos.partials.modal', [
            'id' => 'stockInEdit' . $stockIn->batch_id,
            'title' => 'Edit Stock-In',
            'subtitle' => 'Adjust source details and incoming items.',
        ])
            <form class="d-grid gap-3" method="POST" action="{{ route('stock-ins.update', $stockIn) }}" data-item-composer>
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Batch Status</label>
                        <select name="batch_status" class="form-select">
                            @foreach (\App\Enums\BatchStatus::manualValues() as $batchStatus)
                                <option value="{{ $batchStatus }}" @selected(old('batch_status', $stockIn->manual_status_presentation['value']) === $batchStatus)>{{ $batchStatus }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">"Sold Out" is automatic when all batch quantities reach zero. Use "Closed" only for a manual stop.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="datetime-local" name="batch_date" class="form-control" value="{{ old('batch_date', $stockIn->batch_date->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Source Type</label>
                        <select name="source_type" class="form-select">
                            <option value="Supplier" @selected(old('source_type', $stockIn->source_presentation['label']) === 'Supplier')>Supplier</option>
                            <option value="Own Livestock" @selected(old('source_type', $stockIn->source_presentation['label']) === 'Own Livestock')>Own Livestock</option>
                        </select>
                    </div>
                    <div class="col-12" data-supplier-field>
                        <label class="form-label fw-semibold">Supplier</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">Select a supplier</option>
                            @foreach ($selectableSuppliers as $supplier)
                                <option value="{{ $supplier->supplier_id }}" @selected((string) old('supplier_id', $stockIn->supplier_id) === (string) $supplier->supplier_id)>{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold mb-2">Items</label>
                        <div class="row g-3 align-items-end" data-item-entry>
                            <div class="col-md-4">
                                <select class="form-select" data-item-input-product>
                                    <option value="">Select product</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->product_id }}">{{ $product->product_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" step="0.001" class="form-control" placeholder="0.000" data-item-input-qty>
                            </div>
                            <div class="col-md-3">
                                <input type="number" step="0.01" class="form-control" placeholder="0.00" data-item-input-cost>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-light border w-100" data-item-add>Add</button>
                            </div>
                        </div>

                        <div class="table-responsive mt-3">
                            <table class="table app-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity (kg)</th>
                                        <th>Cost per kg</th>
                                        <th>Line Total</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody data-item-table-body>
                                    @foreach ($stockIn->items as $item)
                                        <tr data-item-row>
                                            <td data-item-label>{{ $item->product_display_name }}</td>
                                            <td data-item-qty>{{ $item->formatted_qty }}</td>
                                            <td data-item-cost>{{ $item->formatted_cost }}</td>
                                            <td data-item-total>{{ $item->formatted_line_total }}</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-danger btn-sm" data-item-remove>Remove</button>
                                            </td>
                                            <input type="hidden" value="{{ $item->product_id }}" data-item-hidden="product_id">
                                            <input type="hidden" value="{{ $item->qty_value }}" data-item-hidden="qty_in_kg">
                                            <input type="hidden" value="{{ $item->cost_value }}" data-item-hidden="cost_per_kg">
                                        </tr>
                                    @endforeach
                                    @if ($stockIn->items->count() === 0)
                                        <tr data-item-empty-row>
                                            <td colspan="5" class="table-empty">No items added yet.</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        @endcomponent

        @component('pos.partials.modal', [
            'id' => 'stockInDelete' . $stockIn->batch_id,
            'title' => 'Delete Stock-In Record',
            'subtitle' => 'This action removes the current stock-in entry from the list.',
            'size' => 'modal-md',
        ])
            <p class="text-secondary mb-4">Delete <span class="fw-semibold text-dark">{{ $stockIn->display_id }}</span>? Review
                inventory impact before removing this record.</p>
            <form method="POST" action="{{ route('stock-ins.destroy', $stockIn) }}" class="d-flex justify-content-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete Record</button>
            </form>
        @endcomponent
    @endforeach

</x-app-layout>
