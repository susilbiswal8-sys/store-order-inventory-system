<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Store Order & Inventory</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">Inventory desk</p>
                <h1>Store Order & Inventory</h1>
            </div>
            <div class="status-strip" aria-live="polite">
                <span id="product-count">0 products</span>
                <span id="low-stock-count">0 low stock</span>
            </div>
        </header>

        <section id="notice" class="notice" hidden></section>

        <section class="workspace">
            <div class="panel order-panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Create order</p>
                        <h2>New customer order</h2>
                    </div>
                    <button class="icon-button" type="button" id="refresh-products" title="Refresh products" aria-label="Refresh products">
                        <span aria-hidden="true">&#8635;</span>
                    </button>
                </div>

                <form id="order-form" class="stack">
                    <div class="form-grid">
                        <label>
                            <span>Customer name</span>
                            <input type="text" name="customer_name" placeholder="Maya Patel" required>
                        </label>
                        <label>
                            <span>Customer email</span>
                            <input type="email" name="customer_email" placeholder="maya.patel@example.com" required>
                        </label>
                    </div>

                    <div class="line-header">
                        <span>Items</span>
                        <button class="secondary-button" type="button" id="add-line">Add item</button>
                    </div>

                    <div id="order-lines" class="order-lines"></div>

                    <div class="totals">
                        <div>
                            <span>Subtotal</span>
                            <strong id="preview-subtotal">$0.00</strong>
                        </div>
                        <div>
                            <span>Tax</span>
                            <strong id="preview-tax">$0.00</strong>
                        </div>
                        <div>
                            <span>Grand total</span>
                            <strong id="preview-grand-total">$0.00</strong>
                        </div>
                    </div>

                    <button class="primary-button" type="submit">Create order</button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-heading stock-heading">
                    <div>
                        <p class="eyebrow">Stock</p>
                        <h2>Low-stock products</h2>
                    </div>
                    <form id="low-stock-form" class="threshold-control">
                        <label>
                            <span>Threshold</span>
                            <input type="number" min="1" step="1" name="threshold" value="10">
                        </label>
                        <button class="secondary-button" type="submit">Apply</button>
                    </form>
                </div>
                <div id="low-stock-list" class="compact-list"></div>
            </div>
        </section>

        <section class="workspace lower">
            <div class="panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">Catalog</p>
                        <h2>Products</h2>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Price</th>
                                <th>Tax</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody id="products-table"></tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-heading">
                    <div>
                        <p class="eyebrow">History</p>
                        <h2>Customer orders</h2>
                    </div>
                </div>
                <form id="history-form" class="lookup-form">
                    <input type="email" name="email" placeholder="customer@example.com" required>
                    <button class="secondary-button" type="submit">Search</button>
                </form>
                <div id="history-results" class="history-results"></div>
            </div>
        </section>
    </main>
</body>
</html>
