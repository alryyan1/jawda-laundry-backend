<?php

// Test script for real-time functionality
// Access this file via: http://localhost/laundry/jawda-laundry-backend/test-realtime.php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Order;

echo "<h1>Real-Time Testing Tool</h1>";

// Check if we have any orders
$order = Order::with('customer')->first();

if (!$order) {
    echo "<p style='color: red;'>No orders found in database. Please create an order first.</p>";
    echo "<p><a href='http://localhost/laundry/jawda-laundry-front/'>Go to POS</a></p>";
    exit;
}

echo "<h2>Testing with Order: {$order->order_number}</h2>";
echo "<p>Customer: {$order->customer->name}</p>";
echo "<p>Status: {$order->status}</p>";

// Test OrderCreated event
if (isset($_GET['test_created'])) {
    echo "<h3>Testing OrderCreated Event...</h3>";
    try {
        event(new OrderCreated($order));
        echo "<p style='color: green;'>✅ OrderCreated event dispatched successfully!</p>";
        echo "<p>Check your POS page for real-time updates.</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Test OrderUpdated event
if (isset($_GET['test_updated'])) {
    echo "<h3>Testing OrderUpdated Event...</h3>";
    try {
        event(new OrderUpdated($order, ['status' => 'processing']));
        echo "<p style='color: green;'>✅ OrderUpdated event dispatched successfully!</p>";
        echo "<p>Check your POS page for real-time updates.</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
}

// Show test buttons
echo "<h3>Test Real-Time Events:</h3>";
echo "<p><a href='?test_created=1' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Order Created Event</a></p>";
echo "<p><a href='?test_updated=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Order Updated Event</a></p>";

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Open your POS page in multiple browser tabs: <a href='http://localhost/laundry/jawda-laundry-front/' target='_blank'>POS Page</a></li>";
echo "<li>Click one of the test buttons above</li>";
echo "<li>Watch for real-time updates in your POS tabs</li>";
echo "<li>You should see toast notifications and automatic data refresh</li>";
echo "</ol>";

echo "<h3>Debugging:</h3>";
echo "<p>Check browser console (F12) for WebSocket connection status</p>";
echo "<p>Check Laravel logs for any broadcasting errors</p>";
echo "<p>Verify Pusher credentials in .env files</p>";
?> 