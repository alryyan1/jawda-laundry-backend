<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\Customer;
use App\Models\ServiceOffering;

echo "Debugging category sequences...\n\n";

// Check the category
$category = ProductCategory::find(4);
echo "Category: {$category->name}\n";
echo "Current Sequence: {$category->current_sequence}\n";
echo "Next Sequence: {$category->getNextSequence()}\n\n";

// Check last 5 orders
echo "Last 5 orders with category_sequences:\n";
$orders = Order::orderBy('id', 'desc')->take(5)->get(['id', 'order_number', 'category_sequences', 'created_at']);

foreach ($orders as $order) {
    echo "Order #{$order->id} ({$order->order_number}) - Created: {$order->created_at}\n";
    echo "  Sequences: " . json_encode($order->category_sequences) . "\n";
}

echo "\n--- Testing new order creation ---\n";

// Get a customer and service offering for testing
$customer = Customer::first();
$serviceOffering = ServiceOffering::where('product_type_id', function($query) {
    $query->select('id')->from('product_types')->where('product_category_id', 4)->first();
})->first();

if (!$customer || !$serviceOffering) {
    echo "Error: Customer or service offering not found for testing\n";
    exit;
}

echo "Before creating order:\n";
echo "Category Current Sequence: {$category->current_sequence}\n";
echo "Category Next Sequence: {$category->getNextSequence()}\n\n";

// Create a test order
try {
    $order = Order::create([
        'order_number' => 'DEBUG-' . strtoupper(uniqid()),
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

    echo "Order created with ID: {$order->id}\n";
    echo "Items count: {$order->items()->count()}\n\n";

    // Generate category sequences
    echo "Generating category sequences...\n";
    $order->generateCategorySequences();

    echo "Order sequences: " . json_encode($order->category_sequences) . "\n\n";

    // Check sequence after creating order
    $category->refresh();
    echo "After creating order:\n";
    echo "Category Current Sequence: {$category->current_sequence}\n";
    echo "Category Next Sequence: {$category->getNextSequence()}\n\n";

} catch (Exception $e) {
    echo "Error creating order: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "Done.\n"; 