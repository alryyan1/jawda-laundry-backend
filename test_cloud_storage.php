<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Cloud Storage Integration\n";
echo "================================\n\n";

try {
    // Check if Cloud Storage is configured
    $cloudStorageService = app(\App\Services\CloudStorageService::class);
    
    if (!$cloudStorageService->isConfigured()) {
        echo "❌ Cloud Storage is not configured.\n";
        echo "\n📋 Available Cloud Storage Options:\n";
        
        $availableTypes = $cloudStorageService->getAvailableTypes();
        foreach ($availableTypes as $type => $info) {
            echo "\n{$info['name']} ({$type}):\n";
            echo "   {$info['description']}\n";
            echo "   Setup: {$info['setup']}\n";
        }
        
        echo "\n🔄 Falling back to local storage test...\n";
        
        // Test local storage fallback
        $whatsAppService = app(\App\Services\WhatsAppService::class);
        
        if (!$whatsAppService->isConfigured()) {
            echo "❌ WhatsApp service is also not configured.\n";
            exit;
        }
        
        echo "✅ WhatsApp service is configured.\n";
        
        // Create a test PDF
        $testPdfContent = "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n2 0 obj\n<<\n/Type /Pages\n/Kids [3 0 R]\n/Count 1\n>>\nendobj\n3 0 obj\n<<\n/Type /Page\n/Parent 2 0 R\n/MediaBox [0 0 612 792]\n/Contents 4 0 R\n>>\nendobj\n4 0 obj\n<<\n/Length 50\n>>\nstream\nBT\n/F1 12 Tf\n72 720 Td\n(Test PDF) Tj\nET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000204 00000 n \ntrailer\n<<\n/Size 5\n/Root 1 0 R\n>>\nstartxref\n300\n%%EOF";
        
        $base64Content = base64_encode($testPdfContent);
        $fileName = 'test-firebase-fallback-' . date('Y-m-d-H-i-s') . '.pdf';
        $caption = 'Test PDF - Firebase Storage Fallback - ' . date('Y-m-d H:i:s');
        
        echo "\n📄 Testing Local Storage Fallback:\n";
        echo "   File: {$fileName}\n";
        echo "   Size: " . strlen($testPdfContent) . " bytes\n";
        
        $result = $whatsAppService->sendMediaBase64('249991961111', $base64Content, $fileName, $caption);
        
        echo "\n📊 Result:\n";
        echo "   Status: " . $result['status'] . "\n";
        if (isset($result['message'])) {
            echo "   Message: " . $result['message'] . "\n";
        }
        
    } else {
        echo "✅ Cloud Storage is configured.\n";
        
        // Test Cloud Storage upload
        echo "\n📤 Testing Cloud Storage Upload...\n";
        
        // Create a test file
        $testContent = "This is a test file for Cloud Storage integration.\nCreated at: " . date('Y-m-d H:i:s') . "\n";
        $fileName = 'test-cloud-' . date('Y-m-d-H-i-s') . '.txt';
        
        // Save to temp file first
        $tempPath = storage_path('app/temp/' . $fileName);
        $tempDir = dirname($tempPath);
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        file_put_contents($tempPath, $testContent);
        
        echo "   File: {$fileName}\n";
        echo "   Size: " . strlen($testContent) . " bytes\n";
        
        // Upload to Cloud Storage
        $result = $cloudStorageService->uploadFile($tempPath, 'test/' . $fileName);
        
        if ($result['status'] === 'success') {
            echo "   ✅ Upload successful!\n";
            echo "   🔗 Public URL: {$result['url']}\n";
            echo "   📁 Storage Path: {$result['path']}\n";
            echo "   📏 File Size: {$result['size']} bytes\n";
            
            // Test URL accessibility
            echo "\n🔍 Testing URL accessibility...\n";
            $context = stream_context_create(['http' => ['timeout' => 10]]);
            $fileContents = @file_get_contents($result['url'], false, $context);
            
            if ($fileContents !== false) {
                echo "   ✅ URL is accessible via HTTP\n";
                echo "   📥 Downloaded: " . strlen($fileContents) . " bytes\n";
                
                // Test Base64 upload
                echo "\n🔄 Testing Base64 upload to Cloud Storage...\n";
                $base64Content = base64_encode($testContent);
                $base64Result = $cloudStorageService->uploadBase64($base64Content, 'base64-' . $fileName);
                
                if ($base64Result['status'] === 'success') {
                    echo "   ✅ Base64 upload successful!\n";
                    echo "   🔗 Public URL: {$base64Result['url']}\n";
                } else {
                    echo "   ❌ Base64 upload failed: {$base64Result['message']}\n";
                }
                
            } else {
                echo "   ❌ URL is NOT accessible via HTTP\n";
            }
            
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            
        } else {
            echo "   ❌ Upload failed: {$result['message']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Test completed.\n";
