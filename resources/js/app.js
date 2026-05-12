import './bootstrap';

const formatters = {
    date: new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }),
    time: new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    }),
};

const updateTopbarClock = () => {
    const dateElement = document.querySelector('[data-current-date]');
    const timeElement = document.querySelector('[data-current-time]');

    if (!dateElement || !timeElement) {
        return;
    }

    const now = new Date();
    dateElement.textContent = formatters.date.format(now);
    timeElement.textContent = formatters.time.format(now);
};

updateTopbarClock();
window.setInterval(updateTopbarClock, 1000 * 30);

const setInvalidState = (element, isInvalid) => {
    if (!element) {
        return;
    }

    element.classList.toggle('is-invalid', isInvalid);
};

const createElement = (tagName, { className, textContent, attributes } = {}) => {
    const element = document.createElement(tagName);

    if (className) {
        element.className = className;
    }

    if (textContent !== undefined) {
        element.textContent = textContent;
    }

    Object.entries(attributes || {}).forEach(([name, value]) => {
        element.setAttribute(name, String(value));
    });

    return element;
};

const createTableCell = (attributeName, textContent) => createElement('td', {
    textContent,
    attributes: {
        [attributeName]: '',
    },
});

const createHiddenInput = (name, value) => createElement('input', {
    attributes: {
        type: 'hidden',
        name,
        value,
    },
});

const syncItemHiddenNames = (tbody) => {
    const rows = tbody.querySelectorAll('tr[data-item-row]');

    rows.forEach((row, index) => {
        const product = row.querySelector('input[data-item-hidden="product_id"]');
        const qty = row.querySelector('input[data-item-hidden="qty_in_kg"]');
        const cost = row.querySelector('input[data-item-hidden="cost_per_kg"]');

        if (product) {
            product.name = `items[${index}][product_id]`;
        }

        if (qty) {
            qty.name = `items[${index}][qty_in_kg]`;
        }

        if (cost) {
            cost.name = `items[${index}][cost_per_kg]`;
        }
    });

    const emptyRow = tbody.querySelector('[data-item-empty-row]');
    if (emptyRow) {
        emptyRow.style.display = rows.length === 0 ? '' : 'none';
    }
};

const createItemTableRow = ({ productId, productLabel, qty, cost }) => {
    const row = document.createElement('tr');
    row.setAttribute('data-item-row', '');

    const lineTotal = qty * cost;

    const actionCell = createElement('td', { className: 'text-center' });
    const removeButton = createElement('button', {
        className: 'btn btn-outline-danger btn-sm',
        textContent: 'Remove',
        attributes: {
            type: 'button',
            'data-item-remove': '',
        },
    });

    actionCell.appendChild(removeButton);

    row.append(
        createTableCell('data-item-label', productLabel),
        createTableCell('data-item-qty', qty.toFixed(3)),
        createTableCell('data-item-cost', `P${cost.toFixed(2)}`),
        createTableCell('data-item-total', `P${lineTotal.toFixed(2)}`),
        actionCell,
        createElement('input', {
            attributes: {
                type: 'hidden',
                'data-item-hidden': 'product_id',
                value: productId,
            },
        }),
        createElement('input', {
            attributes: {
                type: 'hidden',
                'data-item-hidden': 'qty_in_kg',
                value: qty,
            },
        }),
        createElement('input', {
            attributes: {
                type: 'hidden',
                'data-item-hidden': 'cost_per_kg',
                value: cost,
            },
        }),
    );

    return row;
};

document.querySelectorAll('form[data-item-composer]').forEach((form) => {
    const productInput = form.querySelector('[data-item-input-product]');
    const qtyInput = form.querySelector('[data-item-input-qty]');
    const costInput = form.querySelector('[data-item-input-cost]');
    const addButton = form.querySelector('[data-item-add]');
    const tbody = form.querySelector('[data-item-table-body]');

    if (!productInput || !qtyInput || !costInput || !addButton || !tbody) {
        return;
    }

    syncItemHiddenNames(tbody);

    addButton.addEventListener('click', () => {
        const productId = productInput.value;
        const qty = Number.parseFloat(qtyInput.value);
        const cost = Number.parseFloat(costInput.value);

        const hasProduct = Boolean(productId);
        const hasQty = Number.isFinite(qty) && qty > 0;
        const hasCost = Number.isFinite(cost) && cost > 0;

        setInvalidState(productInput, !hasProduct);
        setInvalidState(qtyInput, !hasQty);
        setInvalidState(costInput, !hasCost);

        if (!hasProduct || !hasQty || !hasCost) {
            return;
        }

        const selectedOption = productInput.options[productInput.selectedIndex];
        const productLabel = selectedOption ? selectedOption.textContent.trim() : 'Unknown Product';

        const row = createItemTableRow({
            productId,
            productLabel,
            qty,
            cost,
        });

        tbody.appendChild(row);
        syncItemHiddenNames(tbody);

        qtyInput.value = '';
        costInput.value = '';
        productInput.value = '';
        setInvalidState(productInput, false);
        setInvalidState(qtyInput, false);
        setInvalidState(costInput, false);
    });

    tbody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-item-remove]');

        if (!button) {
            return;
        }

        const row = button.closest('tr[data-item-row]');

        if (!row) {
            return;
        }

        row.remove();
        syncItemHiddenNames(tbody);
    });
});

const toggleSupplierField = (form) => {
    const sourceType = form.querySelector('select[name="source_type"]');
    const supplierField = form.querySelector('[data-supplier-field]');
    const supplierSelect = supplierField ? supplierField.querySelector('select[name="supplier_id"]') : null;

    if (!sourceType || !supplierField || !supplierSelect) {
        return;
    }

    const isOwnLivestock = sourceType.value === 'Own Livestock';

    supplierField.classList.toggle('d-none', isOwnLivestock);
    supplierSelect.disabled = isOwnLivestock;

    if (isOwnLivestock) {
        supplierSelect.value = '';
    }
};

document.querySelectorAll('form').forEach((form) => {
    const sourceType = form.querySelector('select[name="source_type"]');
    const supplierField = form.querySelector('[data-supplier-field]');

    if (!sourceType || !supplierField) {
        return;
    }

    toggleSupplierField(form);

    sourceType.addEventListener('change', () => {
        toggleSupplierField(form);
    });
});

const requestPosFullscreen = () => {
    const target = document.documentElement;

    if (!target.requestFullscreen || document.fullscreenElement) {
        return;
    }

    target.requestFullscreen().catch(() => {});
};

document.querySelectorAll('[data-pos-entry]').forEach((link) => {
    link.addEventListener('click', () => {
        requestPosFullscreen();
    });
});

document.querySelectorAll('[data-pos-fullscreen]').forEach((button) => {
    button.addEventListener('click', requestPosFullscreen);
});

document.querySelectorAll('[data-pos-exit]').forEach((link) => {
    link.addEventListener('click', () => {
        if (document.fullscreenElement && document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
        }
    });
});

const receiptElement = document.querySelector('[data-pos-receipt]');
if (receiptElement) {
    receiptElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const pesoFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
});

const posClockFormatter = {
    date: new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }),
    time: new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
    }),
};

document.querySelectorAll('[data-pos-form]').forEach((form) => {
    const cart = new Map();
    const cartElement = form.querySelector('[data-pos-cart]');
    const emptyCartElement = form.querySelector('[data-pos-empty-cart]');
    const countElement = form.querySelector('[data-pos-cart-count]');
    const hiddenFieldsElement = form.querySelector('[data-pos-hidden-fields]');
    const cashInput = form.querySelector('[data-pos-cash]');
    const submitButton = form.querySelector('[data-pos-submit]');
    const timeElement = form.querySelector('[data-pos-time]');
    const dateElement = form.querySelector('[data-pos-date]');

    const formatPeso = (amount) => pesoFormatter.format(amount).replace('PHP', 'P');
    const roundQty = (value) => Math.round(value * 1000) / 1000;
    const parseQtyValue = (value) => {
        const sanitized = String(value ?? '').trim().replace(',', '.');

        if (sanitized === '' || sanitized === '.') {
            return null;
        }

        const parsed = Number.parseFloat(sanitized);

        if (!Number.isFinite(parsed)) {
            return null;
        }

        return roundQty(parsed);
    };

    const total = () => Array.from(cart.values()).reduce((sum, item) => sum + item.qty * item.price, 0);

    const syncClock = () => {
        if (!timeElement || !dateElement) {
            return;
        }

        const now = new Date();
        timeElement.textContent = posClockFormatter.time.format(now);
        dateElement.textContent = posClockFormatter.date.format(now);
    };

    const renderCart = () => {
        cartElement.querySelectorAll('[data-pos-cart-item]').forEach((item) => item.remove());

        Array.from(cart.values()).forEach((item) => {
            const row = document.createElement('article');
            row.className = 'terminal-cart-card';
            row.setAttribute('data-pos-cart-item', item.id);

            const top = createElement('div', { className: 'terminal-cart-top' });
            const productCopy = document.createElement('div');
            productCopy.append(
                createElement('h4', {
                    className: 'terminal-product-name',
                    textContent: item.name,
                }),
                createElement('div', {
                    className: 'terminal-product-category',
                    textContent: `${formatPeso(item.price)}/kg`,
                }),
            );

            const removeButton = createElement('button', {
                className: 'terminal-trash-button',
                attributes: {
                    type: 'button',
                    'data-pos-remove': item.id,
                    'aria-label': `Remove ${item.name}`,
                },
            });
            removeButton.appendChild(createElement('i', { className: 'bi bi-trash' }));
            top.append(productCopy, removeButton);

            const bottom = createElement('div', { className: 'terminal-cart-bottom' });
            const qtyGroup = createElement('div', { className: 'terminal-qty-group' });
            qtyGroup.append(
                createElement('button', {
                    className: 'terminal-qty-button',
                    textContent: '-',
                    attributes: {
                        type: 'button',
                        'data-pos-decrement': item.id,
                    },
                }),
                createElement('input', {
                    className: 'terminal-qty-value terminal-qty-input',
                    attributes: {
                        inputmode: 'decimal',
                        min: '0.001',
                        max: item.stock,
                        value: item.qty.toFixed(3),
                        'data-pos-qty': item.id,
                    },
                }),
                createElement('button', {
                    className: 'terminal-qty-button',
                    textContent: '+',
                    attributes: {
                        type: 'button',
                        'data-pos-increment': item.id,
                    },
                }),
            );
            bottom.append(
                qtyGroup,
                createElement('div', {
                    className: 'terminal-line-total',
                    textContent: formatPeso(item.qty * item.price),
                }),
            );

            row.append(top, bottom);
            cartElement.appendChild(row);
        });

        const cartTotal = total();
        const cash = Number.parseFloat(cashInput.value || '0') || 0;
        const hasItems = cart.size > 0;

        emptyCartElement.style.display = hasItems ? 'none' : '';
        countElement.textContent = String(cart.size);

        form.querySelectorAll('[data-pos-subtotal], [data-pos-total]').forEach((element) => {
            element.textContent = formatPeso(cartTotal);
        });

        form.querySelector('[data-pos-change]').textContent = formatPeso(Math.max(cash - cartTotal, 0));
        submitButton.disabled = !hasItems || cash < cartTotal;

        hiddenFieldsElement.replaceChildren();
        Array.from(cart.values()).forEach((item, index) => {
            hiddenFieldsElement.append(
                createHiddenInput(`items[${index}][product_id]`, item.id),
                createHiddenInput(`items[${index}][qty_sold_kg]`, item.qty.toFixed(3)),
            );
        });
    };

    const addProduct = (button) => {
        const id = button.dataset.productId;
        const existing = cart.get(id);
        const stock = Number.parseFloat(button.dataset.productStock || '0');
        const nextQty = existing ? roundQty(existing.qty + 0.25) : 0.25;

        if (nextQty > stock) {
            return;
        }

        cart.set(id, {
            id,
            name: button.dataset.productName,
            category: button.dataset.productCategory,
            price: Number.parseFloat(button.dataset.productPrice || '0'),
            stock,
            qty: nextQty,
        });

        renderCart();
    };

    const commitQtyInput = (qtyInput, { removeWhenZero = false } = {}) => {
        const item = cart.get(qtyInput.dataset.posQty);

        if (!item) {
            return;
        }

        const qty = parseQtyValue(qtyInput.value);

        if (qty === null) {
            qtyInput.value = item.qty.toFixed(3);
            return;
        }

        // Allow 0 and negative numbers for SQL trigger testing
        if (removeWhenZero && qty === 0) {
            cart.delete(item.id);
            renderCart();
            return;
        }

        item.qty = Math.min(qty, item.stock);
        renderCart();
    };

    form.querySelectorAll('[data-pos-product]').forEach((button) => {
        button.addEventListener('click', () => addProduct(button));
    });

    form.addEventListener('click', (event) => {
        const target = event.target;
        const removeButton = target.closest('[data-pos-remove]');
        const incrementButton = target.closest('[data-pos-increment]');
        const decrementButton = target.closest('[data-pos-decrement]');

        if (removeButton) {
            cart.delete(removeButton.dataset.posRemove);
            renderCart();
        }

        if (incrementButton) {
            const item = cart.get(incrementButton.dataset.posIncrement);
            if (item && roundQty(item.qty + 0.25) <= item.stock) {
                item.qty = roundQty(item.qty + 0.25);
                renderCart();
            }
        }

        if (decrementButton) {
            const item = cart.get(decrementButton.dataset.posDecrement);
            if (item) {
                item.qty = roundQty(item.qty - 0.25);
                if (item.qty <= 0) {
                    cart.delete(item.id);
                }
                renderCart();
            }
        }
    });

    form.addEventListener('input', (event) => {
        const searchInput = event.target.closest('[data-pos-search]');

        if (searchInput) {
            const query = searchInput.value.trim().toLowerCase();
            form.querySelectorAll('[data-pos-product]').forEach((button) => {
                const haystack = `${button.dataset.productName} ${button.dataset.productCategory}`.toLowerCase();
                button.style.display = haystack.includes(query) ? '' : 'none';
            });
        }

        if (event.target === cashInput) {
            renderCart();
        }
    });

    form.addEventListener('change', (event) => {
        const qtyInput = event.target.closest('[data-pos-qty]');

        if (qtyInput) {
            commitQtyInput(qtyInput, { removeWhenZero: true });
        }
    });

    form.querySelectorAll('[data-pos-cash-shortcut]').forEach((button) => {
        button.addEventListener('click', () => {
            cashInput.value = button.dataset.posCashShortcut;
            renderCart();
        });
    });

    form.querySelectorAll('[data-pos-key]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.posKey;
            cashInput.value = key === 'CLR' ? '' : `${cashInput.value}${key}`;
            renderCart();
        });
    });

    form.querySelector('[data-pos-clear]').addEventListener('click', () => {
        cart.clear();
        renderCart();
    });

    form.addEventListener('submit', () => {
        form.querySelectorAll('[data-pos-qty]').forEach((qtyInput) => {
            const item = cart.get(qtyInput.dataset.posQty);
            const qty = parseQtyValue(qtyInput.value);

            if (!item || qty === null || qty <= 0) {
                return;
            }

            item.qty = Math.min(qty, item.stock);
        });

        renderCart();
    });

    syncClock();
    window.setInterval(syncClock, 1000);
    renderCart();
});
