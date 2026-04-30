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

    @php
        $supplierSummaryCards = [
            ['label' => 'Total Suppliers', 'value' => $supplierStats['total'], 'meta' => 'Full directory records', 'icon' => 'bi-buildings'],
            ['label' => 'Active Suppliers', 'value' => $supplierStats['active'], 'meta' => 'Available for new purchases', 'icon' => 'bi-check2-circle'],
            ['label' => 'Contact Ready', 'value' => $supplierStats['contact_ready'], 'meta' => 'Phone or email already saved', 'icon' => 'bi-person-lines-fill'],
            ['label' => 'With Delivery History', 'value' => $supplierStats['with_delivery_history'], 'meta' => 'Linked to stock-in batches', 'icon' => 'bi-clock-history'],
        ];
        $supplierHeaderAside = new \Illuminate\Support\HtmlString(
            '<div class="card-header-note"><i class="bi bi-lightning-charge me-2"></i>View keeps the full profile, while the table stays focused on the essentials.</div>'
        );
    @endphp

    <div class="row g-4 mb-4">
        @foreach ($supplierSummaryCards as $card)
            <div class="col-12 col-sm-6 col-xl-3">
                @include('pos.partials.summary-card', [
                    'label' => $card['label'],
                    'value' => $card['value'],
                    'meta' => $card['meta'],
                    'icon' => $card['icon'],
                ])
            </div>
        @endforeach
    </div>

    <div class="collapse mb-4 {{ $errors->any() ? 'show' : '' }}" id="supplierCreatePanel">
        @component('pos.partials.panel-card', [
            'title' => 'Add New Supplier',
            'subtitle' => 'Keep the supplier profile concise but complete enough for purchasing and follow-ups.',
            'dismissLabel' => 'Close supplier form',
            'dismissTarget' => 'supplierCreatePanel',
        ])
            <form class="d-grid gap-4" method="POST" action="{{ route('suppliers.store') }}">
                @csrf

                <div class="row g-3 supplier-form-grid">
                    <div class="col-12 col-xl-8">
                        <label class="form-label fw-semibold">Supplier Name</label>
                        <input
                            type="text"
                            name="supplier_name"
                            class="form-control @error('supplier_name') is-invalid @enderror"
                            value="{{ old('supplier_name') }}"
                            placeholder="Metro Cuts Trading"
                        >
                        @error('supplier_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-xl-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select
                            name="status"
                            class="form-select @error('status') is-invalid @enderror"
                        >
                            <option value="Active" @selected(old('status', 'Active') === 'Active')>Active</option>
                            <option value="Inactive" @selected(old('status') === 'Inactive')>Inactive</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold">Contact First Name</label>
                        <input
                            type="text"
                            name="contact_person_first_name"
                            class="form-control @error('contact_person_first_name') is-invalid @enderror"
                            value="{{ old('contact_person_first_name') }}"
                            placeholder="Ana"
                        >
                        @error('contact_person_first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold">Contact Last Name</label>
                        <input
                            type="text"
                            name="contact_person_last_name"
                            class="form-control @error('contact_person_last_name') is-invalid @enderror"
                            value="{{ old('contact_person_last_name') }}"
                            placeholder="Ramos"
                        >
                        @error('contact_person_last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold">Contact Number</label>
                        <input
                            type="text"
                            name="contact_number"
                            class="form-control @error('contact_number') is-invalid @enderror"
                            value="{{ old('contact_number') }}"
                            placeholder="09xx xxx xxxx"
                        >
                        @error('contact_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input
                            type="email"
                            name="email_address"
                            class="form-control @error('email_address') is-invalid @enderror"
                            value="{{ old('email_address') }}"
                            placeholder="buyer@supplier.com"
                        >
                        @error('email_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-8">
                        <label class="form-label fw-semibold">Business Address</label>
                        <input
                            type="text"
                            name="business_address"
                            class="form-control @error('business_address') is-invalid @enderror"
                            value="{{ old('business_address') }}"
                            placeholder="Barangay, city, province"
                        >
                        @error('business_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success px-4">Save Supplier</button>
                    <button type="button" class="btn btn-light border px-4" data-bs-toggle="collapse" data-bs-target="#supplierCreatePanel" aria-controls="supplierCreatePanel">Cancel</button>
                </div>
            </form>
        @endcomponent
    </div>

    <div class="content-card">
        @include('pos.partials.section-card-header', [
            'title' => 'Supplier Directory',
            'subtitle' => 'A simpler table layout for scanning suppliers quickly without losing the important details.',
            'aside' => $supplierHeaderAside,
        ])

        <div class="table-responsive">
            <table class="table app-table align-middle mb-0 supplier-table">
                <thead>
                    <tr>
                        <th>Supplier ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Deliveries</th>
                        <th>Last Delivery</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="text-secondary supplier-table-id">{{ $supplier->display_id }}</td>
                            <td>
                                <div class="supplier-table-name">{{ $supplier->supplier_name }}</div>
                            </td>
                            <td>{{ $supplier->contact_full_name ?: '-' }}</td>
                            <td>
                                @if ($supplier->contact_number)
                                    <a class="supplier-table-link" href="tel:{{ preg_replace('/\s+/', '', $supplier->contact_number) }}">{{ $supplier->contact_number }}</a>
                                @else
                                    <span class="supplier-cell-muted">-</span>
                                @endif
                            </td>
                            <td class="supplier-cell-secondary">{{ $supplier->email_address ?: '-' }}</td>
                            <td>
                                @include('pos.partials.status-pill', [
                                    'label' => $supplier->status,
                                    'type' => $supplier->status_type,
                                ])
                            </td>
                            <td class="fw-semibold">{{ $supplier->batches_count }}</td>
                            <td class="supplier-cell-secondary">{{ $supplier->formatted_last_delivery ?: '-' }}</td>
                            <td class="text-center supplier-table-actions">
                                @include('pos.partials.table-actions', [
                                    'view' => 'supplierView'.$supplier->supplier_id,
                                    'edit' => 'supplierEdit'.$supplier->supplier_id,
                                    'delete' => 'supplierDelete'.$supplier->supplier_id,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-empty">No suppliers added yet. Start by creating a supplier profile with contact and purchasing details.</td>
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
            'id' => 'supplierView'.$supplier->supplier_id,
            'title' => 'Supplier Profile',
            'subtitle' => $supplier->display_id.' | Read-only summary',
        ])
            <div class="supplier-modal-head mb-4">
                <div class="supplier-identity-copy">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h4 class="supplier-name">{{ $supplier->supplier_name }}</h4>
                        @include('pos.partials.status-pill', [
                            'label' => $supplier->status,
                            'type' => $supplier->status_type,
                        ])
                    </div>
                    <div class="supplier-meta-line">
                        <span class="supplier-id-text">{{ $supplier->display_id }}</span>
                        <span>{{ $supplier->contact_full_name ?: 'No contact person assigned' }}</span>
                    </div>
                </div>
            </div>

            <div class="supplier-modal-grid mb-3">
                <div class="supplier-detail-card">
                    <div class="modal-detail-label">Supplier Name</div>
                    <div class="fw-semibold">{{ $supplier->supplier_name }}</div>
                </div>
                <div class="supplier-detail-card">
                    <div class="modal-detail-label">Contact First Name</div>
                    <div class="fw-semibold">{{ $supplier->contact_person_first_name ?: '-' }}</div>
                </div>
                <div class="supplier-detail-card">
                    <div class="modal-detail-label">Contact Last Name</div>
                    <div class="fw-semibold">{{ $supplier->contact_person_last_name ?: '-' }}</div>
                </div>
                <div class="supplier-detail-card">
                    <div class="modal-detail-label">Contact Number</div>
                    <div class="fw-semibold">{{ $supplier->contact_number ?: '-' }}</div>
                </div>
                <div class="supplier-detail-card">
                    <div class="modal-detail-label">Email Address</div>
                    <div class="fw-semibold">{{ $supplier->email_address ?: '-' }}</div>
                </div>
                <div class="supplier-detail-card">
                    <div class="modal-detail-label">Delivery Count</div>
                    <div class="fw-semibold">{{ $supplier->batches_count }}</div>
                </div>
                <div class="supplier-detail-card">
                    <div class="modal-detail-label">Last Delivery</div>
                    <div class="fw-semibold">{{ $supplier->formatted_last_delivery ?: 'No delivery history yet.' }}</div>
                </div>
                <div class="supplier-detail-card">
                    <div class="modal-detail-label">Business Address</div>
                    <div class="fw-semibold">{{ $supplier->business_address ?: '-' }}</div>
                </div>
            </div>

        @endcomponent

        @component('pos.partials.modal', [
            'id' => 'supplierEdit'.$supplier->supplier_id,
            'title' => 'Edit Supplier',
            'subtitle' => $supplier->display_id.' | Update supplier information',
        ])
            <form class="d-grid gap-4" method="POST" action="{{ route('suppliers.update', $supplier) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-12 col-xl-8">
                        <label class="form-label fw-semibold">Supplier Name</label>
                        <input type="text" name="supplier_name" class="form-control" value="{{ $supplier->supplier_name }}">
                    </div>
                    <div class="col-12 col-xl-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" @selected($supplier->status === 'Active')>Active</option>
                            <option value="Inactive" @selected($supplier->status === 'Inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold">Contact First Name</label>
                        <input type="text" name="contact_person_first_name" class="form-control" value="{{ $supplier->contact_person_first_name }}">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold">Contact Last Name</label>
                        <input type="text" name="contact_person_last_name" class="form-control" value="{{ $supplier->contact_person_last_name }}">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold">Contact Number</label>
                        <input type="text" name="contact_number" class="form-control" value="{{ $supplier->contact_number }}">
                    </div>
                    <div class="col-12 col-lg-4">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email_address" class="form-control" value="{{ $supplier->email_address }}">
                    </div>
                    <div class="col-12 col-lg-8">
                        <label class="form-label fw-semibold">Business Address</label>
                        <input type="text" name="business_address" class="form-control" value="{{ $supplier->business_address }}">
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
