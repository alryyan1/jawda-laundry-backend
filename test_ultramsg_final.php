<?php
/**
 * Final UltraMsg Document Test with Correct Format
 * 
 * This script tests sending documents via UltraMsg API using the correct format
 */

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

// UltraMsg Configuration
$token = 'b6ght2y2ff7rbha6';
$instanceId = 'instance139458';
$baseUrl = "https://api.ultramsg.com/{$instanceId}";
$testPhoneNumber = '+96878622990'; // Correct format with + prefix

echo "UltraMsg Final Document Test\n";
echo "============================\n\n";

$client = new Client(['timeout' => 30.0]);

/**
 * Test sending document with correct format
 */
function testSendDocument($client, $baseUrl, $token, $phoneNumber) {
    echo "Testing send document with correct format...\n";
    
    try {
        // Use a real document URL for testing
        $documentUrl = 'https://file-example.s3-accelerate.amazonaws.com/documents/cv.pdf';
        
        $params = [
            'token' => $token,
            'to' => $phoneNumber,
            'document' => $documentUrl,
            'filename' => 'test-document.pdf',
            'caption' => 'Test document from LaundryPro system via UltraMsg'
        ];
        
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        
        $options = ['form_params' => $params];
        $request = new Request('POST', "{$baseUrl}/messages/document", $headers);
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
