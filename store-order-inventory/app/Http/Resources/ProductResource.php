<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'price_per_unit' => $this->price_per_unit,
            'tax_percentage' => $this->tax_percentage,
            'stock_on_hand' => $this->stock_on_hand,
        ];
    }
}
