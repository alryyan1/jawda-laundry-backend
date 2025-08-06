<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\Customer;
use App\Models\ServiceOffering;

echo "Testing order creation with category sequences...\n\n";

// Check current sequence before creating order
$category = ProductCategory::find(4);
echo "Before creating order:\n";
echo "Category: {$category->name}\n";
echo "Current Sequence: {$category->current_sequence}\n";
echo "Next Sequence: {$category->getNextSequence()}\n\n";

// Get a customer and service offering for testing
$customer = Customer::first();
$serviceOffering = ServiceOffering::where('product_type_id', function($query) {
    $query->select('id')->from('product_types')->where('product_category_id', 4)->first();
})->first();

if (!$customer || !$serviceOffering) {
    echo "Error: Customer or service offering not found for testing\n";
    exit;
}

echo "Using Customer: {$customer->name}\n";
echo "Using Service Offering: {$serviceOffering->display_name}\n\n";

// Create a test order
try {
    $order = Order::create([
        'order_number' => 'TEST-' . strtoupper(uniqid()),
        'customer_id' => $customer->id,
        'user_id' => 1,
        'status' => 'pending',
        'order_type' => 'take_away',
        'total_amount' => 0,
        'paid_amount' => 0,
        'payment_status' => 'pending',
        'order_date' => now(),
    ]);

    // Add an item to the order
    $order->items()->create([
        'service_offering_id' => $serviceOffering->id,
        'quantity' => 1,
        'calculated_price_per_unit_item' => 10.00,
        'sub_total' => 10.00,
    ]);

    // Generate category sequences
    $order->generateCategorySequences();

    echo "Order created successfully!\n";
    echo "Order ID: {$order->id}\n";
    echo "Category Sequences: " . json_encode($order->category_sequences) . "\n\n";

    // Check sequence after creating order
    $category->refresh();
    echo "After creating order:\n";
    echo "Current Sequence: {$category->current_sequence}\n";
    echo "Next Sequence: {$category->getNextSequence()}\n\n";

} catch (Exception $e) {
    echo "Error creating order: " . $e->getMessage() . "\n";
}

echo "Done.\n"; 