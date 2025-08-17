<?php
/**
 * UltraMsg Document Sending Test
 * 
 * This script tests sending documents via UltraMsg API
 */

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

// UltraMsg Configuration
$token = 'b6ght2y2ff7rbha6';
$instanceId = 'instance139458';
$baseUrl = "https://api.ultramsg.com/{$instanceId}";
$testPhoneNumber = '96878622990';

echo "UltraMsg Document Sending Test\n";
echo "==============================\n\n";

$client = new Client(['timeout' => 30.0]);

/**
 * Test sending document
 */
function testSendDocument($client, $baseUrl, $token, $phoneNumber) {
    echo "Testing send document...\n";
    
    try {
        // Create a simple test PDF content (base64 encoded)
        $testPdfContent = base64_encode('Test PDF content - this is not a real PDF but for testing purposes');
        $testPdfUrl = "data:application/pdf;base64,{$testPdfContent}";
        
        $params = [
            'token' => $token,
            'to' => $phoneNumber,
            'document' => $testPdfUrl,
            'filename' => 'test-document.pdf',
            'caption' => 'Test document from UltraMsg API'
        ];
        
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $options = ['form_params' => $params];
        $request = new GuzzleRequest('POST', "{$baseUrl}/messages/document", $headers);
        $response = $client->sendAsync($request, $options)->wait();
        
        $statusCode = $response->getStatusCode();
        $responseBody = json_decode($response->getBody()->getContents(), true);
        
        echo "Status Code: {$statusCode}\n";
        echo "Response: " . json_encode($responseBody, JSON_PRETTY_PRINT) . "\n\n";
        
        return $statusCode >= 200 && $statusCode < 300;
    } catch (RequestException $e) {
        echo "Error: " . $e->getMessage() . "\n";
        if ($e->hasResponse()) {
            echo "Response: " . $e->getResponse()->getBody()->getContents() . "\n";
        }
        echo "\n";
        return false;
    }
}

// Test sending document
$result = testSendDocument($client, $baseUrl, $token, $testPhoneNumber);

echo "Test Result: " . ($result ? 'PASS' : 'FAIL') . "\n";
echo "\nTest completed!\n";
