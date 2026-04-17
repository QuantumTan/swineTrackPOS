@php
    $pillClass = match ($type ?? 'neutral') {
        'success' => 'status-pill-success',
        'warning' => 'status-pill-warning',
        'primary' => 'status-pill-primary',
        'danger' => 'status-pill-danger',
        'info' => 'status-pill-info',
        default => 'status-pill-neutral',
    };
@endphp

<span class="status-pill {{ $pillClass }}">{{ $label }}</span>
