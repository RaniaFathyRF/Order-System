<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Cache\RedisLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    /**
     * Test that a successful order placement updates stock, associates the order with the correct user and product,
     * and verifies the ordered quantity.
     */
    public function test_successful_order_placement()
    {
        $product = Product::factory()->create(['stock' => 10]);
        $user = User::factory()->create();

        $order = $this->orderService->placeOrder($user->id, $product->id, 2);

        $this->assertEquals(8, $product->fresh()->stock);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals($product->id, $order->product_id);
        $this->assertEquals(2, $order->quantity);
    }

    /**
     * Test that an exception is thrown for insufficient stock when placing an order.
     *
     * This method validates that a product cannot be ordered in a quantity exceeding its available stock.
     *
     * @return void
     */
    public function test_insufficient_stock()
    {
        $product = Product::factory()->create(['stock' => 1]);
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Insufficient stock available');

        $this->orderService->placeOrder($user->id, $product->id, 2);
    }

    /**
     * Test that the product stock is reduced correctly after a successful order.
     */

    public function test_stock_is_reduced_after_successful_order()
    {
        $product = Product::factory()->create(['stock' => 10]);
        $user = User::factory()->create();

        $this->orderService->placeOrder($user->id, $product->id, 3);

        $this->assertEquals(7, $product->fresh()->stock);
    }

    /**
     * Test that the stock is not reduced when an order fails.
     */
    public function test_stock_not_reduced_when_order_fails()
    {
        $product = Product::factory()->create(['stock' => 10]);
        $user = User::factory()->create();

        try {
            $this->orderService->placeOrder($user->id, $product->id, 20);
        } catch (ValidationException $exception) {
            // Verify stock wasn't decremented
            $this->assertEquals(10, $product->fresh()->stock);

        }

    }

    /**
     * Test the low stock lock mechanism to ensure only one order can be processed
     * at a time for a product with limited stock, using Redis-based locking.
     *
     * This test ensures:
     * - The first order acquires the lock and succeeds.
     * - Subsequent orders fail to acquire the lock and throw a ValidationException.
     */
    public function test_low_stock_lock_mechanism()
    {
        $product = Product::factory()->create(['stock' => 5]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Mock the lock
        $mockLock1 = \Mockery::mock(RedisLock::class);
        // Mock Redis lock for first request
        Cache::shouldReceive('lock')
            ->once()
            ->andReturn($mockLock1);

        $mockLock1->shouldReceive('get')->once()->andReturn(true);
        $mockLock1->shouldReceive('release')->once();

        // First order should succeed
        $order1 = $this->orderService->placeOrder($user1->id, $product->id, 1);

        // Second request should fail to acquire lock
        $mockLock2 = \Mockery::mock(RedisLock::class);
        // Test lock contention scenario
        Cache::shouldReceive('lock')
            ->once()
            ->andReturn($mockLock2);

        $mockLock2->shouldReceive('get')->once()->andReturn(false);
        $mockLock2->shouldReceive('release')->once();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('This product is currently being processed');

        $this->orderService->placeOrder($user2->id, $product->id, 1);
    }

    /**
     * Test that a transaction is rolled back on failure, ensuring the stock remains unchanged.
     */
    public function test_transaction_rollback_on_failure()
    {
        $product = Product::factory()->create(['stock' => 10]);
        $user = User::factory()->create();

        // Force a failure after stock decrement
        DB::shouldReceive('commit')->andThrow(new \Exception('Forced failure'));
        DB::shouldReceive('rollBack')->andReturn(false);
        DB::shouldReceive('beginTransaction')->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Forced failure');

        try {
            $this->orderService->placeOrder($user->id, $product->id, 2);
        } finally {
            // Verify stock wasn't decremented
            $this->assertEquals(10, $product->stock);
        }

    }
}
