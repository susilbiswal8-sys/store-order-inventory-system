import './bootstrap';

const state = {
    products: [],
    lowStock: [],
};

const money = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

const qs = (selector) => document.querySelector(selector);

const showNotice = (message, type = 'success') => {
    const notice = qs('#notice');
    notice.textContent = message;
    notice.className = `notice ${type}`;
    notice.hidden = false;
};

const clearNotice = () => {
    qs('#notice').hidden = true;
};

const productById = (id) => state.products.find((product) => Number(product.id) === Number(id));

const renderProductOptions = (selectedId = '') => state.products.map((product) => {
    const selected = Number(product.id) === Number(selectedId) ? 'selected' : '';

    return `<option value="${product.id}" ${selected}>${product.name} (${product.code}) - ${product.stock_on_hand} in stock</option>`;
}).join('');

const renderProducts = () => {
    qs('#product-count').textContent = `${state.products.length} products`;

    qs('#products-table').innerHTML = state.products.map((product) => `
        <tr>
            <td>${product.name}</td>
            <td><span class="code">${product.code}</span></td>
            <td>${money.format(Number(product.price_per_unit))}</td>
            <td>${Number(product.tax_percentage).toFixed(2)}%</td>
            <td>${product.stock_on_hand}</td>
        </tr>
    `).join('');

    document.querySelectorAll('.product-select').forEach((select) => {
        const selected = select.value;
        select.innerHTML = renderProductOptions(selected);
    });

    updateTotalsPreview();
};

const renderLowStock = () => {
    qs('#low-stock-count').textContent = `${state.lowStock.length} low stock`;

    if (!state.lowStock.length) {
        qs('#low-stock-list').innerHTML = '<p class="empty">No products below this threshold.</p>';
        return;
    }

    qs('#low-stock-list').innerHTML = state.lowStock.map((product) => `
        <div class="compact-item">
            <div>
                <strong>${product.name}</strong>
                <span>${product.code}</span>
            </div>
            <span class="stock-pill">${product.stock_on_hand}</span>
        </div>
    `).join('');
};

const addLine = (selectedId = '', quantity = 1) => {
    const line = document.createElement('div');
    line.className = 'order-line';
    line.innerHTML = `
        <label>
            <span>Product</span>
            <select class="product-select" required>
                ${renderProductOptions(selectedId)}
            </select>
        </label>
        <label>
            <span>Quantity</span>
            <input class="quantity-input" type="number" min="1" step="1" value="${quantity}" required>
        </label>
        <button class="icon-button remove-line" type="button" title="Remove item" aria-label="Remove item">x</button>
    `;

    qs('#order-lines').appendChild(line);
    updateTotalsPreview();
};

const updateTotalsPreview = () => {
    let subtotal = 0;
    let tax = 0;

    document.querySelectorAll('.order-line').forEach((line) => {
        const product = productById(line.querySelector('.product-select').value);
        const quantity = Number(line.querySelector('.quantity-input').value);

        if (!product || !Number.isInteger(quantity) || quantity < 1) {
            return;
        }

        const lineSubtotal = Number(product.price_per_unit) * quantity;
        const lineTax = lineSubtotal * (Number(product.tax_percentage) / 100);

        subtotal += lineSubtotal;
        tax += lineTax;
    });

    qs('#preview-subtotal').textContent = money.format(subtotal);
    qs('#preview-tax').textContent = money.format(tax);
    qs('#preview-grand-total').textContent = money.format(subtotal + tax);
};

const loadProducts = async () => {
    const response = await axios.get('/api/products');
    state.products = response.data.data;
    renderProducts();

    if (!qs('#order-lines').children.length && state.products.length) {
        addLine(state.products[0].id);
    }
};

const loadLowStock = async () => {
    const threshold = qs('#low-stock-form [name="threshold"]').value;
    const response = await axios.get('/api/products/low-stock', {
        params: { threshold },
    });
    state.lowStock = response.data.data;
    renderLowStock();
};

const collectOrderPayload = () => ({
    customer: {
        name: qs('[name="customer_name"]').value,
        email: qs('[name="customer_email"]').value,
    },
    items: Array.from(document.querySelectorAll('.order-line')).map((line) => ({
        product_id: Number(line.querySelector('.product-select').value),
        quantity: Number(line.querySelector('.quantity-input').value),
    })),
});

const submitOrder = async (event) => {
    event.preventDefault();
    clearNotice();

    try {
        const response = await axios.post('/api/orders', collectOrderPayload());
        showNotice(`Order #${response.data.data.id} created successfully.`, 'success');
        event.target.reset();
        qs('#order-lines').innerHTML = '';
        await loadProducts();
        await loadLowStock();
    } catch (error) {
        showNotice(error.response?.data?.message ?? 'Unable to create order.', 'error');
    }
};

const renderHistory = (payload) => {
    const orders = payload.orders;

    if (!orders.length) {
        qs('#history-results').innerHTML = '<p class="empty">This customer has no orders yet.</p>';
        return;
    }

    qs('#history-results').innerHTML = orders.map((order) => `
        <article class="history-card">
            <div class="history-card-header">
                <strong>Order #${order.id}</strong>
                <span>${money.format(Number(order.grand_total))}</span>
            </div>
            <div class="mini-totals">
                <span>Subtotal ${money.format(Number(order.subtotal))}</span>
                <span>Tax ${money.format(Number(order.tax_total))}</span>
            </div>
            <ul>
                ${order.items.map((item) => `
                    <li>
                        <span>${item.product.name} x ${item.quantity}</span>
                        <strong>${money.format(Number(item.line_total))}</strong>
                    </li>
                `).join('')}
            </ul>
        </article>
    `).join('');
};

const loadHistory = async (event) => {
    event.preventDefault();
    clearNotice();

    try {
        const response = await axios.get('/api/customers/orders', {
            params: { email: event.target.email.value },
        });
        renderHistory(response.data.data);
    } catch (error) {
        qs('#history-results').innerHTML = '';
        showNotice(error.response?.data?.message ?? 'Unable to fetch order history.', 'error');
    }
};

document.addEventListener('DOMContentLoaded', async () => {
    qs('#add-line').addEventListener('click', () => addLine(state.products[0]?.id));
    qs('#refresh-products').addEventListener('click', async () => {
        await loadProducts();
        await loadLowStock();
        showNotice('Products refreshed.', 'success');
    });
    qs('#order-form').addEventListener('submit', submitOrder);
    qs('#history-form').addEventListener('submit', loadHistory);
    qs('#low-stock-form').addEventListener('submit', async (event) => {
        event.preventDefault();
        await loadLowStock();
    });

    qs('#order-lines').addEventListener('input', updateTotalsPreview);
    qs('#order-lines').addEventListener('change', updateTotalsPreview);
    qs('#order-lines').addEventListener('click', (event) => {
        if (!event.target.classList.contains('remove-line')) {
            return;
        }

        event.target.closest('.order-line').remove();

        if (!qs('#order-lines').children.length) {
            addLine(state.products[0]?.id);
        }

        updateTotalsPreview();
    });

    try {
        await loadProducts();
        await loadLowStock();
    } catch (error) {
        showNotice('Unable to load data. Check database connection, migrations, and seed data.', 'error');
    }
});
