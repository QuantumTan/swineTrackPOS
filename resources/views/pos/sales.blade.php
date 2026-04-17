<x-app-layout pageTitle="Sales (POS)">
    @include('pos.partials.page-header', [
        'title' => 'Sales (POS)',
        'subtitle' => 'This module is intentionally blank for now.',
    ])

    <section class="content-card">
        <div class="p-4 p-lg-5">
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-cart3"></i>
                </div>
                <h3 class="section-title mb-2">Sales placeholder....</h3>
            </div>
        </div>
    </section>
</x-app-layout>
