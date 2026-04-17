<section class="content-card panel-card {{ $class ?? '' }}">
    <div class="panel-card-header d-flex align-items-start justify-content-between gap-3">
        <div>
            <h3 class="section-title mb-1">{{ $title }}</h3>
            @if (! empty($subtitle))
                <p class="section-subtitle mb-0">{{ $subtitle }}</p>
            @endif
        </div>

        @if (! empty($dismissLabel))
            <button
                type="button"
                class="btn btn-panel-close"
                aria-label="{{ $dismissLabel }}"
                @if (! empty($dismissTarget))
                    data-bs-toggle="collapse"
                    data-bs-target="#{{ $dismissTarget }}"
                    aria-controls="{{ $dismissTarget }}"
                @endif
            >
                <i class="bi bi-x-lg"></i>
            </button>
        @endif
    </div>

    <div class="panel-card-body">
        {{ $slot }}
    </div>
</section>
