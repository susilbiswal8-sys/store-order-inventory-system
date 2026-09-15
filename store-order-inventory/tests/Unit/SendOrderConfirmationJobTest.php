<?php

namespace Tests\Unit;

use App\Jobs\SendOrderConfirmationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendOrderConfirmationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_loads_order_data_and_logs_simulated_confirmation(): void
    {
        Log::spy();

        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'subtotal' => 100.00,
            'tax_total' => 10.00,
            'grand_total' => 110.00,
        ]);

        $order->orderItems()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 100.00,
            'tax_percentage' => 10.00,
            'line_subtotal' => 100.00,
            'line_tax' => 10.00,
            'line_total' => 110.00,
        ]);

        (new SendOrderConfirmationJob($order->id))->handle();

        Log::shouldHaveReceived('info')->once()->with(
            'Simulated order confirmation email sent.',
            \Mockery::on(fn (array $context) => $context['order_id'] === $order->id
                && $context['customer_id'] === $customer->id
                && $context['customer_email'] === $customer->email
                && $context['grand_total'] === '110.00'
                && $context['item_count'] === 1)
        );
    }
}
