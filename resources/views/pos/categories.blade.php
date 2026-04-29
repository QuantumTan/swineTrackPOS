<x-app-layout pageTitle="Categories">
    @include('pos.partials.page-header', [
        'title' => 'Categories',
        'subtitle' => 'Manage product classifications',
        'actions' => new \Illuminate\Support\HtmlString(
            '<button class="btn btn-success px-4" type="button" data-bs-toggle="collapse" data-bs-target="#categoryCreatePanel" aria-expanded="false" aria-controls="categoryCreatePanel"><i class="bi bi-plus-lg me-2"></i>Add Category</button>'
        ),
    ])

    @if (session('status'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4">{{ session('status') }}</div>
    @endif

    @if ($errors->has('category_delete'))
        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">{{ $errors->first('category_delete') }}</div>
    @endif

    @php
        $categorySummaryCards = [
            ['label' => 'Total Categories', 'value' => $categoryStats['total'], 'meta' => 'Product classification records', 'icon' => 'bi-tags'],
            ['label' => 'With Products', 'value' => $categoryStats['with_products'], 'meta' => 'Currently assigned in catalog', 'icon' => 'bi-box-seam'],
            ['label' => 'Empty', 'value' => $categoryStats['empty'], 'meta' => 'Available but unused', 'icon' => 'bi-inboxes'],
        ];
    @endphp

    <div class="row g-4 mb-4">
        @foreach ($categorySummaryCards as $card)
            <div class="col-12 col-md-4">
                @include('pos.partials.summary-card', [
                    'label' => $card['label'],
                    'value' => $card['value'],
                    'meta' => $card['meta'],
                    'icon' => $card['icon'],
                ])
            </div>
        @endforeach
    </div>

    <div class="collapse mb-4 {{ $errors->any() && ! $errors->has('category_delete') ? 'show' : '' }}" id="categoryCreatePanel">
        @component('pos.partials.panel-card', [
            'title' => 'Add New Category',
            'subtitle' => 'Create a category that products can reference by category_id.',
            'dismissLabel' => 'Close category form',
            'dismissTarget' => 'categoryCreatePanel',
        ])
            <form class="d-grid gap-4" method="POST" action="{{ route('categories.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-lg-5">
                        <label class="form-label fw-semibold">Category Name</label>
                        <input
                            type="text"
                            name="category_name"
                            class="form-control @error('category_name') is-invalid @enderror"
                            value="{{ old('category_name') }}"
                            placeholder="Premium Cuts"
                        >
                        @error('category_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 col-lg-7">
                        <label class="form-label fw-semibold">Description</label>
                        <input
                            type="text"
                            name="category_description"
                            class="form-control @error('category_description') is-invalid @enderror"
                            value="{{ old('category_description') }}"
                            placeholder="Short internal description"
                        >
                        @error('category_description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-success px-4">Save Category</button>
                    <button type="button" class="btn btn-light border px-4" data-bs-toggle="collapse" data-bs-target="#categoryCreatePanel" aria-controls="categoryCreatePanel">Cancel</button>
                </div>
            </form>
        @endcomponent
    </div>

    <section class="content-card">
        @include('pos.partials.section-card-header', [
            'title' => 'Category Directory',
            'subtitle' => 'Categories are referenced by products and used throughout catalog, stock, and sales views.',
        ])

        <div class="table-responsive">
            <table class="table app-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Category ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="text-secondary">C{{ str_pad((string) $category->category_id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td class="fw-semibold">{{ $category->category_name }}</td>
                            <td>{{ $category->category_description ?: '-' }}</td>
                            <td class="fw-semibold">{{ $category->products_count }}</td>
                            <td class="text-center">
                                @include('pos.partials.table-actions', [
                                    'edit' => 'categoryEdit'.$category->category_id,
                                    'delete' => 'categoryDelete'.$category->category_id,
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-empty">No categories available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="d-flex justify-content-end mt-3">
                {{ $categories->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>

    @foreach ($categories as $category)
        @component('pos.partials.modal', [
            'id' => 'categoryEdit'.$category->category_id,
            'title' => 'Edit Category',
            'subtitle' => 'C'.str_pad((string) $category->category_id, 3, '0', STR_PAD_LEFT).' | Update category details',
        ])
            <form class="d-grid gap-3" method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Category Name</label>
                        <input type="text" name="category_name" class="form-control" value="{{ old('category_name', $category->category_name) }}">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="category_description" class="form-control" value="{{ old('category_description', $category->category_description) }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        @endcomponent

        @component('pos.partials.modal', [
            'id' => 'categoryDelete'.$category->category_id,
            'title' => 'Delete Category',
            'subtitle' => 'Categories with assigned products are protected.',
            'size' => 'modal-md',
        ])
            <p class="text-secondary mb-4">Delete <span class="fw-semibold text-dark">{{ $category->category_name }}</span>? This only works when no products reference this category.</p>
            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="d-flex justify-content-end gap-2">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete Category</button>
            </form>
        @endcomponent
    @endforeach
</x-app-layout>
