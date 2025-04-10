<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    /**
     * Handles the storage of a new order.
     *
     * @param PlaceOrderRequest $request The request containing order data.
     *
     * @return JsonResponse A JSON response containing success or error information.
     *
     * @throws \Exception If an error occurs during order placement.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        try {

            $order = $this->orderService->placeOrder(
                $request->user()->id,
                $request->product_id,
                $request->quantity
            );

            return response()->json([
                'message' => 'Order placed successfully',
                'data' => $order
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors() ?? []
            ], 400);
        }
    }
}
