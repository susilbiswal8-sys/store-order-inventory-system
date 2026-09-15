<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer' => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
            ],
            'items' => $this->orderItems->map(fn ($item) => [
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
            'subtotal' => $this->subtotal,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
