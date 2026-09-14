# Store Order & Inventory Management System

A Laravel 10 application for managing products, customers, orders, inventory, and low-stock products.

## Tech Stack

* PHP 8.1+
* Laravel 10
* PostgreSQL
* JavaScript
* Blade
* Vite
* Laravel Queue

## Main Features

* Product and inventory management
* Customer management
* Create orders
* Automatic stock deduction
* Subtotal, tax, and grand total calculation
* Insufficient stock handling
* Customer order history
* Low-stock product report
* Order confirmation queue job
* Database transaction and row locking for safe stock updates

---

## Setup

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Create `.env`

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure database

Update `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=store_order_inventory_system
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 4. Run migrations and seed data

```bash
php artisan migrate
php artisan db:seed
```

### 5. Build frontend

```bash
npm run build
```

### 6. Start the application

```bash
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

---

## Configuration

### Low Stock Threshold

Set the default threshold in `.env`:

```env
LOW_STOCK_THRESHOLD=10
```

The application reads this value using:

```php
config('inventory.low_stock_threshold')
```

### Queue

For local testing, the default queue can use:

```env
QUEUE_CONNECTION=sync
```

For background processing, use:

```env
QUEUE_CONNECTION=database
```

Then run:

```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

---

## Database

The application uses four main tables:

### Products

Stores product information and current stock.

* `name`
* `code`
* `price_per_unit`
* `tax_percentage`
* `stock_on_hand`

Product `code` is unique.

### Customers

Stores customer information.

* `name`
* `email`

Customer `email` is unique.

### Orders

Stores order-level information.

* `customer_id`
* `subtotal`
* `tax_total`
* `grand_total`

Order totals are stored so historical orders remain unchanged.

### Order Items

Stores products included in an order.

* `order_id`
* `product_id`
* `quantity`
* `unit_price`
* `tax_percentage`
* `line_subtotal`
* `line_tax`
* `line_total`

The product price and tax are copied to the order item when the order is created. This preserves the original order information even if the product price changes later.

---

# API Endpoints

## 1. Create Order

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

Success:

```text
201 Created
```

The response contains:

* Customer
* Order totals
* Order items
* Product information

Validation errors:

```text
422 Unprocessable Content
```

Insufficient stock:

```text
409 Conflict
```

---

## 2. Customer Order History

```http
GET /api/customers/orders?email=maya.patel@example.com
```

Returns:

* Customer information
* Orders
* Order items
* Product information
* Subtotal
* Tax
* Grand total

If the customer does not exist:

```text
404 Not Found
```

---

## 3. Low-Stock Products

```http
GET /api/products/low-stock
```

Uses the configured threshold.

You can also provide a custom threshold:

```http
GET /api/products/low-stock?threshold=5
```

The API returns products where:

```text
stock_on_hand < threshold
```

Invalid thresholds return:

```text
422 Unprocessable Content
```

---

## 4. Products

```http
GET /api/products
```

Returns the available products for the frontend catalog and order form.

---

# Order Creation Flow

When an order is created:

1. Validate the request.
2. Find or create the customer using email.
3. Start a database transaction.
4. Load the requested products.
5. Lock product rows using `lockForUpdate()`.
6. Check available stock.
7. Calculate subtotal and tax on the server.
8. Create the order.
9. Create order items.
10. Deduct stock.
11. Commit the transaction.
12. Dispatch the confirmation job after the transaction commits.

Client-provided totals are not trusted. All calculations are performed on the server.

---

# Stock Concurrency

Stock updates use:

```php
DB::transaction(...)
```

and:

```php
lockForUpdate()
```

The product row is locked before checking and deducting stock.

For example, if stock is `1` and two customers try to buy `1` item:

```text
Request 1 → Locks product → Stock = 1 → Deducts → Stock = 0
                                      ↓
                                  Commit

Request 2 → Waits for lock → Checks stock = 0 → 409 Conflict
```

This prevents overselling.

For orders containing multiple products, product rows are locked in ascending product ID order to reduce the chance of deadlocks.

---

# Order Confirmation Job

After a successful order, the application dispatches:

```php
SendOrderConfirmationJob::dispatch($order->id)->afterCommit();
```

The job receives only the order ID and loads the required order/customer information inside `handle()`.

The job currently simulates an email by writing a message to the Laravel log.

`afterCommit()` ensures the job runs only after the order transaction has successfully committed.

---

# Frontend

The `/` page provides a simple dashboard for:

* Viewing products
* Creating orders
* Checking low-stock products
* Searching customer order history

The frontend uses:

* Blade
*  JavaScript
* Existing Laravel APIs

---
# Assumptions

* Customers can be created during order creation.
* Products must already exist.
* Duplicate products in one order are rejected.
* Stock is deducted only when the complete order succeeds.
* If one product has insufficient stock, the entire order fails.
* Product price and tax are stored on order items for historical accuracy.
* No authentication is required.
* Payment, shipping, discount, refund, cancellation, and order-status workflows are not included.
* Order confirmation email is simulated using Laravel logs.
* Money values use decimal fields with two decimal places.

