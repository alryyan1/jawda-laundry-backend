<?php
/**
 * Check WhatsApp Settings in Database
 */

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "WhatsApp Settings Check\n";
echo "======================\n\n";

// Check WhatsApp settings
$settings = \Illuminate\Support\Facades\DB::table('settings')->where('group', 'whatsapp')->get();

echo "WhatsApp Settings in Database:\n";
echo "==============================\n";

foreach ($settings as $setting) {
    echo "{$setting->key}: {$setting->value}\n";
}

echo "\n";

// Test WhatsAppService configuration
echo "WhatsAppService Configuration Test:\n";
echo "===================================\n";

$whatsAppService = new \App\Services\WhatsAppService();
echo "Is Configured: " . ($whatsAppService->isConfigured() ? 'Yes' : 'No') . "\n";

if ($whatsAppService->isConfigured()) {
    echo "API Token: " . substr($whatsAppService->getApiToken() ?? '', 0, 8) . "...\n";
    echo "Instance ID: " . ($whatsAppService->getInstanceId() ?? 'Not set') . "\n";
    echo "API URL: " . ($whatsAppService->getApiUrl() ?? 'Not set') . "\n";
} else {
    echo "WhatsApp service is not properly configured!\n";
}

echo "\nTest completed!\n";
