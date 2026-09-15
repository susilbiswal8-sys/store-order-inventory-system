<?php

namespace Tests\Feature;

use App\Jobs\SendOrderConfirmationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_order_creation_calculates_totals_deducts_stock_and_dispatches_job(): void
    {
        Queue::fake();

        $productA = Product::factory()->create([
            'price_per_unit' => 100.00,
            'tax_percentage' => 10.00,
            'stock_on_hand' => 5,
        ]);
        $productB = Product::factory()->create([
            'price_per_unit' => 50.00,
            'tax_percentage' => 5.00,
            'stock_on_hand' => 3,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer' => [
                'name' => 'Maya Patel',
                'email' => 'maya.patel@example.com',
            ],
            'items' => [
                ['product_id' => $productA->id, 'quantity' => 2],
                ['product_id' => $productB->id, 'quantity' => 1],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.customer.email', 'maya.patel@example.com')
            ->assertJsonPath('data.subtotal', '250.00')
            ->assertJsonPath('data.tax_total', '22.50')
            ->assertJsonPath('data.grand_total', '272.50')
            ->assertJsonCount(2, 'data.items');

        $orderId = $response->json('data.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'subtotal' => 250.00,
            'tax_total' => 22.50,
            'grand_total' => 272.50,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $productA->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'tax_percentage' => 10.00,
            'line_subtotal' => 200.00,
            'line_tax' => 20.00,
            'line_total' => 220.00,
        ]);

        $this->assertSame(3, $productA->fresh()->stock_on_hand);
        $this->assertSame(2, $productB->fresh()->stock_on_hand);

        Queue::assertPushed(SendOrderConfirmationJob::class, fn ($job) => $job->orderId === $orderId);
    }

    public function test_order_items_preserve_historical_price_and_tax(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'price_per_unit' => 19.99,
            'tax_percentage' => 8.25,
            'stock_on_hand' => 10,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer' => [
                'name' => 'Avery Johnson',
                'email' => 'avery.johnson@example.com',
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated();

        $product->update([
            'price_per_unit' => 29.99,
            'tax_percentage' => 10.00,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $response->json('data.id'),
            'product_id' => $product->id,
            'unit_price' => 19.99,
            'tax_percentage' => 8.25,
        ]);
    }

    public function test_insufficient_stock_returns_conflict_and_rolls_back_order(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'stock_on_hand' => 1,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer' => [
                'name' => 'Noah Williams',
                'email' => 'noah.williams@example.com',
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response
            ->assertStatus(409)
            ->assertJsonPath('errors.items.0.product_id', $product->id)
            ->assertJsonPath('errors.items.0.requested_quantity', 2)
            ->assertJsonPath('errors.items.0.available_stock', 1);

        $this->assertSame(1, $product->fresh()->stock_on_hand);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);

        Queue::assertNotPushed(SendOrderConfirmationJob::class);
    }

    public function test_duplicate_products_in_one_order_are_rejected(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson('/api/orders', [
            'customer' => [
                'name' => 'Sofia Garcia',
                'email' => 'sofia.garcia@example.com',
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('items.1.product_id');
    }

    public function test_customer_order_history_returns_orders_items_and_products(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'name' => 'Wireless Barcode Scanner',
            'code' => 'INV-BAR-001',
            'price_per_unit' => 100.00,
            'tax_percentage' => 10.00,
            'stock_on_hand' => 5,
        ]);

        $this->postJson('/api/orders', [
            'customer' => [
                'name' => 'Maya Patel',
                'email' => 'maya.patel@example.com',
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $response = $this->getJson('/api/customers/orders?email=maya.patel@example.com');

        $response
            ->assertOk()
            ->assertJsonPath('data.customer.email', 'maya.patel@example.com')
            ->assertJsonCount(1, 'data.orders')
            ->assertJsonPath('data.orders.0.items.0.product.code', 'INV-BAR-001')
            ->assertJsonPath('data.orders.0.subtotal', '100.00')
            ->assertJsonPath('data.orders.0.tax_total', '10.00')
            ->assertJsonPath('data.orders.0.grand_total', '110.00');
    }

    public function test_customer_order_history_returns_not_found_for_missing_customer(): void
    {
        $response = $this->getJson('/api/customers/orders?email=missing@example.com');

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Customer not found.');
    }

    public function test_low_stock_api_returns_products_below_configured_threshold(): void
    {
        config(['inventory.low_stock_threshold' => 10]);

        $lowProduct = Product::factory()->create([
            'name' => 'USB Card Reader',
            'stock_on_hand' => 2,
        ]);
        Product::factory()->create([
            'name' => 'Cash Drawer',
            'stock_on_hand' => 10,
        ]);
        Product::factory()->create([
            'name' => 'Receipt Printer',
            'stock_on_hand' => 25,
        ]);

        $response = $this->getJson('/api/products/low-stock');

        $response
            ->assertOk()
            ->assertJsonPath('meta.threshold', 10)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $lowProduct->id);
    }

    public function test_low_stock_api_allows_valid_threshold_query_parameter(): void
    {
        Product::factory()->create(['stock_on_hand' => 2]);
        Product::factory()->create(['stock_on_hand' => 6]);

        $response = $this->getJson('/api/products/low-stock?threshold=5');

        $response
            ->assertOk()
            ->assertJsonPath('meta.threshold', 5)
            ->assertJsonCount(1, 'data');
    }

    public function test_low_stock_api_rejects_invalid_threshold(): void
    {
        $this->getJson('/api/products/low-stock?threshold=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('threshold');
    }

    public function test_practical_concurrency_guard_only_one_purchase_can_consume_last_unit(): void
    {
        Queue::fake();

        $product = Product::factory()->create([
            'stock_on_hand' => 1,
        ]);

        $payload = fn (string $email) => [
            'customer' => [
                'name' => 'Concurrency Test',
                'email' => $email,
            ],
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $firstResponse = $this->postJson('/api/orders', $payload('first@example.com'));
        $secondResponse = $this->postJson('/api/orders', $payload('second@example.com'));

        $firstResponse->assertCreated();
        $secondResponse->assertStatus(409);

        $this->assertSame(0, $product->fresh()->stock_on_hand);
        $this->assertSame(1, Order::count());
    }
}
