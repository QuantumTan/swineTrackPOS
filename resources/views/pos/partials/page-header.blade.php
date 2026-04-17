<div class="page-header d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="page-title mb-1">{{ $title }}</h2>
        @if (! empty($subtitle))
            <p class="page-subtitle mb-0">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="page-actions">
            {{ $actions }}
        </div>
    @endisset
</div>
