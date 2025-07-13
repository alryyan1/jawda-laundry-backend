<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service class for interacting with the waapi.app WhatsApp API.
 */
class WhatsAppService
{
    protected GuzzleClient $client;
    protected ?string $apiUrl;
    protected ?string $apiToken;
    protected bool $isEnabled;

    public function __construct()
    {
        $this->client = new GuzzleClient([
            'timeout' => 10.0, // Set a timeout for requests
        ]);
        $this->isEnabled = Config::get('whatsapp.enabled', false);
        $this->apiUrl = Config::get('whatsapp.api_url');
        $this->apiToken = Config::get('whatsapp.api_token');
    }
    /**
     * Checks if the service is fully configured and enabled.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return $this->isEnabled && !empty($this->apiToken) && !empty($this->apiUrl);
    }

 /**
     * Send a WhatsApp message.
     *
     * @param string $chatId The recipient's WhatsApp ID (e.g., "249991961111@c.us")
     * @param string $message The message content.
     */
    public function sendMessage(string $chatId, string $message)
    {

        // Log::info("WhatsAppService: Sending message to {$chatId}: {$message}");
        $chatId = $this->formatChatId($chatId);
        if (!$this->isEnabled) {
            Log::info("WhatsAppService: Sending disabled. Message not sent to {$chatId}: {$message}");
            return true; // Or false if you want to indicate a "failure" due to being disabled
        }

        if (empty($this->apiUrl) || empty($this->apiToken)) {
            Log::error("WhatsAppService: API URL or Token is not configured.");
            return [
                'status' => 'error',
                'message' => 'WhatsApp API is not configured.',
                'data' => null
            ];
        }

        if (empty($chatId)) {
            Log::error("WhatsAppService: Chat ID is empty. Cannot send message.");
            return [
                'status' => 'error',
                'message' => 'Chat ID is empty. Cannot send message.',
                'data' => null
            ];
        }

        // Construct the proper API endpoint for sending messages
        $apiEndpoint = rtrim($this->apiUrl, '/') . '/client/action/send-message';
        
        try {
            Log::info("WhatsAppService: Attempting to send message to {$chatId}", [
                'endpoint' => $apiEndpoint,
                'chatId' => $chatId,
                'message' => $message
            ]);
            
            $response = $this->client->request('POST', $apiEndpoint, [
                'json' => [
                    'chatId' => $chatId,
                    'message' => $message,
                ],
                'headers' => [
                    'accept' => 'application/json',
                    'authorization' => 'Bearer '.$this->apiToken,
                ],
                'http_errors' => false, // Don't throw exceptions, handle manually
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            Log::info("WhatsAppService: API Response", [
                'statusCode' => $statusCode,
                'responseBody' => $response,
                'chatId' => $chatId
            ]);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info("WhatsAppService: Message sent successfully to {$chatId}. Status: {$statusCode}", ['response' => $responseBody]);
                return [
                    'status' => 'success',
                    'message' => 'Message sent successfully',
                    'other'=>'ss',
                    'api_response'=>$responseBody,
                    'data' => $responseBody
                ];
            } else {
                Log::error("WhatsAppService: Failed to send message to {$chatId}. Status: {$statusCode}", ['response' => $responseBody]);
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
            Log::error("WhatsAppService: RequestException sending message to {$chatId}", [
                'error' => $errorMessage,
                'endpoint' => $apiEndpoint
            ]);
            return [
                'status' => 'error',
                'message' => 'Request exception: ' . $errorMessage,
                'data' => null
            ];
        } catch (\Exception $e) {
            Log::error("WhatsAppService: Generic Exception sending message to {$chatId}", [
                'error' => $e->getMessage(),
                'endpoint' => $apiEndpoint
            ]);
            return [
                'status' => 'error',
                'message' => 'Generic exception: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Sends a media file encoded in Base64 via WhatsApp.
     *
     * @param string $phoneNumber The recipient's phone number.
     * @param string $base64Data The Base64 encoded string of the file content.
     * @param string $fileName The name of the file (e.g., 'invoice.pdf').
     * @param string|null $caption An optional caption for the media.
     * @return array An array containing the status and API response data.
     */
    public function sendMediaBase64(string $phoneNumber, string $base64Data, string $fileName, ?string $caption = null): array
    {
        $phoneNumber = $this->formatChatId($phoneNumber);
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'WhatsApp service is not configured or enabled in settings.'];
        }

        $payload = [
            'chatId' => $this->formatChatId($phoneNumber),
            'mediaBase64' => $base64Data,
            'mediaName' => $fileName,
        ];
        if ($caption) $payload['mediaCaption'] = $caption;

        return $this->sendRequest('/client/action/send-media', $payload);
    }

    /**
     * Sends a test message to the specified phone number.
     */
    public function sendTestMessage(string $testPhoneNumber)
    {
        $message = "This is a test message from your LaundryPro system. WhatsApp integration is working!";
        
        $result = $this->sendMessage($testPhoneNumber, $message);
        return $result;
        
    }

    /**
     * Centralized method for making API requests.
     */
    private function sendRequest(string $endpoint, array $payload): array
    {
        $fullUrl = rtrim($this->apiUrl, '/') . $endpoint;
        // return ['url' => $fullUrl, 'payload' => $payload,'status' => 'success'];
        Log::info("WhatsApp API request to {$endpoint} was successful.", ['phone' => $payload['chatId']]);

        try {
            $response = Http::withToken($this->apiToken)
                ->acceptJson()
                ->asJson()
                ->post($fullUrl, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp API request to {$endpoint} was successful.", ['phone' => $payload['chatId']]);
                return ['status' => 'success', 'data' => $response->json()];
            } else {
                $errorMessage = $response->json('message', 'API request failed with status ' . $response->status());
                Log::error("WhatsApp API request failed.", [
                    'endpoint' => $endpoint, 'status' => $response->status(), 'response' => $response->json() ?? $response->body()
                ]);
                return ['status' => 'error', 'message' => $errorMessage, 'data' => $response->json()];
            }
        } catch (\Exception $e) {
            Log::critical("Exception while communicating with WhatsApp API.", ['endpoint' => $endpoint, 'message' => $e->getMessage()]);
            return ['status' => 'error', 'message' => 'A critical communication error occurred. Check server logs.'];
        }
    }

    /**
     * Formats a raw phone number into the required "chatId" format.
     */
    private function formatChatId(string $phoneNumber): string
    {
        // Get the country code from config, default to Oman (968)
        $countryCode = Config::get('app_settings.whatsapp_country_code', '968');
        
        // Clean the phone number (remove any non-digits)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If the phone number doesn't start with the country code, add it
        if (!str_starts_with($cleanPhone, $countryCode)) {
            $cleanPhone = $countryCode . $cleanPhone;
        }
        
        return $cleanPhone . '@c.us';
    }
}