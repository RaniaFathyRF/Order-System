<?php

namespace App\Services;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Cache\RedisLock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Cache\LockTimeoutException;

class OrderService
{
    public function __construct(){

    }

    /**
     * Places an order for a product by a user, ensuring stock availability and avoiding concurrent modification issues.
     *
     * This method processes an order by validating stock, acquiring a lock (if applicable), and updating the database.
     * Throws validation exceptions if the product is under processing by another transaction or has insufficient stock.
     * Uses a redis-backed locking mechanism to handle low stock scenarios and ensures atomicity using database transactions.
     *
     * @param int $userId The ID of the user placing the order.
     * @param int $productId The ID of the product being ordered.
     * @param int $quantity The quantity of the product to order.
     *
     * @return Order The successfully created order.
     *
     * @throws ValidationException If the product is being processed or stock is insufficient.
     * @throws \Exception For general errors during order processing.
     */
    public function placeOrder(int $userId, int $productId, int $quantity): Order
    {
        $lockKey = "product_order:{$productId}";
        $lock = null;
        $order = null;

        try {
            DB::beginTransaction();

            $product = Product::where('id', $productId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($product->hasLowStock()) {
                $lock = Cache::lock($lockKey, 10);

                try {
                    if (!$lock->get()) {
                        throw ValidationException::withMessages([
                            'product' => 'This product is currently being processed. Please try again shortly.'
                        ]);
                    }
                } catch (LockTimeoutException $e) {
                    throw ValidationException::withMessages([
                        'product' => 'Could not acquire lock for processing. Please try again.'
                    ]);
                }
            }

            if ($product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'product' => 'Insufficient stock available'
                ]);
            }

            $product->decrement('stock', $quantity);

            $order = Order::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'total_price' => $product->price * $quantity,
                'status' => 'completed'
            ]);

            DB::commit();

            return $order;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Order failed: " . $e->getMessage());
            throw $e;
        } finally {
            if ($lock) {
                $lock->release();
            }
        }
    }
}
