<?php

namespace App\Http\Controllers;

use App\Http\Requests\LowStockProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LowStockProductController extends Controller
{
    public function index(LowStockProductRequest $request): AnonymousResourceCollection
    {
        $threshold = $request->threshold();

        $products = Product::query()
            ->where('stock_on_hand', '<', $threshold)
            ->orderBy('stock_on_hand')
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products)->additional([
            'meta' => [
                'threshold' => $threshold,
            ],
        ]);
    }
}
