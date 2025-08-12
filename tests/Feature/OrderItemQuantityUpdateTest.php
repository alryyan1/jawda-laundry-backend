<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\ServiceOffering;
use App\Models\ProductType;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class OrderItemQuantityUpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $customer;
    protected $category;
    protected $productType;
    protected $serviceOffering;
    protected $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user
        $this->user = User::factory()->create();
        
        // Create a customer
        $this->customer = Customer::factory()->create();
        
        // Create a product category with sequence enabled
        $this->category = ProductCategory::factory()->create([
            'sequence_enabled' => true,
            'sequence_prefix' => 'Z',
            'current_sequence' => 0,
        ]);
        
        // Create a product type
        $this->productType = ProductType::factory()->create([
            'product_category_id' => $this->category->id,
        ]);
        
        // Create a service offering
        $this->serviceOffering = ServiceOffering::factory()->create([
            'product_type_id' => $this->productType->id,
            'default_price' => 10.00,
        ]);
        
        // Create an order
        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'total_amount' => 0,
        ]);
    }

    /** @test */
    public function updating_order_item_quantity_updates_category_sequence()
    {
        // Create an order item with initial quantity of 2
        $orderItem = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'service_offering_id' => $this->serviceOffering->id,
            'quantity' => 2,
            'calculated_price_per_unit_item' => 10.00,
            'sub_total' => 20.00,
        ]);

        // Generate initial category sequences
        $this->order->generateCategorySequences();
        $this->order->refresh();

        // Verify initial category sequence shows quantity 2
        $initialSequences = $this->order->category_sequences;
        $this->assertArrayHasKey($this->category->id, $initialSequences);
        $this->assertStringContainsString('-2', $initialSequences[$this->category->id]);

        // Update the order item quantity to 5
        $response = $this->actingAs($this->user)
            ->putJson("/api/order-items/{$orderItem->id}/quantity", [
                'quantity' => 5
            ]);

        // Assert the response is successful
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Order item quantity updated successfully.'
        ]);

        // Refresh the order to get updated data
        $this->order->refresh();

        // Verify the order item quantity was updated
        $orderItem->refresh();
        $this->assertEquals(5, $orderItem->quantity);

        // Verify the category sequence was updated to show quantity 5
        $updatedSequences = $this->order->category_sequences;
        $this->assertArrayHasKey($this->category->id, $updatedSequences);
        $this->assertStringContainsString('-5', $updatedSequences[$this->category->id]);

        // Verify the sequence number before the hyphen remains the same
        $initialSequenceNumber = explode('-', $initialSequences[$this->category->id])[0];
        $updatedSequenceNumber = explode('-', $updatedSequences[$this->category->id])[0];
        $this->assertEquals($initialSequenceNumber, $updatedSequenceNumber);

        // Verify the response includes updated category sequences
        $responseData = $response->json();
        $this->assertArrayHasKey('category_sequences', $responseData);
        $this->assertStringContainsString('-5', $responseData['category_sequences'][$this->category->id]);
    }

    /** @test */
    public function updating_order_item_quantity_updates_order_total()
    {
        // Create an order item with initial quantity of 2
        $orderItem = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'service_offering_id' => $this->serviceOffering->id,
            'quantity' => 2,
            'calculated_price_per_unit_item' => 10.00,
            'sub_total' => 20.00,
        ]);

        // Set initial order total
        $this->order->update(['total_amount' => 20.00]);

        // Update the order item quantity to 5
        $response = $this->actingAs($this->user)
            ->putJson("/api/order-items/{$orderItem->id}/quantity", [
                'quantity' => 5
            ]);

        // Assert the response is successful
        $response->assertStatus(200);

        // Refresh the order to get updated data
        $this->order->refresh();
        $orderItem->refresh();

        // Verify the order total was updated (5 * 10.00 = 50.00)
        $this->assertEquals(50.00, $this->order->total_amount);
        $this->assertEquals(50.00, $orderItem->sub_total);

        // Verify the response includes updated order total
        $responseData = $response->json();
        $this->assertEquals(50.00, $responseData['order_total']);
    }

    /** @test */
    public function updating_order_item_quantity_with_multiple_items_in_same_category()
    {
        // Create a second service offering in the same category
        $serviceOffering2 = ServiceOffering::factory()->create([
            'product_type_id' => $this->productType->id,
            'default_price' => 15.00,
        ]);

        // Create two order items in the same category
        $orderItem1 = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'service_offering_id' => $this->serviceOffering->id,
            'quantity' => 2,
            'calculated_price_per_unit_item' => 10.00,
            'sub_total' => 20.00,
        ]);

        $orderItem2 = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'service_offering_id' => $serviceOffering2->id,
            'quantity' => 3,
            'calculated_price_per_unit_item' => 15.00,
            'sub_total' => 45.00,
        ]);

        // Generate initial category sequences
        $this->order->generateCategorySequences();
        $this->order->refresh();

        // Verify initial category sequence shows total quantity 5 (2 + 3)
        $initialSequences = $this->order->category_sequences;
        $this->assertArrayHasKey($this->category->id, $initialSequences);
        $this->assertStringContainsString('-5', $initialSequences[$this->category->id]);

        // Update the first order item quantity to 4
        $response = $this->actingAs($this->user)
            ->putJson("/api/order-items/{$orderItem1->id}/quantity", [
                'quantity' => 4
            ]);

        // Assert the response is successful
        $response->assertStatus(200);

        // Refresh the order to get updated data
        $this->order->refresh();

        // Verify the category sequence was updated to show total quantity 7 (4 + 3)
        $updatedSequences = $this->order->category_sequences;
        $this->assertArrayHasKey($this->category->id, $updatedSequences);
        $this->assertStringContainsString('-7', $updatedSequences[$this->category->id]);
    }

    /** @test */
    public function updating_order_item_quantity_validates_input()
    {
        // Create an order item
        $orderItem = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'service_offering_id' => $this->serviceOffering->id,
            'quantity' => 2,
        ]);

        // Test with invalid quantity (0)
        $response = $this->actingAs($this->user)
            ->putJson("/api/order-items/{$orderItem->id}/quantity", [
                'quantity' => 0
            ]);

        $response->assertStatus(422);

        // Test with invalid quantity (negative)
        $response = $this->actingAs($this->user)
            ->putJson("/api/order-items/{$orderItem->id}/quantity", [
                'quantity' => -1
            ]);

        $response->assertStatus(422);

        // Test with missing quantity
        $response = $this->actingAs($this->user)
            ->putJson("/api/order-items/{$orderItem->id}/quantity", []);

        $response->assertStatus(422);
    }
}
