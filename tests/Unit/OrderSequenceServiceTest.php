<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\OrderSequenceService;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductCategory;
use App\Models\ProductType;
use App\Models\ServiceOffering;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderSequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $orderSequenceService;
    protected $order;
    protected $category;
    protected $productType;
    protected $serviceOffering;
    protected $customer;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderSequenceService = new OrderSequenceService();
        
        // Create basic models without complex dependencies
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '1234567890',
            'email' => 'customer@example.com',
        ]);
        
        $this->category = ProductCategory::create([
            'name' => 'Test Category',
            'sequence_enabled' => true,
            'sequence_prefix' => 'Z',
            'current_sequence' => 0,
        ]);
        
        $this->productType = ProductType::create([
            'name' => 'Test Product Type',
            'product_category_id' => $this->category->id,
        ]);
        
        $this->serviceOffering = ServiceOffering::create([
            'name' => 'Test Service',
            'product_type_id' => $this->productType->id,
            'default_price' => 10.00,
        ]);
        
        $this->order = Order::create([
            // order_number removed - using id instead
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'total_amount' => 0,
            'paid_amount' => 0,
            'payment_status' => 'pending',
        ]);
    }

    /** @test */
    public function it_calculates_category_item_count_correctly()
    {
        // Create order items with different quantities
        OrderItem::create([
            'order_id' => $this->order->id,
            'service_offering_id' => $this->serviceOffering->id,
            'quantity' => 3,
            'calculated_price_per_unit_item' => 10.00,
            'sub_total' => 30.00,
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'service_offering_id' => $this->serviceOffering->id,
            'quantity' => 2,
            'calculated_price_per_unit_item' => 10.00,
            'sub_total' => 20.00,
        ]);

        // Use reflection to access the private method
        $reflection = new \ReflectionClass($this->orderSequenceService);
        $method = $reflection->getMethod('getCategoryItemCount');
        $method->setAccessible(true);

        $itemCount = $method->invoke($this->orderSequenceService, $this->order, $this->category->id);

        // Should return total quantity (3 + 2 = 5)
        $this->assertEquals(5, $itemCount);
    }

    /** @test */
    public function it_generates_sequence_with_correct_quantity()
    {
        // Create order item with quantity 4
        OrderItem::create([
            'order_id' => $this->order->id,
            'service_offering_id' => $this->serviceOffering->id,
            'quantity' => 4,
            'calculated_price_per_unit_item' => 10.00,
            'sub_total' => 40.00,
        ]);

        // Generate sequences
        $sequences = $this->orderSequenceService->generateOrderSequences($this->order);

        // Verify sequence format and quantity
        $this->assertArrayHasKey($this->category->id, $sequences);
        $sequence = $sequences[$this->category->id];
        
        // Should be in format Z001-4
        $this->assertStringStartsWith('Z', $sequence);
        $this->assertStringEndsWith('-4', $sequence);
    }

    /** @test */
    public function it_updates_sequence_when_quantity_changes()
    {
        // Create order item with initial quantity 2
        $orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'service_offering_id' => $this->serviceOffering->id,
            'quantity' => 2,
            'calculated_price_per_unit_item' => 10.00,
            'sub_total' => 20.00,
        ]);

        // Generate initial sequences
        $initialSequences = $this->orderSequenceService->generateOrderSequences($this->order);
        $initialSequence = $initialSequences[$this->category->id];

        // Update quantity to 7
        $orderItem->update(['quantity' => 7]);

        // Generate updated sequences
        $updatedSequences = $this->orderSequenceService->generateOrderSequences($this->order);
        $updatedSequence = $updatedSequences[$this->category->id];

        // Verify the sequence number before hyphen remains the same
        $initialParts = explode('-', $initialSequence);
        $updatedParts = explode('-', $updatedSequence);
        
        $this->assertEquals($initialParts[0], $updatedParts[0]); // Z001 should remain same
        
        // Verify the quantity after hyphen was updated
        $this->assertEquals('2', $initialParts[1]); // Should be 2 initially
        $this->assertEquals('7', $updatedParts[1]); // Should be 7 after update
    }

    /** @test */
    public function it_handles_multiple_items_in_same_category()
    {
        // Create second service offering in same category
        $serviceOffering2 = ServiceOffering::create([
            'name' => 'Test Service 2',
            'product_type_id' => $this->productType->id,
            'default_price' => 15.00,
        ]);

        // Create two order items in same category
        OrderItem::create([
            'order_id' => $this->order->id,
            'service_offering_id' => $this->serviceOffering->id,
            'quantity' => 3,
            'calculated_price_per_unit_item' => 10.00,
            'sub_total' => 30.00,
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'service_offering_id' => $serviceOffering2->id,
            'quantity' => 2,
            'calculated_price_per_unit_item' => 15.00,
            'sub_total' => 30.00,
        ]);

        // Generate sequences
        $sequences = $this->orderSequenceService->generateOrderSequences($this->order);

        // Should show total quantity 5 (3 + 2)
        $this->assertArrayHasKey($this->category->id, $sequences);
        $sequence = $sequences[$this->category->id];
        $this->assertStringEndsWith('-5', $sequence);
    }

    /** @test */
    public function it_skips_categories_without_sequence_enabled()
    {
        // Create category without sequence enabled
        $categoryNoSequence = ProductCategory::create([
            'name' => 'No Sequence Category',
            'sequence_enabled' => false,
            'sequence_prefix' => 'N',
            'current_sequence' => 0,
        ]);

        $productTypeNoSequence = ProductType::create([
            'name' => 'No Sequence Product Type',
            'product_category_id' => $categoryNoSequence->id,
        ]);

        $serviceOfferingNoSequence = ServiceOffering::create([
            'name' => 'No Sequence Service',
            'product_type_id' => $productTypeNoSequence->id,
            'default_price' => 5.00,
        ]);

        // Create order item for category without sequence
        OrderItem::create([
            'order_id' => $this->order->id,
            'service_offering_id' => $serviceOfferingNoSequence->id,
            'quantity' => 3,
            'calculated_price_per_unit_item' => 5.00,
            'sub_total' => 15.00,
        ]);

        // Generate sequences
        $sequences = $this->orderSequenceService->generateOrderSequences($this->order);

        // Should not include category without sequence enabled
        $this->assertArrayNotHasKey($categoryNoSequence->id, $sequences);
    }
}
