<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\TextUI\Application;
use Tests\TestCase;

class OrderTest extends TestCase
{

    use RefreshDatabase;

    /**
     * Test to ensure that a user can place an order.
     *
     * The test sets up a user and a product with an initial stock.
     * It then simulates placing an order using the `postJson` method under the context of the authenticated user.
     * The response is validated for status 201 and the correct JSON structure representing the order details.
     * Additionally, the test confirms that the product's stock is appropriately reduced after the order.
     */
    public function test_user_can_place_order()
    {
        $user = User::factory()->create();

        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'product_id' => $product->id,
                'quantity' => 2
            ]);

       //  Assertions
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'product_id',
                    'quantity',
                    'total_price',
                    'status'
                ]
            ]);

        $this->assertEquals(8, $product->fresh()->stock);
    }

    /**
     * Tests that an order fails when the requested quantity exceeds the available stock.
     */
    public function test_order_fails_with_insufficient_stock()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 1]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'product_id' => $product->id,
                'quantity' => 2
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Insufficient stock available'
            ]);

        $this->assertEquals(1, $product->fresh()->stock);
    }

    /**
     * Tests that the order endpoint returns validation errors for invalid input.
     */
    public function test_validation_errors()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/orders', [
                'product_id' => 999,
                'quantity' => 0
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'quantity']);
    }
}
