@props([
    'pageTitle' => 'Dashboard',
    'pageSubtitle' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle }} | {{ config('app.name', 'SwineTrack POS') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-body">
        <div class="app-shell">
            <aside class="sidebar-shell d-none d-lg-block">
                @include('pos.partials.sidebar')
            </aside>

            <div class="offcanvas offcanvas-start sidebar-offcanvas" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
                <div class="offcanvas-body p-0">
                    @include('pos.partials.sidebar')
                </div>
            </div>

            <div class="content-shell">
                @include('pos.partials.topbar')

                <main class="app-main px-3 px-lg-4 py-4">
                    @if (session('error'))
                        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">{{ session('error') }}</div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
