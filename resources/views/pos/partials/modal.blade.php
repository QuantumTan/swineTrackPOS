<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog {{ $size ?? 'modal-lg' }} modal-dialog-centered">
        <div class="modal-content app-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h4 class="modal-title fw-bold">{{ $title }}</h4>
                    @if (! empty($subtitle))
                        <p class="section-subtitle mb-0 mt-1">{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
