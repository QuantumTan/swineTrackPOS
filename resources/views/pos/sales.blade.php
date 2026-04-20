<x-app-layout pageTitle="Sales (POS)">
    <section class="pos-terminal-card">
        <div class="pos-terminal-top">
            <a href="{{ route('dashboard') }}" class="terminal-back-link">
                <i class="bi bi-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>

            <div class="terminal-mode">
                <div class="terminal-mode-icon">
                    <i class="bi bi-list"></i>
                </div>
                <div>
                    <h2 class="terminal-mode-title">{{ str_replace('â€”', '-', $terminalMeta['title']) }}</h2>
                    <p class="terminal-mode-subtitle mb-0">{{ $terminalMeta['subtitle'] }}</p>
                </div>
            </div>

            <div class="terminal-clock">
                <div class="terminal-clock-time">{{ $terminalMeta['time'] }}</div>
                <div class="terminal-clock-date">{{ $terminalMeta['date'] }}</div>
            </div>
        </div>

        <div class="pos-terminal-grid">
            <section class="terminal-panel">
                <div class="terminal-panel-head">
                    <h3 class="terminal-panel-title">Products</h3>
                </div>

                <div class="terminal-search">
                    <i class="bi bi-search"></i>
                    <input type="text" class="terminal-search-input" placeholder="Search products..." readonly>
                </div>

                <div class="terminal-product-list">
                    @foreach ($catalogItems as $item)
                        <article class="terminal-product-card">
                            <div class="terminal-product-row">
                                <div>
                                    <h4 class="terminal-product-name">{{ $item['product_name'] }}</h4>
                                    <div class="terminal-product-category">{{ $item['category'] }}</div>
                                </div>
                                <span class="terminal-stock-badge {{ $item['status']['class'] === 'danger' ? 'terminal-stock-badge-danger' : 'terminal-stock-badge-success' }}">
                                    {{ $item['stock'] }}
                                </span>
                            </div>

                            <div class="terminal-product-price">{{ $item['price_per_kg'] }}/kg</div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="terminal-panel">
                <div class="terminal-panel-head">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cart3 text-success"></i>
                        <h3 class="terminal-panel-title mb-0">Shopping Cart</h3>
                        <span class="terminal-count-badge">{{ count($cartItems) }}</span>
                    </div>
                </div>

                <div class="terminal-cart-list">
                    @foreach ($cartItems as $item)
                        <article class="terminal-cart-card">
                            <div class="terminal-cart-top">
                                <div>
                                    <h4 class="terminal-product-name">{{ $item['product_name'] }}</h4>
                                    <div class="terminal-product-category">{{ $item['price_per_kg'] }}</div>
                                </div>
                                <button type="button" class="terminal-trash-button">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>

                            <div class="terminal-cart-bottom">
                                <div class="terminal-qty-group">
                                    <button type="button" class="terminal-qty-button">-</button>
                                    <div class="terminal-qty-value">{{ $item['qty_display'] }}</div>
                                    <button type="button" class="terminal-qty-button">+</button>
                                </div>
                                <div class="terminal-line-total">{{ $item['line_total'] }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="terminal-subtotal-row">
                    <span>Subtotal</span>
                    <strong>{{ $payment['subtotal'] }}</strong>
                </div>
            </section>

            <section class="terminal-panel">
                <div class="terminal-panel-head">
                    <h3 class="terminal-panel-title">Payment</h3>
                </div>

                <div class="terminal-field-group">
                    <label class="terminal-field-label">Customer</label>
                    <div class="terminal-field-box">{{ $payment['customer'] }}</div>
                </div>

                <div class="terminal-summary-row">
                    <span>Subtotal</span>
                    <strong>{{ $payment['subtotal'] }}</strong>
                </div>

                <div class="terminal-highlight-box">
                    <span>Total Amount</span>
                    <strong>{{ $payment['total'] }}</strong>
                </div>

                <div class="terminal-field-group">
                    <label class="terminal-field-label">Cash Received</label>
                    <div class="terminal-input-box">{{ $payment['cash_received'] }}</div>
                </div>

                <div class="terminal-cash-shortcuts">
                    @foreach ($cashShortcuts as $shortcut)
                        <button type="button" class="terminal-cash-button">{{ $shortcut }}</button>
                    @endforeach
                </div>

                <div class="terminal-keypad-wrap">
                    <div class="terminal-field-label mb-2">Number Keys</div>
                    <div class="terminal-number-grid">
                        @foreach ($numberKeys as $key)
                            <button type="button" class="terminal-number-key {{ $key === 'CLR' ? 'terminal-number-key-clear' : '' }}">
                                {{ $key }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="terminal-highlight-box terminal-highlight-box-soft">
                    <span>Change</span>
                    <strong>{{ $payment['change'] }}</strong>
                </div>

                <div class="terminal-action-stack">
                    <button type="button" class="btn btn-success w-100">Complete Sale</button>
                    <div class="terminal-inline-actions">
                        <button type="button" class="btn btn-light border w-100">Clear Cart</button>
                        <button type="button" class="btn btn-light border w-100">Print Receipt</button>
                    </div>
                </div>
            </section>
        </div>
    </section>
</x-app-layout>
