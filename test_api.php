<?php

// Test the API endpoint directly
$url = 'http://localhost/laundry/jawda-laundry-backend/public/api/auth/login';
$data = [
    'username' => 'admin',
    'password' => '12345678'
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\nAccept: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "URL: $url\n";
echo "Data: " . json_encode($data) . "\n";
echo "Result: $result\n";

if ($result === false) {
    echo "Error: " . error_get_last()['message'] . "\n";
}
?>

