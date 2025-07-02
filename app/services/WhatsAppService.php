<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service class for interacting with the waapi.app WhatsApp API.
 */
class WhatsAppService
{
    protected string $apiUrl;
    protected string $apiToken;
    protected bool $isEnabled;

    /**
     * Create a new service instance.
     * Fetches configuration directly from Laravel's config helper.
     */
    public function __construct()
    {
        $this->isEnabled = config('whatsapp.enabled', false);
        $this->apiUrl = config('whatsapp.api_url', '');
        $this->apiToken = config('whatsapp.api_token', '');
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
     * Sends a simple text message via WhatsApp.
     *
     * @param string $phoneNumber The recipient's phone number (without '+').
     * @param string $message The text message content.
     * @return array An array containing the status and API response data.
     */
    public function sendMessage(string $phoneNumber, string $message): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'WhatsApp service is not configured or enabled in settings.'];
        }

        return $this->sendRequest('/client/action/send-message', [
            'chatId' => $this->formatChatId($phoneNumber),
            'message' => $message,
        ]);
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
    public function sendTestMessage(string $testPhoneNumber): array
    {
        return $this->sendMessage($testPhoneNumber, "This is a test message from your LaundryPro system. WhatsApp integration is working!");
    }

    /**
     * Centralized method for making API requests.
     */
    private function sendRequest(string $endpoint, array $payload): array
    {
        $fullUrl = rtrim($this->apiUrl, '/') . $endpoint;

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
        return preg_replace('/[^0-9]/', '', $phoneNumber) . '@c.us';
    }
}