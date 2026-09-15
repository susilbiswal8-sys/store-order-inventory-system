<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LowStockProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'threshold' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function threshold(): int
    {
        return (int) $this->validated('threshold', config('inventory.low_stock_threshold'));
    }
}
