<?php
/**
 * UltraMsg WhatsApp API Test Script
 * 
 * This script demonstrates how to use the UltraMsg WhatsApp API endpoints
 * that we've implemented in the Laravel application.
 * 
 * Usage:
 * 1. Make sure your Laravel application is running
 * 2. Update the base URL and phone number below
 * 3. Run this script to test the API endpoints
 */

// Configuration
$baseUrl = 'http://localhost:8000/api'; // Update this to your Laravel app URL
$testPhoneNumber = '96878622990'; // Update this to your test phone number

// Test functions
function testSendMessage($baseUrl, $phoneNumber) {
    echo "Testing send message...\n";
    
    $data = [
        'to' => $phoneNumber,
        'body' => 'Hello from UltraMsg API! This is a test message from your LaundryPro system.'
    ];
    
    $response = makeRequest($baseUrl . '/ultramsg/send-message', 'POST', $data);
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
}

function testSendMedia($baseUrl, $phoneNumber) {
    echo "Testing send media...\n";
    
    // Create a simple test image (1x1 pixel PNG)
    $testImageData = base64_encode(file_get_contents('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='));
    
    $data = [
        'to' => $phoneNumber,
        'media' => $testImageData,
        'filename' => 'test.png',
        'caption' => 'Test image from UltraMsg API'
    ];
    
    $response = makeRequest($baseUrl . '/ultramsg/send-media', 'POST', $data);
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
}

function testSendDocument($baseUrl, $phoneNumber) {
    echo "Testing send document...\n";
    
    // Create a simple test document
    $testDocument = "This is a test document from UltraMsg API.\n\nIt contains multiple lines of text to test document sending functionality.";
    $testDocumentData = base64_encode($testDocument);
    
    $data = [
        'to' => $phoneNumber,
        'document' => $testDocumentData,
        'filename' => 'test_document.txt',
        'caption' => 'Test document from UltraMsg API'
    ];
    
    $response = makeRequest($baseUrl . '/ultramsg/send-document', 'POST', $data);
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
}

function testGetInstanceInfo($baseUrl) {
    echo "Testing get instance info...\n";
    
    $response = makeRequest($baseUrl . '/ultramsg/instance-info', 'GET');
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
}

function testGetChatHistory($baseUrl, $phoneNumber) {
    echo "Testing get chat history...\n";
    
    $response = makeRequest($baseUrl . '/ultramsg/chat-history?to=' . urlencode($phoneNumber) . '&limit=10&page=1', 'GET');
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
}

function testSendTestMessage($baseUrl, $phoneNumber) {
    echo "Testing send test message...\n";
    
    $data = [
        'to' => $phoneNumber
    ];
    
    $response = makeRequest($baseUrl . '/ultramsg/send-test', 'POST', $data);
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
}

function makeRequest($url, $method, $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    if ($data && $method === 'POST') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        echo "cURL Error: " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

// Run tests
echo "UltraMsg WhatsApp API Test Script\n";
echo "==================================\n\n";

echo "Base URL: $baseUrl\n";
echo "Test Phone Number: $testPhoneNumber\n\n";

// Test all endpoints
testGetInstanceInfo($baseUrl);
testSendMessage($baseUrl, $testPhoneNumber);
testSendTestMessage($baseUrl, $testPhoneNumber);
testSendMedia($baseUrl, $testPhoneNumber);
testSendDocument($baseUrl, $testPhoneNumber);
testGetChatHistory($baseUrl, $testPhoneNumber);

echo "All tests completed!\n";
echo "\nNote: Make sure your UltraMsg API is properly configured in your Laravel settings.\n";
echo "Required settings:\n";
echo "- ultramsg_enabled: true\n";
echo "- ultramsg_token: your_ultramsg_token\n";
echo "- ultramsg_instance_id: your_instance_id\n";
