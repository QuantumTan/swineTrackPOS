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

    row.innerHTML = `
        <td data-item-label>${productLabel}</td>
        <td data-item-qty>${qty.toFixed(3)}</td>
        <td data-item-cost>P${cost.toFixed(2)}</td>
        <td data-item-total>P${lineTotal.toFixed(2)}</td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" data-item-remove>Remove</button>
        </td>
        <input type="hidden" data-item-hidden="product_id" value="${productId}">
        <input type="hidden" data-item-hidden="qty_in_kg" value="${qty}">
        <input type="hidden" data-item-hidden="cost_per_kg" value="${cost}">
    `;

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
