<x-pos-layout pageTitle="Sales (POS)">
    @php($receipt = session('receipt'))

    <form method="POST" action="{{ route('sales.store') }}" class="pos-terminal-card" data-pos-form>
        @csrf

        <div class="pos-terminal-top">
            <a href="{{ route('dashboard') }}" class="terminal-back-link" data-pos-exit>
                <i class="bi bi-box-arrow-left"></i>
                <span>Exit POS Mode</span>
            </a>

            <div class="terminal-mode">
                <div class="terminal-mode-icon">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div>
                    <h2 class="terminal-mode-title">{{ $terminalMeta['title'] }}</h2>
                    <p class="terminal-mode-subtitle mb-0">{{ $terminalMeta['subtitle'] }}</p>
                </div>
            </div>

            <div class="terminal-clock">
                <button type="button" class="btn btn-outline-success btn-sm mb-2" data-pos-fullscreen>
                    <i class="bi bi-fullscreen"></i>
                    <span>Fullscreen</span>
                </button>
                <div class="terminal-clock-time" data-pos-time>{{ $terminalMeta['time'] }}</div>
                <div class="terminal-clock-date" data-pos-date>{{ $terminalMeta['date'] }}</div>
            </div>
        </div>

        @error('items')
            <div class="alert alert-danger rounded-4 border-0">{{ $message }}</div>
        @enderror

        <div class="pos-terminal-grid">
            <section class="terminal-panel">
                <div class="terminal-panel-head">
                    <h3 class="terminal-panel-title">Products</h3>
                </div>

                <div class="terminal-search">
                    <i class="bi bi-search"></i>
                    <input type="search" class="terminal-search-input" placeholder="Search products..." data-pos-search>
                </div>

                <div class="terminal-product-list">
                    @forelse ($catalogItems as $item)
                        @php($status = $item->stock_status)
                        <button
                            type="button"
                            class="terminal-product-card terminal-product-button"
                            data-pos-product
                            data-product-id="{{ $item->product_id }}"
                            data-product-name="{{ $item->product_name }}"
                            data-product-category="{{ $item->category_name }}"
                            data-product-price="{{ (float) $item->product_price_per_kilo }}"
                            data-product-stock="{{ (float) ($item->current_stock ?? 0) }}"
                            @disabled((float) ($item->current_stock ?? 0) <= 0)
                        >
                            <div class="terminal-product-row">
                                <div>
                                    <h4 class="terminal-product-name">{{ $item->product_name }}</h4>
                                    <div class="terminal-product-category">{{ $item->category_name }}</div>
                                </div>
                                <span class="terminal-stock-badge {{ $status['class'] === 'success' ? 'terminal-stock-badge-success' : 'terminal-stock-badge-danger' }}">
                                    {{ $item->formatted_stock }}
                                </span>
                            </div>

                            <div class="terminal-product-price">{{ $item->formatted_price }}/kg</div>
                        </button>
                    @empty
                        <div class="terminal-empty-state">No products available.</div>
                    @endforelse
                </div>
            </section>

            <section class="terminal-panel">
                <div class="terminal-panel-head">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-cart3 text-success"></i>
                        <h3 class="terminal-panel-title mb-0">Shopping Cart</h3>
                        <span class="terminal-count-badge" data-pos-cart-count>0</span>
                    </div>
                </div>

                <div class="terminal-cart-list" data-pos-cart>
                    <div class="terminal-empty-state" data-pos-empty-cart>Cart is empty.</div>
                </div>

                <div class="terminal-subtotal-row">
                    <span>Subtotal</span>
                    <strong data-pos-subtotal>P0.00</strong>
                </div>
            </section>

            <section class="terminal-panel terminal-payment-panel">
                <div class="terminal-panel-head">
                    <h3 class="terminal-panel-title">Payment</h3>
                </div>

                <div class="terminal-field-group">
                    <label class="terminal-field-label">Customer</label>
                    <div class="terminal-field-box">Walk-In Customer</div>
                </div>

                <div class="terminal-summary-row">
                    <span>Subtotal</span>
                    <strong data-pos-subtotal>P0.00</strong>
                </div>

                <div class="terminal-highlight-box">
                    <span>Total Amount</span>
                    <strong data-pos-total>P0.00</strong>
                </div>

                <div class="terminal-field-group">
                    <label class="terminal-field-label" for="cash_received">Cash Received</label>
                    <input id="cash_received" name="cash_received" class="terminal-input-box" inputmode="decimal" value="{{ old('cash_received', '') }}" data-pos-cash>
                    @error('cash_received')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>

                <div class="terminal-cash-shortcuts">
                    @foreach ($cashShortcuts as $shortcut)
                        <button type="button" class="terminal-cash-button" data-pos-cash-shortcut="{{ $shortcut }}">P{{ $shortcut }}</button>
                    @endforeach
                </div>

                <div class="terminal-keypad-wrap">
                    <div class="terminal-field-label mb-2">Number Keys</div>
                    <div class="terminal-number-grid">
                        @foreach ($numberKeys as $key)
                            <button type="button" class="terminal-number-key {{ $key === 'CLR' ? 'terminal-number-key-clear' : '' }}" data-pos-key="{{ $key }}">
                                {{ $key }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="terminal-highlight-box terminal-highlight-box-soft">
                    <span>Change</span>
                    <strong data-pos-change>P0.00</strong>
                </div>

                <div data-pos-hidden-fields></div>

                <div class="terminal-action-stack">
                    <button type="submit" class="btn btn-success w-100" data-pos-submit disabled>Complete Sale</button>
                    <div class="terminal-inline-actions">
                        <button type="button" class="btn btn-light border w-100" data-pos-clear>Clear Cart</button>
                        <button type="button" class="btn btn-light border w-100" onclick="window.print()">Print Receipt</button>
                    </div>
                </div>

                @if ($receipt)
                    <div class="terminal-panel-head terminal-receipt-head">
                        <h3 class="terminal-panel-title">Latest Receipt</h3>
                    </div>

                    <div class="pos-receipt-card" data-pos-receipt>
                        <div class="pos-receipt-head">
                            <div>
                                <div class="pos-receipt-brand">SwineTrack POS</div>
                                <div class="pos-receipt-meta">Receipt #{{ $receipt['sale_id'] }} | {{ $receipt['sale_date'] }}</div>
                                <div class="pos-receipt-meta">Batch #{{ $receipt['batch_id'] }} | {{ $receipt['cashier'] }}</div>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" onclick="window.print()">
                                <i class="bi bi-printer"></i>
                                <span>Print Receipt</span>
                            </button>
                        </div>

                        <div class="pos-receipt-lines">
                            @foreach ($receipt['items'] as $item)
                                <div class="pos-receipt-line">
                                    <div>
                                        <div class="pos-receipt-item-name">{{ $item['product_name'] }}</div>
                                        <div class="pos-receipt-meta">{{ $item['qty_sold_kg'] }} kg x {{ $item['price_per_kg'] }}</div>
                                    </div>
                                    <strong>{{ $item['line_total'] }}</strong>
                                </div>
                            @endforeach
                        </div>

                        <div class="pos-receipt-total">
                            <span>Total</span>
                            <strong>{{ $receipt['total'] }}</strong>
                        </div>
                        <div class="pos-receipt-tender">
                            <span>Cash</span>
                            <strong>{{ $receipt['cash_received'] }}</strong>
                        </div>
                        <div class="pos-receipt-tender">
                            <span>Change</span>
                            <strong>{{ $receipt['change'] }}</strong>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </form>
</x-pos-layout>
