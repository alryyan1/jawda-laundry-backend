<?php

// Test script for broadcasting functionality
// Access this file via: http://localhost/laundry/jawda-laundry-backend/test-broadcasting.php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

echo "<h1>Broadcasting Test Tool</h1>";

// Check broadcasting configuration
echo "<h2>Broadcasting Configuration:</h2>";
echo "<p>BROADCAST_DRIVER: " . env('BROADCAST_DRIVER') . "</p>";
echo "<p>PUSHER_APP_ID: " . env('PUSHER_APP_ID') . "</p>";
echo "<p>PUSHER_APP_KEY: " . env('PUSHER_APP_KEY') . "</p>";
echo "<p>PUSHER_APP_CLUSTER: " . env('PUSHER_APP_CLUSTER') . "</p>";

// Check if we have any orders
$order = Order::with('customer')->first();

if (!$order) {
    echo "<p style='color: red;'>No orders found in database. Please create an order first.</p>";
    echo "<p><a href='http://localhost/laundry/jawda-laundry-front/'>Go to POS</a></p>";
    exit;
}

echo "<h2>Testing with Order: {$order->id}</h2>";
echo "<p>Customer: {$order->customer->name}</p>";
echo "<p>Status: {$order->status}</p>";

// Test OrderCreated event
if (isset($_GET['test_created'])) {
    echo "<h3>Testing OrderCreated Event...</h3>";
    try {
        // Enable broadcasting logging
        Log::info('Testing OrderCreated event dispatch...');
        
        event(new OrderCreated($order));
        
        echo "<p style='color: green;'>✅ OrderCreated event dispatched successfully!</p>";
        echo "<p>Check Laravel logs for broadcasting details.</p>";
        echo "<p>Check your POS page for real-time updates.</p>";
        
        Log::info('OrderCreated event test completed');
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        Log::error('OrderCreated event test failed: ' . $e->getMessage());
    }
}

// Test OrderUpdated event
if (isset($_GET['test_updated'])) {
    echo "<h3>Testing OrderUpdated Event...</h3>";
    try {
        // Enable broadcasting logging
        Log::info('Testing OrderUpdated event dispatch...');
        
        event(new OrderUpdated($order, ['status' => 'processing']));
        
        echo "<p style='color: green;'>✅ OrderUpdated event dispatched successfully!</p>";
        echo "<p>Check Laravel logs for broadcasting details.</p>";
        echo "<p>Check your POS page for real-time updates.</p>";
        
        Log::info('OrderUpdated event test completed');
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
        Log::error('OrderUpdated event test failed: ' . $e->getMessage());
    }
}

// Show test buttons
echo "<h3>Test Broadcasting Events:</h3>";
echo "<p><a href='?test_created=1' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Test Order Created Event</a></p>";
echo "<p><a href='?test_updated=1' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Order Updated Event</a></p>";

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Open your POS page in multiple browser tabs: <a href='http://localhost/laundry/jawda-laundry-front/' target='_blank'>POS Page</a></li>";
echo "<li>Open browser console (F12) to check WebSocket connection status</li>";
echo "<li>Click one of the test buttons above</li>";
echo "<li>Watch for real-time updates in your POS tabs</li>";
echo "<li>You should see toast notifications and automatic data refresh</li>";
echo "</ol>";

echo "<h3>Debugging:</h3>";
echo "<p>Check browser console (F12) for WebSocket connection status</p>";
echo "<p>Check Laravel logs for any broadcasting errors</p>";
echo "<p>Verify Pusher credentials in .env files</p>";
echo "<p>Check if Pusher app is properly configured</p>";

// Show recent logs
echo "<h3>Recent Broadcasting Logs:</h3>";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLogs = array_slice($lines, -20);
    
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
    foreach ($recentLogs as $line) {
        if (strpos($line, 'broadcast') !== false || strpos($line, 'pusher') !== false || strpos($line, 'event') !== false) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
}
?>
