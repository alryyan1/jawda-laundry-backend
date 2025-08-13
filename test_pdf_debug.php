<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Pdf\OrdersListPdf;

echo "=== Testing PDF Generation with Debug ===\n\n";

try {
    $filters = [
        'date_from' => '2025-08-01',
        'date_to' => '2025-08-31',
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
    
    // Get all orders for the date range
    $allOrders = Order::with(['customer', 'items.serviceOffering.productType', 'items.serviceOffering.serviceAction'])
        ->where('order_date', '>=', '2025-08-01')
        ->where('order_date', '<=', '2025-08-31')
        ->get();
    
    echo "Total orders in date range: " . $allOrders->count() . "\n";
    
    // Check delivered orders separately
    $deliveredOrders = Order::where('status', 'delivered')
        ->where('order_date', '>=', '2025-08-01')
        ->where('order_date', '<=', '2025-08-31')
        ->get();
    
    echo "Delivered orders in date range: " . $deliveredOrders->count() . "\n";
    
    if ($deliveredOrders->count() > 0) {
        echo "Sample delivered order:\n";
        $sampleOrder = $deliveredOrders->first();
        echo "- ID: {$sampleOrder->id}, Status: {$sampleOrder->status}, Date: {$sampleOrder->order_date}\n";
    }
    
    $pdf = new OrdersListPdf();
    $pdf->setOrders($allOrders);
    $pdf->setFilters($filters);
    $pdf->setSettings($settings);
    
    echo "\nGenerating PDF...\n";
    $pdfContent = $pdf->generate();
    
    echo "PDF generated successfully! Size: " . strlen($pdfContent) . " bytes\n";
    
    // Save PDF for inspection
    file_put_contents('test_pdf_debug.pdf', $pdfContent);
    echo "PDF saved as test_pdf_debug.pdf\n";
    
} catch (Exception $e) {
    echo "Error generating PDF: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
