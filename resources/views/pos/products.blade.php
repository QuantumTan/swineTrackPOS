<x-app-layout pageTitle="Products">
    @include('pos.partials.page-header', [
        'title' => 'Products',
        'subtitle' => 'Manage your product catalog',
        'actions' => new \Illuminate\Support\HtmlString(
            '<button class="btn btn-success px-4" type="button" data-bs-toggle="collapse" data-bs-target="#productCreatePanel" aria-expanded="false" aria-controls="productCreatePanel"><i class="bi bi-plus-lg me-2"></i>Add Product</button>'
        ),
    ])

    <div class="collapse mb-4" id="productCreatePanel">
        @component('pos.partials.panel-card', [
            'title' => 'Add New Product',
            'subtitle' => 'Keep catalog details complete and pricing accurate.',
            'dismissLabel' => 'Close product form',
            'dismissTarget' => 'productCreatePanel',
        ])
            <form class="d-grid gap-4">
                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Product Name</label>
                        <input type="text" class="form-control">
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Category</label>
                        <select class="form-select">
                            <option selected>Select category</option>
                            @foreach ($categories as $category)
                                <option>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">Price per kg (P)</label>
                        <input type="number" class="form-control" placeholder="0.00">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-success px-4">Save</button>
                    <button type="button" class="btn btn-light border px-4" data-bs-toggle="collapse" data-bs-target="#productCreatePanel" aria-controls="productCreatePanel">Cancel</button>
                </div>
            </form>
        @endcomponent
    </div>

    <div class="content-card">
        <div class="toolbar-row">
            <div class="toolbar-chip"><i class="bi bi-grid me-2"></i>{{ count($products) }} total products</div>
            <div class="toolbar-chip"><i class="bi bi-exclamation-circle me-2"></i>Catalog ready for live product data</div>
        </div>

        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price per kg</th>
                        <th>Current Stock</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td class="text-secondary">{{ $product['id'] }}</td>
                            <td class="fw-semibold">{{ $product['name'] }}</td>
                            <td>{{ $product['category'] }}</td>
                            <td class="fw-semibold">{{ $product['price'] }}</td>
                            <td>
                                @include('pos.partials.status-pill', [
                                    'label' => $product['stock']['value'],
                                    'type' => $product['stock']['class'],
                                ])
                            </td>
                            <td class="text-center">
                                @include('pos.partials.table-actions', [
                                    'edit' => 'productEdit'.$loop->index,
                                    'delete' => 'productDelete'.$loop->index,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-empty">No products available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($products as $product)
        @component('pos.partials.modal', [
            'id' => 'productEdit'.$loop->index,
            'title' => 'Edit Product',
            'subtitle' => $product['id'].' | Update pricing and classification',
        ])
            <form class="d-grid gap-3">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Product Name</label>
                        <input type="text" class="form-control" value="{{ $product['name'] }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Category</label>
                        <select class="form-select">
                            @foreach ($categories as $category)
                                <option @selected($category === $product['category'])>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Price per kg (P)</label>
                        <input type="text" class="form-control" value="{{ str_replace('P', '', $product['price']) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Current Stock</label>
                        <input type="text" class="form-control" value="{{ $product['stock']['value'] }}">
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        @endcomponent

        @component('pos.partials.modal', [
            'id' => 'productDelete'.$loop->index,
            'title' => 'Delete Product',
            'subtitle' => 'This removes the product from the catalog UI.',
            'size' => 'modal-md',
        ])
            <p class="text-secondary mb-4">Delete <span class="fw-semibold text-dark">{{ $product['name'] }}</span>? Existing inventory and sales references should be reviewed first.</p>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger">Delete Product</button>
            </div>
        @endcomponent
    @endforeach
</x-app-layout>
