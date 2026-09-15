<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (InsufficientStockException $e): JsonResponse {
            return response()->json([
                'message' => 'Unable to create order because one or more items are out of stock.',
                'errors' => [
                    'items' => [
                        [
                            'product_id' => $e->productId,
                            'message' => 'Requested quantity is not available.',
                        ],
                    ],
                ],
            ], 409);
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
