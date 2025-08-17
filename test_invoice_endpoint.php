<?php
/**
 * Test WhatsApp Invoice Endpoint with UltraMsg Integration
 * 
 * This script tests the refactored sendWhatsappInvoice endpoint
 */

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Services\WhatsAppService;

echo "WhatsApp Invoice Endpoint Test\n";
echo "==============================\n\n";

// Test order ID (you can change this to test with a different order)
$orderId = 7;

echo "Testing with Order ID: {$orderId}\n\n";

try {
    // Get the order
    $order = Order::with('customer')->find($orderId);
    
    if (!$order) {
        echo "Error: Order with ID {$orderId} not found!\n";
        exit(1);
    }
    
    echo "Order Details:\n";
    echo "==============\n";
    echo "Order ID: {$order->id}\n";
    echo "Customer: {$order->customer->name}\n";
    echo "Customer Phone: {$order->customer->phone}\n";
    echo "Order Total: {$order->total_amount}\n\n";
    
    // Test WhatsAppService configuration
    $whatsAppService = new WhatsAppService();
    
    echo "WhatsApp Service Configuration:\n";
    echo "===============================\n";
    echo "Is Configured: " . ($whatsAppService->isConfigured() ? 'Yes' : 'No') . "\n";
    
    if (!$whatsAppService->isConfigured()) {
        echo "Error: WhatsApp service is not configured!\n";
        exit(1);
    }
    
    echo "API Token: " . substr($whatsAppService->getApiToken() ?? '', 0, 8) . "...\n";
    echo "Instance ID: " . ($whatsAppService->getInstanceId() ?? 'Not set') . "\n";
    echo "API URL: " . ($whatsAppService->getApiUrl() ?? 'Not set') . "\n\n";
    
    // Test PDF generation
    echo "Testing PDF Generation:\n";
    echo "=======================\n";
    
    // Get the OrderController instance
    $orderController = app(\App\Http\Controllers\Api\OrderController::class);
    
    // Test PDF generation (this is the method used in sendWhatsappInvoice)
    $pdfContent = $orderController->downloadPosInvoice($order, true);
    
    if ($pdfContent) {
        echo "PDF generated successfully!\n";
        echo "PDF size: " . strlen($pdfContent) . " bytes\n\n";
    } else {
        echo "Error: Failed to generate PDF!\n";
        exit(1);
    }
    
    // Test the actual sendWhatsappInvoice method
    echo "Testing sendWhatsappInvoice Method:\n";
    echo "===================================\n";
    
    // Call the method directly
    $response = $orderController->sendWhatsappInvoice($order, $whatsAppService);
    
    // Get the response content
    $responseContent = $response->getContent();
    $responseData = json_decode($responseContent, true);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->getStatusCode() === 200) {
        echo "✅ SUCCESS: WhatsApp invoice sent successfully!\n";
    } else {
        echo "❌ FAILED: WhatsApp invoice sending failed!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed!\n";
