<?php
/**
 * Test WhatsAppService with UltraMsg Integration
 * 
 * This script tests the WhatsAppService methods using UltraMsg API
 */

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\WhatsAppService;

echo "WhatsAppService UltraMsg Integration Test\n";
echo "=========================================\n\n";

// Test the WhatsAppService
$whatsAppService = new WhatsAppService();

echo "Configuration:\n";
echo "==============\n";
echo "Is Configured: " . ($whatsAppService->isConfigured() ? 'Yes' : 'No') . "\n";
echo "API Token: " . substr($whatsAppService->getApiToken() ?? '', 0, 8) . "...\n";
echo "Instance ID: " . ($whatsAppService->getInstanceId() ?? 'Not set') . "\n";
echo "API URL: " . ($whatsAppService->getApiUrl() ?? 'Not set') . "\n\n";

if (!$whatsAppService->isConfigured()) {
    echo "Error: WhatsApp service is not configured!\n";
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

// Use a real document URL for testing
$testPdfUrl = 'https://file-example.s3-accelerate.amazonaws.com/documents/cv.pdf';

$result = $whatsAppService->sendMedia($testPhoneNumber, $testPdfUrl, 'test-document.pdf', 'Test document from LaundryPro system');
echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";

echo "All tests completed!\n";
