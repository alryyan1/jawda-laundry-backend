<?php
/**
 * Test WhatsApp Invoice Sending with UltraMsg
 * 
 * This script tests the WhatsApp invoice sending functionality
 * using the updated UltraMsg API integration.
 */

require 'vendor/autoload.php';

use App\Services\WhatsAppService;
use App\Services\SettingsService;

echo "WhatsApp Invoice Sending Test\n";
echo "=============================\n\n";

// Test the WhatsAppService directly
$whatsAppService = new WhatsAppService();

echo "WhatsApp Service Configuration:\n";
echo "===============================\n";
echo "Is Configured: " . ($whatsAppService->isConfigured() ? 'Yes' : 'No') . "\n";

if (!$whatsAppService->isConfigured()) {
    echo "Error: WhatsApp service is not configured!\n";
    echo "Please check your UltraMsg settings in the database.\n";
    exit(1);
}

// Test phone number
$testPhoneNumber = '96878622990';

echo "Test Phone Number: {$testPhoneNumber}\n\n";

// Test 1: Send a simple text message
echo "Test 1: Sending Text Message\n";
echo "-----------------------------\n";
$result = $whatsAppService->sendMessage($testPhoneNumber, 'Test message from LaundryPro system - UltraMsg integration test');
echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Send a test document (PDF)
echo "Test 2: Sending Test Document\n";
echo "------------------------------\n";

// Create a simple test PDF content (base64 encoded)
$testPdfContent = base64_encode('Test PDF content - this is not a real PDF but for testing purposes');
$testPdfUrl = "data:application/pdf;base64,{$testPdfContent}";

$result = $whatsAppService->sendMedia($testPhoneNumber, $testPdfUrl, 'test-document.pdf', 'Test document from LaundryPro system');
echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Test the sendMediaBase64 method
echo "Test 3: Sending Base64 Document\n";
echo "--------------------------------\n";
$result = $whatsAppService->sendMediaBase64($testPhoneNumber, $testPdfContent, 'test-base64-document.pdf', 'Test base64 document from LaundryPro system');
echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "All tests completed!\n";
