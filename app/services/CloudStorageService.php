<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

class CloudStorageService
{
    private $config;

    public function __construct()
    {
        $this->config = [
            'type' => env('CLOUD_STORAGE_TYPE', 'local'),
            'base_url' => env('CLOUD_STORAGE_BASE_URL', ''),
            'api_key' => env('CLOUD_STORAGE_API_KEY', ''),
            'api_secret' => env('CLOUD_STORAGE_API_SECRET', ''),
            'bucket' => env('CLOUD_STORAGE_BUCKET', ''),
            'region' => env('CLOUD_STORAGE_REGION', ''),
        ];
    }

    /**
     * Upload a file to cloud storage and return public URL
     */
    public function uploadFile($filePath, $destinationPath, $makePublic = true)
    {
        try {
            switch ($this->config['type']) {
                case 'imgbb':
                    return $this->uploadToImgBB($filePath, $destinationPath);
                case 'cloudinary':
                    return $this->uploadToCloudinary($filePath, $destinationPath);
                case 'imgur':
                    return $this->uploadToImgur($filePath, $destinationPath);
                case 'local':
                default:
                    return $this->uploadToLocal($filePath, $destinationPath);
            }
        } catch (Exception $e) {
            Log::error("CloudStorageService: Failed to upload file", [
                'error' => $e->getMessage(),
                'filePath' => $filePath,
                'destinationPath' => $destinationPath
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to upload file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload Base64 content to cloud storage
     */
    public function uploadBase64($base64Content, $fileName, $destinationPath = null)
    {
        try {
            // Decode Base64 content
            $fileContent = base64_decode($base64Content);
            if ($fileContent === false) {
                throw new Exception('Invalid Base64 content');
            }

            // Create temporary file
            $tempPath = storage_path('app/temp/' . uniqid() . '_' . $fileName);
            $tempDir = dirname($tempPath);
            
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            file_put_contents($tempPath, $fileContent);

            // Set destination path if not provided
            if (!$destinationPath) {
                $destinationPath = 'whatsapp/' . date('Y/m/d/') . $fileName;
            }

            // Upload to cloud storage
            $result = $this->uploadFile($tempPath, $destinationPath);

            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return $result;

        } catch (Exception $e) {
            Log::error("CloudStorageService: Failed to upload Base64 content", [
                'error' => $e->getMessage(),
                'fileName' => $fileName
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to upload Base64 content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload to ImgBB (Free image hosting)
     */
    private function uploadToImgBB($filePath, $destinationPath)
    {
        if (empty($this->config['api_key'])) {
            throw new Exception('ImgBB API key not configured');
        }

        $response = Http::attach(
            'image',
            file_get_contents($filePath),
            basename($filePath)
        )->post('https://api.imgbb.com/1/upload', [
            'key' => $this->config['api_key']
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['success']) {
                return [
                    'status' => 'success',
                    'url' => $data['data']['url'],
                    'path' => $destinationPath,
                    'size' => filesize($filePath)
                ];
            }
        }

        throw new Exception('ImgBB upload failed: ' . ($response->body() ?? 'Unknown error'));
    }

    /**
     * Upload to Cloudinary (Free tier available)
     */
    private function uploadToCloudinary($filePath, $destinationPath)
    {
        if (empty($this->config['api_key']) || empty($this->config['api_secret'])) {
            throw new Exception('Cloudinary credentials not configured');
        }

        $cloudName = $this->config['bucket'] ?: 'demo';
        
        $response = Http::attach(
            'file',
            file_get_contents($filePath),
            basename($filePath)
        )->post("https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload", [
            'api_key' => $this->config['api_key'],
            'timestamp' => time(),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'status' => 'success',
                'url' => $data['secure_url'],
                'path' => $destinationPath,
                'size' => filesize($filePath)
            ];
        }

        throw new Exception('Cloudinary upload failed: ' . ($response->body() ?? 'Unknown error'));
    }

    /**
     * Upload to Imgur (Free image hosting)
     */
    private function uploadToImgur($filePath, $destinationPath)
    {
        if (empty($this->config['api_key'])) {
            throw new Exception('Imgur client ID not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Client-ID ' . $this->config['api_key']
        ])->attach(
            'image',
            file_get_contents($filePath),
            basename($filePath)
        )->post('https://api.imgur.com/3/image');

        if ($response->successful()) {
            $data = $response->json();
            if ($data['success']) {
                return [
                    'status' => 'success',
                    'url' => $data['data']['link'],
                    'path' => $destinationPath,
                    'size' => filesize($filePath)
                ];
            }
        }

        throw new Exception('Imgur upload failed: ' . ($response->body() ?? 'Unknown error'));
    }

    /**
     * Upload to local storage (fallback)
     */
    private function uploadToLocal($filePath, $destinationPath)
    {
        // Ensure the directory exists
        $directory = storage_path('app/public/whatsapp');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Copy file to local storage
        $localPath = $directory . '/' . basename($destinationPath);
        copy($filePath, $localPath);

        // Generate public URL using environment variable or fallback
        $publicUrl = env('WHATSAPP_PUBLIC_URL', 'http://192.168.137.1/laundry/jawda-laundry-backend/public') . '/storage/whatsapp/' . basename($destinationPath);
        
        return [
            'status' => 'success',
            'url' => $publicUrl,
            'path' => $destinationPath,
            'size' => filesize($filePath)
        ];
    }

    /**
     * Check if cloud storage is configured
     */
    public function isConfigured()
    {
        switch ($this->config['type']) {
            case 'imgbb':
                return !empty($this->config['api_key']);
            case 'cloudinary':
                return !empty($this->config['api_key']) && !empty($this->config['api_secret']);
            case 'imgur':
                return !empty($this->config['api_key']);
            case 'local':
            default:
                return true; // Local storage always available
        }
    }

    /**
     * Get available storage types
     */
    public function getAvailableTypes()
    {
        return [
            'imgbb' => [
                'name' => 'ImgBB',
                'description' => 'Free image hosting, 32MB max, no registration required',
                'url' => 'https://imgbb.com/',
                'setup' => 'Get API key from https://api.imgbb.com/'
            ],
            'cloudinary' => [
                'name' => 'Cloudinary',
                'description' => 'Free tier: 25GB storage, 25GB bandwidth/month',
                'url' => 'https://cloudinary.com/',
                'setup' => 'Sign up and get API key + secret'
            ],
            'imgur' => [
                'name' => 'Imgur',
                'description' => 'Free image hosting, popular platform',
                'url' => 'https://imgur.com/',
                'setup' => 'Get client ID from https://api.imgur.com/oauth2/addclient'
            ],
            'local' => [
                'name' => 'Local Storage',
                'description' => 'Store files locally (requires public internet access)',
                'url' => '',
                'setup' => 'Configure WHATSAPP_PUBLIC_URL in .env'
            ]
        ];
    }
}
