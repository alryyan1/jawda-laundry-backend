<?php
/**
 * UltraMsg API Endpoints Test Script
 * 
 * This script tests different UltraMsg API endpoints to find the correct ones.
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

echo "UltraMsg API Endpoints Test Script\n";
echo "==================================\n\n";

$client = new Client(['timeout' => 30.0]);

/**
 * Test different endpoints
 */
function testEndpoint($client, $baseUrl, $token, $method, $endpoint, $params = []) {
    echo "Testing {$method} {$endpoint}...\n";
    
    try {
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $options = ['form_params' => $params];
        $request = new GuzzleRequest($method, "{$baseUrl}{$endpoint}", $headers);
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

// Test different endpoints
$endpoints = [
    ['GET', '/instance/info', ['token' => $token]],
    ['GET', '/instance/qrcode', ['token' => $token]],
    ['GET', '/instance/status', ['token' => $token]],
    ['POST', '/messages/chat', ['token' => $token, 'to' => $testPhoneNumber, 'body' => 'Test message']],
    ['POST', '/messages/media', ['token' => $token, 'to' => $testPhoneNumber, 'media' => 'https://example.com/test.jpg']],
    ['POST', '/messages/document', ['token' => $token, 'to' => $testPhoneNumber, 'document' => 'https://example.com/test.pdf']],
    ['POST', '/messages/image', ['token' => $token, 'to' => $testPhoneNumber, 'image' => 'https://example.com/test.jpg']],
    ['POST', '/messages/video', ['token' => $token, 'to' => $testPhoneNumber, 'video' => 'https://example.com/test.mp4']],
    ['POST', '/messages/audio', ['token' => $token, 'to' => $testPhoneNumber, 'audio' => 'https://example.com/test.mp3']],
];

foreach ($endpoints as $endpoint) {
    testEndpoint($client, $baseUrl, $token, $endpoint[0], $endpoint[1], $endpoint[2]);
}

echo "All endpoint tests completed!\n";
