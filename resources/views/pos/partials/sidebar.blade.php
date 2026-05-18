@php
    $navigationGroups = [
        [
            'label' => null,
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'bi-grid-1x2'],
            ],
        ],
        [
            'label' => 'Operations',
            'items' => [
                // ['label' => 'Sales (POS)', 'route' => 'sales.index', 'icon' => 'bi-cart3'],
                ['label' => 'Stock-In', 'route' => 'stock-ins.index', 'icon' => 'bi-arrow-down-circle'],
            ],
        ],
        [
            'label' => 'Management',
            'items' => [
                ['label' => 'Products', 'route' => 'products.index', 'icon' => 'bi-box-seam'],
                ['label' => 'Categories', 'route' => 'categories.index', 'icon' => 'bi-tags'],
                // ['label' => 'Suppliers', 'route' => 'suppliers.index', 'icon' => 'bi-truck'],
            ],
        ],
        [
            'label' => 'Monitoring',
            'items' => [
                ['label' => 'Inventory', 'route' => 'inventory.index', 'icon' => 'bi-archive'],
                ['label' => 'Reports', 'route' => 'reports.index', 'icon' => 'bi-file-earmark-text'],
                // ['label' => 'Sales Activity', 'route' => 'reports.sales-activity', 'icon' => 'bi-receipt'],
            ],
        ],
    ];
@endphp

<div class="app-sidebar h-100 d-flex flex-column">
    <div class="sidebar-brand px-4 py-4 border-bottom">
        <a href="{{ route('dashboard') }}" class="text-decoration-none">
            <span class="brand-mark">SwineTrack POS</span>
        </a>
    </div>

    <div class="sidebar-scroll-area flex-grow-1 overflow-auto px-3 py-4">
        @foreach ($navigationGroups as $group)
            @if ($group['label'])
                <div class="sidebar-label px-3 mb-2">{{ $group['label'] }}</div>
            @endif

            <nav class="nav flex-column gap-1 mb-4">
                @foreach ($group['items'] as $item)
                    <a href="{{ route($item['route']) }}"
                       @if ($item['route'] === 'sales.index') data-pos-entry @endif
                       class="nav-link app-nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endforeach
    </div>

    <div class="border-top p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link app-logout w-100 text-start text-decoration-none">
                <i class="bi bi-box-arrow-left me-2"></i>
                Logout
            </button>
        </form>
    </div>
</div>
