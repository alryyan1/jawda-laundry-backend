<?php
/**
 * Test Core WhatsApp Invoice Functionality
 * 
 * This script tests the core invoice sending functionality without authorization
 */

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Services\WhatsAppService;

echo "Core WhatsApp Invoice Test\n";
echo "==========================\n\n";

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
    
    // Test the core functionality without authorization
    echo "Testing Core Invoice Sending Logic:\n";
    echo "===================================\n";
    
    // Simulate the core logic from sendWhatsappInvoice
    $customer = $order->customer;
    
    // --- 1. Generate the PDF using the refactored method ---
    $pdfContent = $orderController->downloadPosInvoice($order, true); // true for base64

    // --- 2. Create a temporary file for the PDF ---
    $tempDir = storage_path('app/temp');
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $fileName = 'Invoice-' . $order->id . '.pdf';
    $tempFilePath = $tempDir . '/' . $fileName;
    
    // Write PDF content to temporary file
    file_put_contents($tempFilePath, $pdfContent);

    // --- 3. Create a publicly accessible URL for the PDF ---
    $publicUrl = url('storage/temp/' . $fileName);
    
    // Copy file to public storage for UltraMsg to access
    $publicDir = public_path('storage/temp');
    if (!file_exists($publicDir)) {
        mkdir($publicDir, 0755, true);
    }
    copy($tempFilePath, $publicDir . '/' . $fileName);

    echo "Temporary file created: {$tempFilePath}\n";
    echo "Public file created: {$publicDir}/{$fileName}\n";
    echo "Public URL: {$publicUrl}\n\n";

    // --- 4. Send via WhatsApp Service using UltraMsg document API ---
    $caption = "Hello {$customer->name}, here is the invoice for your order #{$order->id}. Thank you!";
    
    echo "Sending via WhatsApp Service...\n";
    echo "Phone: {$customer->phone}\n";
    echo "URL: {$publicUrl}\n";
    echo "Filename: {$fileName}\n";
    echo "Caption: {$caption}\n\n";
    
    // Use the customer's phone number directly - WhatsAppService will format it
    $result = $whatsAppService->sendMedia($customer->phone, $publicUrl, $fileName, $caption);

    // --- 5. Clean up temporary files ---
    @unlink($tempFilePath);
    @unlink($publicDir . '/' . $fileName);

    echo "Temporary files cleaned up.\n\n";

    // --- 6. Check Result ---
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
