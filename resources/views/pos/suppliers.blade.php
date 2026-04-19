<x-app-layout pageTitle="Suppliers">
    @include('pos.partials.page-header', [
        'title' => 'Suppliers',
        'subtitle' => 'Manage your supplier network',
        'actions' => new \Illuminate\Support\HtmlString(
            '<button class="btn btn-success px-4" type="button" data-bs-toggle="collapse" data-bs-target="#supplierCreatePanel" aria-expanded="'.($errors->any() ? 'true' : 'false').'" aria-controls="supplierCreatePanel"><i class="bi bi-plus-lg me-2"></i>Add Supplier</button>'
        ),
    ])

    @if (session('status'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4">{{ session('status') }}</div>
    @endif

    @if ($errors->has('supplier_delete'))
        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">{{ $errors->first('supplier_delete') }}</div>
    @endif

    <div class="collapse mb-4 {{ $errors->any() ? 'show' : '' }}" id="supplierCreatePanel">
        @component('pos.partials.panel-card', [
            'title' => 'Add New Supplier',
            'subtitle' => 'Maintain a clean supplier directory with ready-to-use contact information.',
            'dismissLabel' => 'Close supplier form',
            'dismissTarget' => 'supplierCreatePanel',
        ])
            <form class="d-grid gap-4" method="POST" action="{{ route('suppliers.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Supplier Name</label>
                        <input
                            type="text"
                            name="supplier_name"
                            class="form-control @error('supplier_name') is-invalid @enderror"
                            value="{{ old('supplier_name') }}"
                        >
                        @error('supplier_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Contact Number</label>
                        <input
                            type="text"
                            name="supplier_phone_number"
                            class="form-control @error('supplier_phone_number') is-invalid @enderror"
                            value="{{ old('supplier_phone_number') }}"
                            placeholder="Optional"
                        >
                        @error('supplier_phone_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success px-4">Save</button>
                    <button type="button" class="btn btn-light border px-4" data-bs-toggle="collapse" data-bs-target="#supplierCreatePanel" aria-controls="supplierCreatePanel">Cancel</button>
                </div>
            </form>
        @endcomponent
    </div>

    <div class="content-card">
        <div class="toolbar-row">
            <div class="toolbar-chip"><i class="bi bi-truck me-2"></i>{{ $supplierStats['total'] }} active suppliers</div>
            <div class="toolbar-chip"><i class="bi bi-telephone me-2"></i>{{ $supplierStats['with_phone'] }} contact numbers saved</div>
            <div class="toolbar-chip"><i class="bi bi-box-arrow-in-down me-2"></i>{{ $supplierStats['with_delivery_history'] }} suppliers with delivery history</div>
        </div>

        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Supplier ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Number</th>
                        <th>Deliveries</th>
                        <th>Last Delivery</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="text-secondary">S{{ str_pad((string) $supplier->supplier_id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="fw-semibold">{{ $supplier->supplier_name }}</td>
                            <td>{{ $supplier->supplier_phone_number ?: '-' }}</td>
                            <td>{{ $supplier->batches_count }}</td>
                            <td>
                                {{ $supplier->batches_max_batch_date ? \Illuminate\Support\Carbon::parse($supplier->batches_max_batch_date)->format('d M Y, h:i A') : '-' }}
                            </td>
                            <td class="text-center">
                                @include('pos.partials.table-actions', [
                                    'edit' => 'supplierEdit'.$supplier->supplier_id,
                                    'delete' => 'supplierDelete'.$supplier->supplier_id,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty">No suppliers added yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($suppliers->hasPages())
            <div class="px-4 pb-4 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="text-secondary small">
                    Showing {{ $suppliers->firstItem() }} to {{ $suppliers->lastItem() }} of {{ $suppliers->total() }} suppliers
                </div>
                {{ $suppliers->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </div>

    @foreach ($suppliers as $supplier)
        @component('pos.partials.modal', [
            'id' => 'supplierEdit'.$supplier->supplier_id,
            'title' => 'Edit Supplier',
            'subtitle' => 'S'.str_pad((string) $supplier->supplier_id, 3, '0', STR_PAD_LEFT).' | Update supplier information',
        ])
            <form class="d-grid gap-3" method="POST" action="{{ route('suppliers.update', $supplier) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Supplier Name</label>
                        <input type="text" name="supplier_name" class="form-control" value="{{ $supplier->supplier_name }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Contact Number</label>
                        <input type="text" name="supplier_phone_number" class="form-control" value="{{ $supplier->supplier_phone_number }}">
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        @endcomponent

        @component('pos.partials.modal', [
            'id' => 'supplierDelete'.$supplier->supplier_id,
            'title' => 'Delete Supplier',
            'subtitle' => 'This action permanently removes the supplier record.',
            'size' => 'modal-md',
        ])
            <p class="text-secondary mb-4">Delete <span class="fw-semibold text-dark">{{ $supplier->supplier_name }}</span>? Make sure this supplier is not linked to active stock-in records.</p>
            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="d-flex justify-content-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete Supplier</button>
            </form>
        @endcomponent
    @endforeach
</x-app-layout>
