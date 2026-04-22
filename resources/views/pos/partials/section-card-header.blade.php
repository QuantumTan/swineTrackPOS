<div class="card-header-clean {{ $class ?? '' }}">
    <div class="section-header-row">
        <div class="section-header-copy">
            <h3 class="section-title mb-1">{{ $title }}</h3>

            @if (! empty($subtitle))
                <p class="section-subtitle mb-0">{{ $subtitle }}</p>
            @endif
        </div>

        @if (! empty($aside))
            <div class="section-header-aside">
                {!! $aside !!}
            </div>
        @endif
    </div>
</div>
