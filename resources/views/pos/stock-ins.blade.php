<x-app-layout pageTitle="Stock-In">
    @include('pos.partials.page-header', [
        'title' => 'Stock-In',
        'subtitle' => 'Record incoming inventory',
        'actions' => new \Illuminate\Support\HtmlString(
            '<button class="btn btn-success px-4" type="button" data-bs-toggle="collapse" data-bs-target="#stockInCreatePanel" aria-expanded="false" aria-controls="stockInCreatePanel"><i class="bi bi-plus-lg me-2"></i>New Stock-In</button>'),
    ])

    <div class="row g-4 mb-4">
        @if (count($summary))
            @foreach ($summary as $card)
                <div class="col-12 col-md-4">
                    <div class="stat-card compact">
                        <div class="stat-label">{{ $card['label'] }}</div>
                        <div class="stat-value">{{ $card['value'] }}</div>
                    </div>
                </div>
            @endforeach
        @else
            @foreach ([['label' => 'Today Entries', 'value' => '0'], ['label' => 'This Week Cost', 'value' => '0'], ['label' => 'Primary Source', 'value' => 'N/A']] as $card)
                <div class="col-12 col-md-4">
                    <div class="stat-card compact">
                        <div class="stat-label">{{ $card['label'] }}</div>
                        <div class="stat-value">{{ $card['value'] }}</div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="collapse mb-4" id="stockInCreatePanel">
        @component('pos.partials.panel-card', [
            'title' => 'Record Stock-In',
            'subtitle' => 'Capture delivery details and itemized incoming stock.',
            'dismissLabel' => 'Close stock-in form',
            'dismissTarget' => 'stockInCreatePanel',
        ])
            <form class="d-grid gap-4">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="text" class="form-control" value="15/04/2026">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Source Type</label>
                        <select class="form-select">
                            <option>Supplier</option>
                            <option>Own Livestock</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Supplier</label>
                        <select class="form-select">
                            <option selected>Select a supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option>{{ $supplier }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="panel-divider"></div>

                <div>
                    <div class="fw-semibold mb-3">Items</div>
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-4">
                            <label class="form-label fw-semibold">Product</label>
                            <select class="form-select">
                                <option selected>Select product</option>
                                @foreach ($stockInProducts as $product)
                                    <option>{{ $product }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label fw-semibold">Quantity (kg)</label>
                            <input type="number" class="form-control" placeholder="0.000">
                        </div>
                        <div class="col-12 col-lg-3">
                            <label class="form-label fw-semibold">Cost per kg (P)</label>
                            <input type="number" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-12 col-lg-2">
                            <button type="button" class="btn btn-light border w-100">Add Item</button>
                        </div>
                    </div>
                </div>

                <div class="panel-divider"></div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-success px-4">Save Stock-In</button>
                    <button type="button" class="btn btn-light border px-4" data-bs-toggle="collapse"
                        data-bs-target="#stockInCreatePanel" aria-controls="stockInCreatePanel">Cancel</button>
                </div>
            </form>
        @endcomponent
    </div>

    <section class="content-card">
        <div class="card-header-clean">
            <div>
                <h3 class="section-title mb-1">Stock-In Records</h3>
                <p class="section-subtitle mb-0">Review recent inventory arrivals and trace each incoming batch.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Stock-In ID</th>
                        <th>Date</th>
                        <th>Source Type</th>
                        <th>Supplier</th>
                        <th>Total Cost</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockIns as $stockIn)
                        <tr>
                            <td class="fw-semibold">{{ $stockIn['id'] }}</td>
                            <td>{{ $stockIn['date'] }}</td>
                            <td>
                                @include('pos.partials.status-pill', [
                                    'label' => $stockIn['source']['label'],
                                    'type' => $stockIn['source']['class'],
                                ])
                            </td>
                            <td>{{ $stockIn['supplier'] }}</td>
                            <td class="fw-semibold">{{ $stockIn['total'] }}</td>
                            <td class="text-center">
                                @include('pos.partials.table-actions', [
                                    'view' => 'stockInView' . $loop->index,
                                    'edit' => 'stockInEdit' . $loop->index,
                                    'delete' => 'stockInDelete' . $loop->index,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty">No stock-in records yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @foreach ($stockIns as $stockIn)
        @component('pos.partials.modal', [
            'id' => 'stockInView' . $loop->index,
            'title' => 'Stock-In Details',
            'subtitle' => $stockIn['id'] . ' | ' . $stockIn['date'],
        ])
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="modal-detail-label">Source Type</div>
                    @include('pos.partials.status-pill', [
                        'label' => $stockIn['source']['label'],
                        'type' => $stockIn['source']['class'],
                    ])
                </div>
                <div class="col-md-6">
                    <div class="modal-detail-label">Supplier</div>
                    <div class="fw-semibold">{{ $stockIn['supplier'] }}</div>
                </div>
                <div class="col-md-6">
                    <div class="modal-detail-label">Total Cost</div>
                    <div class="fw-semibold">{{ $stockIn['total'] }}</div>
                </div>
                <div class="col-md-6">
                    <div class="modal-detail-label">Remarks</div>
                    <div class="text-secondary">Ready for inventory posting.</div>
                </div>
            </div>
        @endcomponent

        @component('pos.partials.modal', [
            'id' => 'stockInEdit' . $loop->index,
            'title' => 'Edit Stock-In',
            'subtitle' => 'Adjust source details and incoming items.',
        ])
            <form class="d-grid gap-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="text" class="form-control" value="{{ $stockIn['date'] }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Source Type</label>
                        <select class="form-select">
                            <option @selected($stockIn['source']['label'] === 'Supplier')>Supplier</option>
                            <option @selected($stockIn['source']['label'] === 'Own Livestock')>Own Livestock</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Supplier</label>
                        <select class="form-select">
                            @foreach ($suppliers as $supplier)
                                <option @selected($supplier === $stockIn['supplier'])>{{ $supplier }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        @endcomponent

        @component('pos.partials.modal', [
            'id' => 'stockInDelete' . $loop->index,
            'title' => 'Delete Stock-In Record',
            'subtitle' => 'This action removes the current stock-in entry from the list.',
            'size' => 'modal-md',
        ])
            <p class="text-secondary mb-4">Delete <span class="fw-semibold text-dark">{{ $stockIn['id'] }}</span>? Review
                inventory impact before removing this record.</p>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger">Delete Record</button>
            </div>
        @endcomponent
    @endforeach
</x-app-layout>
