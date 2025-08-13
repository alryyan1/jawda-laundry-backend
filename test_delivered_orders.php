<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Pdf\OrdersListPdf;

echo "=== Testing Delivered Orders ===\n\n";

// 1. Check total orders
$totalOrders = Order::count();
echo "Total orders in database: $totalOrders\n";

// 2. Check delivered orders
$deliveredOrders = Order::where('status', 'delivered')->count();
echo "Delivered orders in database: $deliveredOrders\n";

// 3. Check orders by status
$statusCounts = Order::selectRaw('status, count(*) as count')
    ->groupBy('status')
    ->get();
echo "\nOrders by status:\n";
foreach ($statusCounts as $status) {
    echo "- {$status->status}: {$status->count}\n";
}

// 4. Check delivered orders with date range
$dateFrom = '2025-08-01';
$dateTo = '2025-08-31';
echo "\nDelivered orders from $dateFrom to $dateTo:\n";

$deliveredInRange = Order::where('status', 'delivered')
    ->where('order_date', '>=', $dateFrom)
    ->where('order_date', '<=', $dateTo)
    ->get();

echo "Found " . $deliveredInRange->count() . " delivered orders in date range\n";

if ($deliveredInRange->count() > 0) {
    echo "\nSample delivered orders:\n";
    foreach ($deliveredInRange->take(3) as $order) {
        echo "- Order ID: {$order->id}, Date: {$order->order_date}, Customer: " . 
             ($order->customer ? $order->customer->name : 'N/A') . "\n";
    }
}

// 5. Test PDF generation with filters
echo "\n=== Testing PDF Generation ===\n";

try {
    $filters = [
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'status' => '',
        'search' => '',
        'order_id' => '',
        'customer_id' => '',
        'product_type_id' => '',
        'category_sequence_search' => ''
    ];
    
    $settings = [
        'company_name' => 'Test Company',
        'company_address' => 'Test Address',
        'currency_symbol' => 'OMR'
    ];
    
    // Get all orders for the date range (not just delivered)
    $allOrders = Order::with(['customer', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction'])
        ->where('order_date', '>=', $dateFrom)
        ->where('order_date', '<=', $dateTo)
        ->get();
    
    echo "Total orders in date range: " . $allOrders->count() . "\n";
    
    $pdf = new OrdersListPdf();
    $pdf->setOrders($allOrders);
    $pdf->setFilters($filters);
    $pdf->setSettings($settings);
    
    $pdfContent = $pdf->generate();
    
    echo "PDF generated successfully! Size: " . strlen($pdfContent) . " bytes\n";
    
    // Save PDF for inspection
    file_put_contents('test_orders_pdf.pdf', $pdfContent);
    echo "PDF saved as test_orders_pdf.pdf\n";
    
} catch (Exception $e) {
    echo "Error generating PDF: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
