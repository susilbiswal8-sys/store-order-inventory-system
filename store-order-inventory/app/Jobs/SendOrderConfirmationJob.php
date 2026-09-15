<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $orderId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::query()
            ->with(['customer', 'orderItems.product'])
            ->findOrFail($this->orderId);

        Log::info('Simulated order confirmation email sent.', [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'customer_email' => $order->customer->email,
            'grand_total' => $order->grand_total,
            'item_count' => $order->orderItems->count(),
        ]);
    }
}
