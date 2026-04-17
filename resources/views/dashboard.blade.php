<x-app-layout pageTitle="Dashboard">
    @include('pos.partials.page-header', [
        'title' => 'Dashboard',
        'subtitle' => 'This module is intentionally blank for now.',
    ])

    <section class="content-card">
        <div class="p-4 p-lg-5">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-grid-1x2"></i>
                </div>
                <h3 class="section-title mb-2">Dashboard placeholder..</h3>
            </div>
        </div>
    </section>
</x-app-layout>
