# Store Order & Inventory Management System

Laravel 10 take-home assignment for managing products, customers, orders, order items, stock deduction, low-stock reporting, and simulated order confirmation jobs.

## Requirements

- PHP 8.1+
- Composer
- Node.js and npm
- PostgreSQL or another Laravel-supported database
- Laravel 10

## Setup

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the app key:

```bash
php artisan key:generate
```

Configure the database in `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=store_order_inventory_system
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

Run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed
```

Build frontend assets:

```bash
npm run build
```

Run the application locally:

```bash
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

## Configuration

Low-stock threshold is configurable through `.env`:

```env
LOW_STOCK_THRESHOLD=10
```

The value is read from:

```php
config('inventory.low_stock_threshold')
```

Queue driver can be configured with:

```env
QUEUE_CONNECTION=sync
```

For local background queue testing:

```env
QUEUE_CONNECTION=database
```

Then run:

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

## Database Design

Main tables:

- `products`
- `customers`
- `orders`
- `order_items`

### Products

Stores current product catalog and inventory.

Important fields:

- `name`
- `code`
- `price_per_unit`
- `tax_percentage`
- `stock_on_hand`

`code` is unique.

### Customers

Stores customer identity.

Important fields:

- `name`
- `email`

`email` is unique.

### Orders

Stores one order header for one customer.

Important fields:

- `customer_id`
- `subtotal`
- `tax_total`
- `grand_total`

Totals are stored so historical orders do not change later.

### Order Items

Stores product lines for an order.

Important fields:

- `order_id`
- `product_id`
- `quantity`
- `unit_price`
- `tax_percentage`
- `line_subtotal`
- `line_tax`
- `line_total`

`unit_price` and `tax_percentage` are copied from the product at order time. This preserves historical order data if product price or tax percentage changes later.

## API Endpoints

### Create Order

```http
POST /api/orders
```

Example request:

```json
{
  "customer": {
    "name": "Maya Patel",
    "email": "maya.patel@example.com"
  },
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```

Success response: `201 Created`

The API returns customer details, order totals, order items, and product information.

Validation errors return `422`.

Insufficient stock returns `409`.

### Customer Order History

```http
GET /api/customers/orders?email=maya.patel@example.com
```

Returns the customer, their orders, order items, product information, subtotal, tax total, and grand total.

If the customer does not exist, the API returns `404`.

### Low-Stock Products

```http
GET /api/products/low-stock
```

Optional threshold override:

```http
GET /api/products/low-stock?threshold=5
```

Returns products where:

```text
stock_on_hand < threshold
```

Invalid thresholds return `422`.

### Products

```http
GET /api/products
```

Returns products for the frontend catalog and order form.

## Order Creation Flow

1. Validate request using `StoreOrderRequest`.
2. Find or create the customer by email.
3. Start a database transaction.
4. Load requested products.
5. Lock product rows using `lockForUpdate()`.
6. Check stock after the rows are locked.
7. Calculate subtotal, tax, and grand total on the server.
8. Create the order.
9. Create order items with historical price and tax values.
10. Deduct stock.
11. Commit the transaction.
12. Dispatch `SendOrderConfirmationJob` after commit.

Client-provided totals are never trusted.

## Concurrency Design

The critical stock operation is protected with:

```php
DB::transaction(...)
lockForUpdate()
```

Product rows are locked before stock is checked and before stock is deducted.

This prevents overselling. For example, if stock is `1` and two requests both try to buy quantity `1`, the first transaction locks the product row. The second request must wait. After the first transaction commits and stock becomes `0`, the second request checks the current stock and fails cleanly with `409`.

For orders with multiple products, products are locked in ascending ID order to reduce deadlock risk.

## Queue Job

`SendOrderConfirmationJob` simulates sending an order-confirmation email by writing to the Laravel log.

The job receives only the order ID and loads the required order/customer data inside `handle()`.

The job is dispatched with:

```php
SendOrderConfirmationJob::dispatch($order->id)->afterCommit();
```

Dispatching after commit matters because a queue worker could otherwise run before the order transaction is committed. That could cause the job to read missing data or send a confirmation for an order that later rolls back.

## Frontend

The root page `/` provides a simple dashboard for:

- viewing products
- creating orders
- checking low-stock products
- searching customer order history

The frontend uses Blade, Vite, vanilla JavaScript, and the existing API endpoints.

## Testing

Run tests:

```bash
php artisan test
```

The test suite covers:

- successful order creation
- subtotal calculation
- tax calculation
- grand total calculation
- stock deduction
- insufficient stock handling
- customer order history
- low-stock API
- confirmation job dispatch
- practical stock exhaustion behavior

True simultaneous concurrency testing should be run against PostgreSQL or MySQL, not SQLite in-memory, because row-level locking behavior depends on the database engine.

## Assumptions

- Customers can be created during order creation.
- Products must already exist before creating an order.
- Orders use `product_id` values in request items.
- Duplicate product IDs in the same order are rejected.
- Stock is deducted only when the full order succeeds.
- If any item has insufficient stock, the full order fails.
- Product price and tax percentage are snapshotted onto order items.
- Order totals are stored historically.
- No authentication is required for this assignment.
- No payment, shipping, discount, refund, cancellation, or order-status workflow is included.
- The confirmation email is simulated with logs; real SMTP is not required.
- Money values use decimal columns with two decimal places.

