<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service class for interacting with the WA Client WhatsApp API.
 */
class WhatsAppService
{
    protected GuzzleClient $client;
    protected ?string $apiUrl;
    protected ?string $apiToken;
    protected ?string $instanceId;
    protected bool $isEnabled;

    public function __construct()
    {
        $this->client = new GuzzleClient([
            'timeout' => 30.0, // Increased timeout for WhatsApp API
        ]);
        
        // Get settings from database
        $settingsService = app(\App\Services\SettingsService::class);
        $whatsappConfig = $settingsService->getWhatsAppConfig();
        
        $this->isEnabled = $whatsappConfig['enabled'] ?? false;
        
        // Use UltraMsg exclusively
        $this->apiToken = $whatsappConfig['ultramsg_token'] ?? '';
        $this->instanceId = $whatsappConfig['ultramsg_instance_id'] ?? '';
        $this->apiUrl = "https://api.ultramsg.com/{$this->instanceId}";
    }

    /**
     * Checks if the service is fully configured and enabled.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->isEnabled && !empty($this->apiToken) && !empty($this->instanceId);
    }

    /**
     * Get the API token (for testing purposes)
     */
    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    /**
     * Get the instance ID (for testing purposes)
     */
    public function getInstanceId(): ?string
    {
        return $this->instanceId;
    }

    /**
     * Get the API URL (for testing purposes)
     */
    public function getApiUrl(): ?string
    {
        return $this->apiUrl;
    }

    /**
     * Send a WhatsApp message.
     *
     * @param string $phoneNumber The recipient's phone number
     * @param string $message The message content
     * @return array Response status and data
     */
    public function sendMessage(string $phoneNumber, string $message): array
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        if (!$this->isEnabled) {
            Log::info("WhatsAppService: Sending disabled. Message not sent to {$phoneNumber}: {$message}");
            return [
                'status' => 'success',
                'message' => 'WhatsApp is disabled, message not sent',
                'data' => null
            ];
        }

        if (!$this->isConfigured()) {
            Log::error("WhatsAppService: API is not properly configured.");
            return [
                'status' => 'error',
                'message' => 'WhatsApp API is not properly configured. Please check your settings.',
                'data' => null
            ];
        }

        if (empty($phoneNumber)) {
            Log::error("WhatsAppService: Phone number is empty. Cannot send message.");
            return [
                'status' => 'error',
                'message' => 'Phone number is empty. Cannot send message.',
                'data' => null
            ];
        }

        try {
            Log::info("WhatsAppService: Attempting to send message to {$phoneNumber}", [
                'phone' => $phoneNumber,
                'message' => $message,
                'instance_id' => $this->instanceId
            ]);
            
            // Use UltraMsg API exclusively
            $params = [
                'token' => $this->apiToken,
                'to' => $phoneNumber,
                'body' => $message
            ];
            
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            $options = ['form_params' => $params];
            $guzzleRequest = new \GuzzleHttp\Psr7\Request('POST', "{$this->apiUrl}/messages/chat", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info("WhatsAppService: API Response", [
                'statusCode' => $statusCode,
                'responseBody' => $responseBody,
                'phone' => $phoneNumber
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                // UltraMsg API response handling
                Log::info("WhatsAppService: Message sent successfully to {$phoneNumber}. Status: {$statusCode}", ['response' => $responseBody]);
                return [
                    'status' => 'success',
                    'message' => 'Message sent successfully',
                    'data' => $responseBody
                ];
            } else {
                Log::error("WhatsAppService: Failed to send message to {$phoneNumber}. Status: {$statusCode}", ['response' => $responseBody]);
                return [
                    'status' => 'error',
                    'message' => 'Failed to send message. API returned status: ' . $statusCode,
                    'data' => $responseBody
                ];
            }
        } catch (RequestException $e) {
            $errorMessage = $e->getMessage();
            if ($e->hasResponse()) {
                $responseBody = $e->getResponse()->getBody()->getContents();
                $errorMessage .= " | Response: " . $responseBody;
            }
            Log::error("WhatsAppService: RequestException sending message to {$phoneNumber}", [
                'error' => $errorMessage
            ]);
            return [
                'status' => 'error',
                'message' => 'Request exception: ' . $errorMessage,
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error("WhatsAppService: Generic Exception sending message to {$phoneNumber}", [
                'error' => $e->getMessage()
            ]);
            return [
                'status' => 'error',
                'message' => 'Generic exception: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Sends a media file via WhatsApp.
     *
     * @param string $phoneNumber The recipient's phone number
     * @param string $mediaUrl The URL of the media file
     * @param string $fileName The name of the file
     * @param string|null $caption An optional caption for the media
     * @return array Response status and data
     */
    public function sendMedia(string $phoneNumber, string $mediaUrl, string $fileName, ?string $caption = null): array
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'WhatsApp service is not configured or is disabled in settings.'];
        }

        try {
            Log::info("WhatsAppService: Sending media to {$phoneNumber}", [
                'phone' => $phoneNumber,
                'media_url' => $mediaUrl,
                'filename' => $fileName,
                'caption' => $caption,
                'instance_id' => $this->instanceId
            ]);

            // Use UltraMsg API for document sending (for PDFs)
            $params = [
                'token' => $this->apiToken,
                'to' => $phoneNumber,
                'document' => $mediaUrl,
                'filename' => $fileName
            ];
            
            if ($caption) {
                $params['caption'] = $caption;
            }

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            $options = ['form_params' => $params];
            $guzzleRequest = new \GuzzleHttp\Psr7\Request('POST', "{$this->apiUrl}/messages/document", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info("WhatsAppService: Media API Response", [
                'statusCode' => $statusCode,
                'responseBody' => $responseBody,
                'phone' => $phoneNumber,
                'media_url' => $mediaUrl
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("WhatsAppService: Media sent successfully to {$phoneNumber}", ['response' => $responseBody]);
                return ['status' => 'success', 'data' => $responseBody];
            } else {
                Log::error("WhatsAppService: Failed to send media to {$phoneNumber}", [
                    'statusCode' => $statusCode,
                    'response' => $responseBody,
                    'media_url' => $mediaUrl
                ]);
                return ['status' => 'error', 'message' => 'Failed to send media. API returned status: ' . $statusCode, 'data' => $responseBody];
            }
        } catch (\Exception $e) {
            Log::error("WhatsAppService: Exception sending media to {$phoneNumber}", [
                'error' => $e->getMessage(),
                'media_url' => $mediaUrl,
                'filename' => $fileName
            ]);
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Send media via Base64 content using Firebase Storage
     */
    public function sendMediaBase64($phoneNumber, $base64Content, $fileName, $caption = '')
    {
        try {
            Log::info("WhatsAppService: Sending media via Base64 to {$phoneNumber}", [
                'fileName' => $fileName,
                'caption' => $caption,
                'contentLength' => strlen($base64Content)
            ]);

            // Use Cloud Storage to upload and get public URL
            $cloudStorageService = app(\App\Services\CloudStorageService::class);
            
            if (!$cloudStorageService->isConfigured()) {
                Log::error("WhatsAppService: Cloud Storage not configured, falling back to local storage");
                return $this->sendMediaBase64Local($phoneNumber, $base64Content, $fileName, $caption);
            }

            // Upload to Cloud Storage
            $uploadResult = $cloudStorageService->uploadBase64($base64Content, $fileName);
            
            if ($uploadResult['status'] !== 'success') {
                Log::error("WhatsAppService: Failed to upload to Cloud Storage", [
                    'error' => $uploadResult['message'],
                    'phoneNumber' => $phoneNumber
                ]);
                
                // Fallback to local storage
                return $this->sendMediaBase64Local($phoneNumber, $base64Content, $fileName, $caption);
            }

            $publicUrl = $uploadResult['url'];
            
            Log::info("WhatsAppService: File uploaded to Cloud Storage", [
                'fileName' => $fileName,
                'publicUrl' => $publicUrl,
                'fileSize' => $uploadResult['size']
            ]);

            // Send media using the public URL
            return $this->sendMedia($phoneNumber, $publicUrl, $fileName, $caption);

        } catch (Exception $e) {
            Log::error("WhatsAppService: Error in sendMediaBase64", [
                'phoneNumber' => $phoneNumber,
                'fileName' => $fileName,
                'error' => $e->getMessage()
            ]);

            // Fallback to local storage
            return $this->sendMediaBase64Local($phoneNumber, $base64Content, $fileName, $caption);
        }
    }

    /**
     * Send media via Base64 content using local storage (fallback method)
     */
    private function sendMediaBase64Local($phoneNumber, $base64Content, $fileName, $caption = '')
    {
        try {
            // Ensure the directory exists
            $directory = storage_path('app/public/whatsapp');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Decode Base64 content
            $fileContent = base64_decode($base64Content);
            if ($fileContent === false) {
                throw new Exception('Invalid Base64 content');
            }

            // Save file to local storage
            $filePath = $directory . '/' . $fileName;
            file_put_contents($filePath, $fileContent);

            // Generate public URL using environment variable or fallback
            $publicUrl = env('WHATSAPP_PUBLIC_URL', 'http://192.168.137.1/laundry/jawda-laundry-backend/public') . '/storage/whatsapp/' . $fileName;
            
            Log::info("WhatsAppService: Sending media via local storage", [
                'phoneNumber' => $phoneNumber,
                'fileName' => $fileName,
                'publicUrl' => $publicUrl,
                'fileSize' => strlen($fileContent)
            ]);

            // Send media using the public URL
            return $this->sendMedia($phoneNumber, $publicUrl, $fileName, $caption);

        } catch (Exception $e) {
            Log::error("WhatsAppService: Error in sendMediaBase64Local", [
                'phoneNumber' => $phoneNumber,
                'fileName' => $fileName,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to process Base64 content: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clean up old WhatsApp files to prevent storage bloat
     */
    private function cleanupOldFiles(): void
    {
        try {
            $whatsappDir = storage_path('app/public/whatsapp');
            if (!is_dir($whatsappDir)) {
                return;
            }
            
            $files = glob($whatsappDir . '/*');
            $cutoffTime = time() - (24 * 3600); // 24 hours ago
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    unlink($file);
                    Log::info("WhatsAppService: Cleaned up old file", ['file' => basename($file)]);
                }
            }
        } catch (\Exception $e) {
            Log::warning("WhatsAppService: Failed to cleanup old files", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Schedule file cleanup after a specified delay
     */
    private function scheduleFileCleanup(string $filePath, int $delaySeconds): void
    {
        // In a production environment, you might want to use Laravel's job queue
        // For now, we'll just log that cleanup should happen
        Log::info("WhatsAppService: File cleanup scheduled", [
            'file' => basename($filePath),
            'cleanupTime' => date('Y-m-d H:i:s', time() + $delaySeconds)
        ]);
        
        // You could implement a proper job queue here:
        // dispatch(new CleanupWhatsAppFile($filePath))->delay(now()->addSeconds($delaySeconds));
    }

    /**
     * Sends a test message to the specified phone number.
     */
    public function sendTestMessage(string $testPhoneNumber): array
    {
        $message = "This is a test message from your RestaurantPro system. WhatsApp integration is working!";
        
        return $this->sendMessage($testPhoneNumber, $message);
    }

    /**
     * Check if a phone number is registered with WhatsApp
     */
    public function checkNumber(string $phoneNumber): array
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'WhatsApp service is not configured.'];
        }

        try {
            // Use UltraMsg API for checking number
            $params = [
                'token' => $this->apiToken,
                'to' => $phoneNumber
            ];

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            $options = ['form_params' => $params];
            $guzzleRequest = new \GuzzleHttp\Psr7\Request('POST', "{$this->apiUrl}/messages/chat", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                return ['status' => 'success', 'data' => $responseBody];
            } else {
                return ['status' => 'error', 'message' => 'Failed to check number', 'data' => $responseBody];
            }
        } catch (\Exception $e) {
            Log::error("WhatsAppService: Exception checking number {$phoneNumber}", ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Get QR code for WhatsApp Web authentication
     */
    public function getQRCode(): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'WhatsApp service is not configured.'];
        }

        try {
            // Use UltraMsg API for getting instance status
            $params = [
                'token' => $this->apiToken
            ];

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            $options = ['form_params' => $params];
            $guzzleRequest = new \GuzzleHttp\Psr7\Request('GET', "{$this->apiUrl}/instance/status", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                return ['status' => 'success', 'data' => $responseBody];
            } else {
                return ['status' => 'error', 'message' => 'Failed to get QR code', 'data' => $responseBody];
            }
        } catch (\Exception $e) {
            Log::error("WhatsAppService: Exception getting QR code", ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Get pairing code for WhatsApp Web authentication
     */
    public function getPairingCode(string $phoneNumber): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'WhatsApp service is not configured.'];
        }

        try {
            // Use UltraMsg API for getting pairing code
            $params = [
                'token' => $this->apiToken,
                'phone' => $phoneNumber
            ];

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];
            
            $options = ['form_params' => $params];
            $guzzleRequest = new \GuzzleHttp\Psr7\Request('POST', "{$this->apiUrl}/messages/chat", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                return ['status' => 'success', 'data' => $responseBody];
            } else {
                return ['status' => 'error', 'message' => 'Failed to get pairing code', 'data' => $responseBody];
            }
        } catch (\Exception $e) {
            Log::error("WhatsAppService: Exception getting pairing code", ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Formats a raw phone number into the required format for UltraMsg API.
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Get the country code from database settings, default to Oman (968)
        $settingsService = app(\App\Services\SettingsService::class);
        $whatsappConfig = $settingsService->getWhatsAppConfig();
        $countryCode = $whatsappConfig['country_code'] ?? '971';
        
        // Clean the phone number (remove any non-digits, spaces, dashes, plus signs)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If the phone number doesn't start with the country code, add it
        if (!str_starts_with($cleanPhone, $countryCode)) {
            $cleanPhone = $countryCode . $cleanPhone;
        }
        
        // Ensure the phone number is at least 10 digits (country code + phone)
        if (strlen($cleanPhone) < 10) {
            Log::warning("WhatsAppService: Phone number too short", [
                'original' => $phoneNumber,
                'cleaned' => $cleanPhone,
                'length' => strlen($cleanPhone)
            ]);
        }
        
        // Format for UltraMsg API: add + prefix
        $formattedPhone = '+' . $cleanPhone;
        
        Log::info("WhatsAppService: Phone number formatted", [
            'original' => $phoneNumber,
            'formatted' => $formattedPhone,
            'countryCode' => $countryCode
        ]);
        
        return $formattedPhone;
    }
}