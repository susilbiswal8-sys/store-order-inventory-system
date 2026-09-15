<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerOrderHistoryRequest;
use App\Http\Resources\CustomerOrderHistoryResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;

class CustomerOrderController extends Controller
{
    public function index(CustomerOrderHistoryRequest $request): CustomerOrderHistoryResource|JsonResponse
    {
        $customer = Customer::query()
            ->where('email', $request->validated('email'))
            ->with([
                'orders' => fn ($query) => $query->latest(),
                'orders.orderItems.product',
            ])
            ->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found.',
            ], 404);
        }

        return new CustomerOrderHistoryResource($customer);
    }
}
