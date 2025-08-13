<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class OrderDeliveredDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_date_can_be_set_on_order()
    {
        // Create a customer and user
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        // Create an order
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'processing',
            'delivered_date' => null,
        ]);

        // Set delivered date and status
        $order->delivered_date = now();
        $order->status = 'delivered';
        $order->save();

        // Refresh the order from database
        $order->refresh();

        // Assert that delivered_date is set
        $this->assertNotNull($order->delivered_date);
        $this->assertEquals('delivered', $order->status);
    }

    public function test_delivered_date_field_exists_in_orders_table()
    {
        // Create a customer and user
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        // Create an order with delivered_date
        $deliveredDate = now();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'status' => 'delivered',
            'delivered_date' => $deliveredDate,
        ]);

        // Refresh the order from database
        $order->refresh();

        // Assert that delivered_date is set correctly
        $this->assertEquals($deliveredDate->format('Y-m-d H:i:s'), $order->delivered_date->format('Y-m-d H:i:s'));
        $this->assertEquals('delivered', $order->status);
    }
}
