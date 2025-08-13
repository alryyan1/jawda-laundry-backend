<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Pdf\OrdersListPdf;

echo "=== Testing getDeliveredOrders Function ===\n\n";

// 1. Check all orders in database
echo "1. Database Overview:\n";
$totalOrders = Order::count();
echo "   Total orders: $totalOrders\n";

$statusCounts = Order::selectRaw('status, count(*) as count')
    ->groupBy('status')
    ->get();
echo "   Orders by status:\n";
foreach ($statusCounts as $status) {
    echo "   - {$status->status}: {$status->count}\n";
}

// 2. Check delivered orders specifically
echo "\n2. Delivered Orders Check:\n";
$deliveredOrders = Order::where('status', 'delivered')->get();
echo "   Total delivered orders: " . $deliveredOrders->count() . "\n";

if ($deliveredOrders->count() > 0) {
    echo "   Sample delivered orders:\n";
    foreach ($deliveredOrders->take(3) as $order) {
        echo "   - ID: {$order->id}, Date: {$order->order_date}, Status: {$order->status}\n";
    }
}

// 3. Test getDeliveredOrders with today's date
echo "\n3. Testing getDeliveredOrders with today's filters:\n";
$today = date('Y-m-d');
echo "   Today's date: $today\n";

// Create a mock PDF instance to test the method
$pdf = new OrdersListPdf();
$filters = [
    'date_from' => $today,
    'date_to' => $today,
    'status' => '',
    'search' => '',
    'order_id' => '',
    'customer_id' => '',
    'product_type_id' => '',
    'category_sequence_search' => ''
];

$pdf->setFilters($filters);

// Use reflection to access private method
$reflection = new ReflectionClass($pdf);
$method = $reflection->getMethod('getDeliveredOrders');
$method->setAccessible(true);

try {
    $result = $method->invoke($pdf);
    echo "   getDeliveredOrders result: " . $result->count() . " orders\n";
    
    if ($result->count() > 0) {
        echo "   Sample result orders:\n";
        foreach ($result->take(3) as $order) {
            echo "   - ID: {$order->id}, Date: {$order->order_date}, Status: {$order->status}\n";
        }
    } else {
        echo "   No orders returned by getDeliveredOrders\n";
    }
} catch (Exception $e) {
    echo "   Error calling getDeliveredOrders: " . $e->getMessage() . "\n";
}

// 4. Test with broader date range
echo "\n4. Testing with broader date range (last 30 days):\n";
$dateFrom = date('Y-m-d', strtotime('-30 days'));
$dateTo = date('Y-m-d');

$filters['date_from'] = $dateFrom;
$filters['date_to'] = $dateTo;
$pdf->setFilters($filters);

try {
    $result = $method->invoke($pdf);
    echo "   getDeliveredOrders result (30 days): " . $result->count() . " orders\n";
    
    if ($result->count() > 0) {
        echo "   Sample result orders:\n";
        foreach ($result->take(3) as $order) {
            echo "   - ID: {$order->id}, Date: {$order->order_date}, Status: {$order->status}\n";
        }
    }
} catch (Exception $e) {
    echo "   Error calling getDeliveredOrders: " . $e->getMessage() . "\n";
}

// 5. Manual query to compare
echo "\n5. Manual query comparison:\n";
$manualQuery = Order::where('status', 'delivered')
    ->whereDate('order_date', '>=', $dateFrom)
    ->whereDate('order_date', '<=', $dateTo)
    ->get();

echo "   Manual query result: " . $manualQuery->count() . " orders\n";

if ($manualQuery->count() > 0) {
    echo "   Manual query sample:\n";
    foreach ($manualQuery->take(3) as $order) {
        echo "   - ID: {$order->id}, Date: {$order->order_date}, Status: {$order->status}\n";
    }
}

// 6. Test PDF generation
echo "\n6. Testing full PDF generation:\n";
try {
    $allOrders = Order::with(['customer', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction'])
        ->where('order_date', '>=', $dateFrom)
        ->where('order_date', '<=', $dateTo)
        ->get();
    
    $settings = [
        'company_name' => 'Test Company',
        'company_address' => 'Test Address',
        'currency_symbol' => 'OMR'
    ];
    
    $pdf->setOrders($allOrders);
    $pdf->setSettings($settings);
    
    $pdfContent = $pdf->generate();
    echo "   PDF generated successfully! Size: " . strlen($pdfContent) . " bytes\n";
    
    // Save PDF for inspection
    file_put_contents('test_delivered_orders_debug.pdf', $pdfContent);
    echo "   PDF saved as test_delivered_orders_debug.pdf\n";
    
} catch (Exception $e) {
    echo "   Error generating PDF: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
