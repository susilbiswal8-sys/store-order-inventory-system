<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'customer' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
            ],
            'orders' => $this->orders->map(fn ($order) => [
                'id' => $order->id,
                'items' => $order->orderItems->map(fn ($item) => [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'code' => $item->product->code,
                    ],
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_percentage' => $item->tax_percentage,
                    'line_subtotal' => $item->line_subtotal,
                    'line_tax' => $item->line_tax,
                    'line_total' => $item->line_total,
                ])->values(),
                'subtotal' => $order->subtotal,
                'tax_total' => $order->tax_total,
                'grand_total' => $order->grand_total,
                'created_at' => $order->created_at?->toISOString(),
            ])->values(),
        ];
    }
}
