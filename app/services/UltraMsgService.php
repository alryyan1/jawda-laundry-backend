<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service class for interacting with the UltraMsg WhatsApp API.
 */
class UltraMsgService
{
    protected Client $client;
    protected string $baseUrl;
    protected string $token;
    protected string $instanceId;
    protected bool $isEnabled;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30.0,
        ]);

        // Get UltraMsg configuration from settings
        $settingsService = app(\App\Services\SettingsService::class);
        $whatsappConfig = $settingsService->getWhatsAppConfig();
        
        $this->isEnabled = $whatsappConfig['ultramsg_enabled'] ?? false;
        $this->token = $whatsappConfig['ultramsg_token'] ?? '';
        $this->instanceId = $whatsappConfig['ultramsg_instance_id'] ?? '';
        $this->baseUrl = "https://api.ultramsg.com/{$this->instanceId}";
    }

    /**
     * Checks if the service is fully configured and enabled.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->isEnabled && !empty($this->token) && !empty($this->instanceId);
    }

    /**
     * Send a WhatsApp message via UltraMsg API.
     *
     * @param string $phoneNumber The recipient's phone number
     * @param string $message The message content
     * @return array Response status and data
     */
    public function sendMessage(string $phoneNumber, string $message): array
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        if (!$this->isEnabled) {
            Log::info("UltraMsgService: Sending disabled. Message not sent to {$phoneNumber}: {$message}");
            return [
                'status' => 'success',
                'message' => 'UltraMsg is disabled, message not sent',
                'data' => null
            ];
        }

        if (!$this->isConfigured()) {
            Log::error("UltraMsgService: API is not properly configured.");
            return [
                'status' => 'error',
                'message' => 'UltraMsg API is not properly configured. Please check your settings.',
                'data' => null
            ];
        }

        if (empty($phoneNumber)) {
            Log::error("UltraMsgService: Phone number is empty. Cannot send message.");
            return [
                'status' => 'error',
                'message' => 'Phone number is empty. Cannot send message.',
                'data' => null
            ];
        }

        try {
            Log::info("UltraMsgService: Attempting to send message to {$phoneNumber}", [
                'phone' => $phoneNumber,
                'message' => $message,
                'instance_id' => $this->instanceId
            ]);
            
            $params = [
                'token' => $this->token,
                'to' => $phoneNumber,
                'body' => $message
            ];

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $options = ['form_params' => $params];
            $guzzleRequest = new GuzzleRequest('POST', "{$this->baseUrl}/messages/chat", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info("UltraMsgService: API Response", [
                'statusCode' => $statusCode,
                'responseBody' => $responseBody,
                'phone' => $phoneNumber
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("UltraMsgService: Message sent successfully to {$phoneNumber}. Status: {$statusCode}", ['response' => $responseBody]);
                return [
                    'status' => 'success',
                    'message' => 'Message sent successfully',
                    'data' => $responseBody
                ];
            } else {
                Log::error("UltraMsgService: Failed to send message to {$phoneNumber}. Status: {$statusCode}", ['response' => $responseBody]);
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
            Log::error("UltraMsgService: RequestException sending message to {$phoneNumber}", [
                'error' => $errorMessage
            ]);
            return [
                'status' => 'error',
                'message' => 'Request exception: ' . $errorMessage,
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error("UltraMsgService: Generic Exception sending message to {$phoneNumber}", [
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
     * Sends a media file via UltraMsg API.
     *
     * @param string $phoneNumber The recipient's phone number
     * @param string $mediaBase64 The Base64 encoded media content
     * @param string $fileName The name of the file
     * @param string|null $caption An optional caption for the media
     * @return array Response status and data
     */
    public function sendMedia(string $phoneNumber, string $mediaBase64, string $fileName, ?string $caption = null): array
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'UltraMsg service is not configured or is disabled in settings.'];
        }

        try {
            $params = [
                'token' => $this->token,
                'to' => $phoneNumber,
                'media' => $mediaBase64,
                'filename' => $fileName
            ];
            
            if ($caption) {
                $params['caption'] = $caption;
            }

            Log::info("UltraMsgService: Sending media to {$phoneNumber}", [
                'phone' => $phoneNumber,
                'filename' => $fileName,
                'caption' => $caption,
                'media_size' => strlen($mediaBase64)
            ]);

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $options = ['form_params' => $params];
            $guzzleRequest = new GuzzleRequest('POST', "{$this->baseUrl}/messages/media", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info("UltraMsgService: Media API Response", [
                'statusCode' => $statusCode,
                'responseBody' => $responseBody,
                'phone' => $phoneNumber,
                'filename' => $fileName
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("UltraMsgService: Media sent successfully to {$phoneNumber}", ['response' => $responseBody]);
                return ['status' => 'success', 'data' => $responseBody];
            } else {
                Log::error("UltraMsgService: Failed to send media to {$phoneNumber}", [
                    'statusCode' => $statusCode,
                    'response' => $responseBody,
                    'filename' => $fileName
                ]);
                return ['status' => 'error', 'message' => 'Failed to send media. API returned status: ' . $statusCode, 'data' => $responseBody];
            }
        } catch (\Exception $e) {
            Log::error("UltraMsgService: Exception sending media to {$phoneNumber}", [
                'error' => $e->getMessage(),
                'filename' => $fileName
            ]);
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Sends a document via UltraMsg API.
     *
     * @param string $phoneNumber The recipient's phone number
     * @param string $documentBase64 The Base64 encoded document content
     * @param string $fileName The name of the file
     * @param string|null $caption An optional caption for the document
     * @return array Response status and data
     */
    public function sendDocument(string $phoneNumber, string $documentBase64, string $fileName, ?string $caption = null): array
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'UltraMsg service is not configured or is disabled in settings.'];
        }

        try {
            $params = [
                'token' => $this->token,
                'to' => $phoneNumber,
                'document' => $documentBase64,
                'filename' => $fileName
            ];
            
            if ($caption) {
                $params['caption'] = $caption;
            }

            Log::info("UltraMsgService: Sending document to {$phoneNumber}", [
                'phone' => $phoneNumber,
                'filename' => $fileName,
                'caption' => $caption,
                'document_size' => strlen($documentBase64)
            ]);

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $options = ['form_params' => $params];
            $guzzleRequest = new GuzzleRequest('POST', "{$this->baseUrl}/messages/document", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info("UltraMsgService: Document API Response", [
                'statusCode' => $statusCode,
                'responseBody' => $responseBody,
                'phone' => $phoneNumber,
                'filename' => $fileName
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("UltraMsgService: Document sent successfully to {$phoneNumber}", ['response' => $responseBody]);
                return ['status' => 'success', 'data' => $responseBody];
            } else {
                Log::error("UltraMsgService: Failed to send document to {$phoneNumber}", [
                    'statusCode' => $statusCode,
                    'response' => $responseBody,
                    'filename' => $fileName
                ]);
                return ['status' => 'error', 'message' => 'Failed to send document. API returned status: ' . $statusCode, 'data' => $responseBody];
            }
        } catch (\Exception $e) {
            Log::error("UltraMsgService: Exception sending document to {$phoneNumber}", [
                'error' => $e->getMessage(),
                'filename' => $fileName
            ]);
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Get instance status and information
     *
     * @return array Response status and data
     */
    public function getInstanceInfo(): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'UltraMsg service is not configured.'];
        }

        try {
            $params = [
                'token' => $this->token
            ];

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $options = ['form_params' => $params];
            $guzzleRequest = new GuzzleRequest('GET', "{$this->baseUrl}/instance/info", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info('UltraMsgService: Instance Info Response', [
                'statusCode' => $statusCode,
                'responseBody' => $responseBody
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                return ['status' => 'success', 'data' => $responseBody];
            } else {
                return ['status' => 'error', 'message' => 'Failed to get instance info', 'data' => $responseBody];
            }
        } catch (\Exception $e) {
            Log::error('UltraMsgService: Exception getting instance info', ['error' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Get chat history
     *
     * @param string $phoneNumber The phone number to get chat history for
     * @param int $limit Number of messages to retrieve (default: 50)
     * @param int $page Page number (default: 1)
     * @return array Response status and data
     */
    public function getChatHistory(string $phoneNumber, int $limit = 50, int $page = 1): array
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'UltraMsg service is not configured.'];
        }

        try {
            $params = [
                'token' => $this->token,
                'to' => $phoneNumber,
                'limit' => $limit,
                'page' => $page
            ];

            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ];

            $options = ['form_params' => $params];
            $guzzleRequest = new GuzzleRequest('GET', "{$this->baseUrl}/messages", $headers);
            $response = $this->client->sendAsync($guzzleRequest, $options)->wait();

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info('UltraMsgService: Chat History Response', [
                'statusCode' => $statusCode,
                'to' => $phoneNumber,
                'limit' => $limit,
                'page' => $page
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                return ['status' => 'success', 'data' => $responseBody];
            } else {
                return ['status' => 'error', 'message' => 'Failed to get chat history', 'data' => $responseBody];
            }
        } catch (\Exception $e) {
            Log::error('UltraMsgService: Exception getting chat history', [
                'error' => $e->getMessage(),
                'to' => $phoneNumber
            ]);
            return ['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Sends a test message to the specified phone number.
     *
     * @param string $testPhoneNumber The phone number to send test message to
     * @return array Response status and data
     */
    public function sendTestMessage(string $testPhoneNumber): array
    {
        $testMessage = "🧪 This is a test message from your LaundryPro system using UltraMsg API. If you receive this, your WhatsApp integration is working correctly! 📱";
        
        return $this->sendMessage($testPhoneNumber, $testMessage);
    }

    /**
     * Formats a raw phone number into the required format for UltraMsg API.
     *
     * @param string $phoneNumber The phone number to format
     * @return string The formatted phone number
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Get the country code from database settings, default to Oman (968)
        $settingsService = app(\App\Services\SettingsService::class);
        $whatsappConfig = $settingsService->getWhatsAppConfig();
        $countryCode = $whatsappConfig['country_code'] ?? '968';
        
        // Clean the phone number (remove any non-digits, spaces, dashes, plus signs)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If the phone number doesn't start with the country code, add it
        if (!str_starts_with($cleanPhone, $countryCode)) {
            $cleanPhone = $countryCode . $cleanPhone;
        }
        
        // Ensure the phone number is at least 10 digits (country code + phone)
        if (strlen($cleanPhone) < 10) {
            Log::warning("UltraMsgService: Phone number too short", [
                'original' => $phoneNumber,
                'cleaned' => $cleanPhone,
                'length' => strlen($cleanPhone)
            ]);
        }
        
        Log::info("UltraMsgService: Phone number formatted", [
            'original' => $phoneNumber,
            'formatted' => $cleanPhone,
            'countryCode' => $countryCode
        ]);
        
        return $cleanPhone;
    }
}
