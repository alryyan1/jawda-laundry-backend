<?php
/**
 * Test WhatsApp Invoice with Base64 Approach
 * 
 * This script tests the refactored invoice sending using base64 encoding
 */

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Services\WhatsAppService;

echo "WhatsApp Invoice Base64 Test\n";
echo "============================\n\n";

// Test order ID
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
    
    // Test the base64 approach
    echo "Testing Base64 Invoice Sending:\n";
    echo "===============================\n";
    
    // Simulate the core logic from sendWhatsappInvoice
    $customer = $order->customer;
    
    // --- 1. Generate the PDF using the refactored method ---
    $pdfContent = $orderController->downloadPosInvoice($order, true); // true for base64

    // --- 2. Base64 Encode the PDF Content for UltraMsg ---
    $base64Pdf = base64_encode($pdfContent);
    $fileName = 'Invoice-' . $order->id . '.pdf';
    $caption = "Hello {$customer->name}, here is the invoice for your order #{$order->id}. Thank you!";

    // --- 3. Send via WhatsApp Service using UltraMsg document API with base64 ---
    // Create data URL for UltraMsg
    $dataUrl = "data:application/pdf;base64,{$base64Pdf}";
    
    echo "Sending via WhatsApp Service...\n";
    echo "Phone: {$customer->phone}\n";
    echo "Data URL length: " . strlen($dataUrl) . " characters\n";
    echo "Filename: {$fileName}\n";
    echo "Caption: {$caption}\n\n";
    
    // Use the customer's phone number directly - WhatsAppService will format it
    $result = $whatsAppService->sendMedia($customer->phone, $dataUrl, $fileName, $caption);

    // --- 4. Check Result ---
    echo "WhatsApp Service Result:\n";
    echo "=======================\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($result['status'] === 'success') {
        echo "✅ SUCCESS: WhatsApp invoice sent successfully!\n";
    } else {
        echo "❌ FAILED: WhatsApp invoice sending failed!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed!\n";
