<?php

// Simple test script for broadcasting
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Events\OrderCreated;
use App\Models\Order;

echo "=== Broadcasting Test ===\n";
echo "BROADCAST_DRIVER: " . env('BROADCAST_DRIVER') . "\n";
echo "PUSHER_APP_ID: " . env('PUSHER_APP_ID') . "\n";
echo "PUSHER_APP_KEY: " . env('PUSHER_APP_KEY') . "\n";
echo "PUSHER_APP_CLUSTER: " . env('PUSHER_APP_CLUSTER') . "\n";
echo "PUSHER_APP_SECRET: " . env('PUSHER_APP_SECRET') . "\n\n";

// Get an order
$order = Order::with('customer')->first();

if (!$order) {
    echo "No orders found in database.\n";
    exit;
}

echo "Testing with Order ID: {$order->id}\n";
echo "Customer: {$order->customer->name}\n\n";

try {
    echo "Dispatching OrderCreated event...\n";
    event(new OrderCreated($order));
    echo "✅ Event dispatched successfully!\n";
    echo "Check your POS page for real-time updates.\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
