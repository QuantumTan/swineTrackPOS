@php
    $userEmail = auth()->user()->user_email ?? 'staff@swinetrack.com';
    $userName = 'Staff User';
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($userEmail, 0, 1));
@endphp

<header class="topbar border-bottom bg-white">
    <div class="container-fluid px-3 px-lg-4">
        <div class="d-flex align-items-center justify-content-between gap-3 py-3">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <h1 class="page-shell-title mb-0">SwineTrack POS</h1>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3 gap-lg-4">
                <div class="text-end d-none d-md-block">
                    <div class="topbar-date" data-current-date>{{ now()->format('l, F j, Y') }}</div>
                    <div class="topbar-time" data-current-time>{{ now()->format('g:i A') }}</div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-circle">{{ $initial }}</div>
                    <div class="d-none d-sm-block">
                        <div class="fw-semibold text-dark-emphasis">{{ $userName }}</div>
                        <div class="text-secondary small">{{ $userEmail }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
