<x-app-layout pageTitle="Suppliers">
    @include('pos.partials.page-header', [
        'title' => 'Suppliers',
        'subtitle' => 'Manage your supplier network',
        'actions' => new \Illuminate\Support\HtmlString(
            '<button class="btn btn-success px-4" type="button" data-bs-toggle="collapse" data-bs-target="#supplierCreatePanel" aria-expanded="false" aria-controls="supplierCreatePanel"><i class="bi bi-plus-lg me-2"></i>Add Supplier</button>'
        ),
    ])

    <div class="collapse mb-4" id="supplierCreatePanel">
        @component('pos.partials.panel-card', [
            'title' => 'Add New Supplier',
            'subtitle' => 'Maintain a clean supplier directory with ready-to-use contact information.',
            'dismissLabel' => 'Close supplier form',
            'dismissTarget' => 'supplierCreatePanel',
        ])
            <form class="d-grid gap-4">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Supplier Name</label>
                        <input type="text" class="form-control">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Contact Number</label>
                        <input type="text" class="form-control" placeholder="Optional">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-success px-4">Save</button>
                    <button type="button" class="btn btn-light border px-4" data-bs-toggle="collapse" data-bs-target="#supplierCreatePanel" aria-controls="supplierCreatePanel">Cancel</button>
                </div>
            </form>
        @endcomponent
    </div>

    <div class="content-card">
        <div class="toolbar-row">
            <div class="toolbar-chip"><i class="bi bi-truck me-2"></i>{{ count($suppliers) }} active suppliers</div>
            <div class="toolbar-chip"><i class="bi bi-telephone me-2"></i>Directory ready for live supplier data</div>
        </div>

        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Supplier ID</th>
                        <th>Supplier Name</th>
                        <th>Contact Number</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr>
                            <td class="text-secondary">{{ $supplier['id'] }}</td>
                            <td class="fw-semibold">{{ $supplier['name'] }}</td>
                            <td>{{ $supplier['contact'] }}</td>
                            <td class="text-center">
                                @include('pos.partials.table-actions', [
                                    'edit' => 'supplierEdit'.$loop->index,
                                    'delete' => 'supplierDelete'.$loop->index,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="table-empty">No suppliers added yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($suppliers as $supplier)
        @component('pos.partials.modal', [
            'id' => 'supplierEdit'.$loop->index,
            'title' => 'Edit Supplier',
            'subtitle' => $supplier['id'].' | Update supplier information',
        ])
            <form class="d-grid gap-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Supplier Name</label>
                        <input type="text" class="form-control" value="{{ $supplier['name'] }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Contact Number</label>
                        <input type="text" class="form-control" value="{{ $supplier['contact'] }}">
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        @endcomponent

        @component('pos.partials.modal', [
            'id' => 'supplierDelete'.$loop->index,
            'title' => 'Delete Supplier',
            'subtitle' => 'This action removes the supplier from the current UI list.',
            'size' => 'modal-md',
        ])
            <p class="text-secondary mb-4">Delete <span class="fw-semibold text-dark">{{ $supplier['name'] }}</span>? Make sure this supplier is not linked to active stock-in records.</p>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger">Delete Supplier</button>
            </div>
        @endcomponent
    @endforeach
</x-app-layout>
