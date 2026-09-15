<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Jobs\SendOrderConfirmationJob;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Create an order and deduct stock in a single database transaction.
     *
     * @param array{customer: array{name: string, email: string}, items: array<int, array{product_id: int, quantity: int}>} $data
     */
    public function create(array $data): Order
    {
        $order = DB::transaction(function () use ($data) {
            $customer = Customer::firstOrCreate(
                ['email' => $data['customer']['email']],
                ['name' => $data['customer']['name']]
            );

            $requestedItems = collect($data['items'])
                ->mapWithKeys(fn (array $item) => [
                    (int) $item['product_id'] => (int) $item['quantity'],
                ]);

            $products = $this->lockProducts($requestedItems->keys());

            $subtotal = 0;
            $taxTotal = 0;
            $preparedItems = [];

            foreach ($requestedItems as $productId => $quantity) {
                $product = $products->get($productId);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => ["Product {$productId} was not found."],
                    ]);
                }

                if ($product->stock_on_hand < $quantity) {
                    throw new InsufficientStockException(
                        productId: $product->id,
                        productName: $product->name,
                        requestedQuantity: $quantity,
                        availableStock: $product->stock_on_hand
                    );
                }

                $unitPrice = (float) $product->price_per_unit;
                $taxPercentage = (float) $product->tax_percentage;
                $lineSubtotal = round($unitPrice * $quantity, 2);
                $lineTax = round($lineSubtotal * ($taxPercentage / 100), 2);
                $lineTotal = round($lineSubtotal + $lineTax, 2);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;

                $preparedItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_percentage' => $taxPercentage,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ];
            }

            $subtotal = round($subtotal, 2);
            $taxTotal = round($taxTotal, 2);
            $grandTotal = round($subtotal + $taxTotal, 2);

            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            foreach ($preparedItems as $item) {
                $order->orderItems()->create([
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_percentage' => $item['tax_percentage'],
                    'line_subtotal' => $item['line_subtotal'],
                    'line_tax' => $item['line_tax'],
                    'line_total' => $item['line_total'],
                ]);

                $item['product']->decrement('stock_on_hand', $item['quantity']);
            }

            return $order->load(['customer', 'orderItems.product']);
        }, 3);

        SendOrderConfirmationJob::dispatch($order->id)->afterCommit();

        return $order;
    }

    /**
     * @param \Illuminate\Support\Collection<int, int> $productIds
     * @return \Illuminate\Support\Collection<int, \App\Models\Product>
     */
    private function lockProducts(Collection $productIds): Collection
    {
        return Product::query()
            ->whereIn('id', $productIds->sort()->values())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }
}
