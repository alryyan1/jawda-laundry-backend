<?php
/**
 * Direct UltraMsg API Test Script
 * 
 * This script tests the UltraMsg API directly without going through Laravel
 * to verify that the credentials and API endpoints are working correctly.
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

echo "UltraMsg Direct API Test Script\n";
echo "================================\n\n";
echo "Instance ID: {$instanceId}\n";
echo "Base URL: {$baseUrl}\n";
echo "Test Phone Number: {$testPhoneNumber}\n\n";

$client = new Client(['timeout' => 30.0]);

/**
 * Test get instance info
 */
function testGetInstanceInfo($client, $baseUrl, $token) {
    echo "Testing get instance info...\n";
    
    try {
        $params = ['token' => $token];
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $options = ['form_params' => $params];
        $request = new GuzzleRequest('GET', "{$baseUrl}/instance/info", $headers);
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

/**
 * Test send message
 */
function testSendMessage($client, $baseUrl, $token, $phoneNumber) {
    echo "Testing send message...\n";
    
    try {
        $params = [
            'token' => $token,
            'to' => $phoneNumber,
            'body' => 'This is a test message from UltraMsg API direct test script!'
        ];
        
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $options = ['form_params' => $params];
        $request = new GuzzleRequest('POST', "{$baseUrl}/messages/chat", $headers);
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

/**
 * Test send media
 */
function testSendMedia($client, $baseUrl, $token, $phoneNumber) {
    echo "Testing send media...\n";
    
    try {
        // Create a simple test image (1x1 pixel PNG)
        $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $testImageBase64 = base64_encode($testImageData);
        
        $params = [
            'token' => $token,
            'to' => $phoneNumber,
            'media' => "data:image/png;base64,{$testImageBase64}",
            'caption' => 'Test image from UltraMsg API'
        ];
        
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $options = ['form_params' => $params];
        $request = new GuzzleRequest('POST', "{$baseUrl}/messages/media", $headers);
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

// Run tests
$tests = [
    'Instance Info' => testGetInstanceInfo($client, $baseUrl, $token),
    'Send Message' => testSendMessage($client, $baseUrl, $token, $testPhoneNumber),
    'Send Media' => testSendMedia($client, $baseUrl, $token, $testPhoneNumber)
];

echo "Test Results Summary:\n";
echo "====================\n";
foreach ($tests as $testName => $result) {
    $status = $result ? 'PASS' : 'FAIL';
    echo "{$testName}: {$status}\n";
}

echo "\nAll tests completed!\n";
echo "\nNote: If tests are failing, please check:\n";
echo "1. Your UltraMsg token is correct\n";
echo "2. Your instance ID is correct\n";
echo "3. Your UltraMsg instance is connected and active\n";
echo "4. The phone number is in the correct format\n";
