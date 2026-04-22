<div class="summary-card h-100 {{ $class ?? '' }}">
    <div class="summary-card-top">
        <div class="summary-copy">
            <div class="summary-label">{{ $label }}</div>
            <div class="summary-value">{{ $value }}</div>
        </div>

        @if (! empty($icon))
            <div class="summary-icon">
                <i class="bi {{ $icon }}"></i>
            </div>
        @endif
    </div>

    @if (! empty($meta))
        <div class="summary-meta">{{ $meta }}</div>
    @endif
</div>
