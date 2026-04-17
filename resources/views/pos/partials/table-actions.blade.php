<div class="d-inline-flex align-items-center gap-1">
    @isset($view)
        <button
            type="button"
            class="btn btn-icon"
            data-bs-toggle="modal"
            data-bs-target="#{{ $view }}"
            aria-label="View details"
        >
            <i class="bi bi-eye text-success"></i>
        </button>
    @endisset

    @isset($edit)
        <button
            type="button"
            class="btn btn-icon"
            data-bs-toggle="modal"
            data-bs-target="#{{ $edit }}"
            aria-label="Edit item"
        >
            <i class="bi bi-pencil text-success"></i>
        </button>
    @endisset

    @isset($delete)
        <button
            type="button"
            class="btn btn-icon"
            data-bs-toggle="modal"
            data-bs-target="#{{ $delete }}"
            aria-label="Delete item"
        >
            <i class="bi bi-trash text-danger"></i>
        </button>
    @endisset
</div>
