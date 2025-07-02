<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $apiToken;
    protected string $instanceId;

    /**
     * Create a new service instance.
     * It fetches the necessary credentials from the SettingsService upon instantiation.
     *
     * @param  \App\Services\SettingsService  $settings
     */
    public function __construct(SettingsService $settings)
    {
        // Get credentials from our settings database via the SettingsService.
        // The database keys are "group_key".
        $this->apiUrl = $settings->get('whatsapp_api_url', 'https://waapi.app/api/v1/instances');
        $this->apiToken = $settings->get('whatsapp_api_token');
        $this->instanceId = $settings->get('whatsapp_instance_id');
    }

    /**
     * Checks if the service is fully configured with the necessary credentials.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken) && !empty($this->instanceId) && !empty($this->apiUrl);
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
            Log::warning('WhatsAppService: Attempted to send message but service is not configured.');
            return ['status' => 'error', 'message' => 'WhatsApp service not configured in settings.'];
        }

        return $this->sendRequest(
            "{$this->apiUrl}/{$this->instanceId}/client/action/send-message",
            [
                'chatId' => $this->formatChatId($phoneNumber),
                'message' => $message,
            ]
        );
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
            Log::warning('WhatsAppService: Attempted to send media but service is not configured.');
            return ['status' => 'error', 'message' => 'WhatsApp service not configured in settings.'];
        }

        $payload = [
            'chatId' => $this->formatChatId($phoneNumber),
            'mediaBase64' => $base64Data,
            'mediaName' => $fileName,
        ];

        if ($caption) {
            $payload['mediaCaption'] = $caption;
        }

        return $this->sendRequest(
            "{$this->apiUrl}/{$this->instanceId}/client/action/send-media",
            $payload
        );
    }

    /**
     * Sends a test message to the configured test number.
     *
     * @param string $testPhoneNumber
     * @return array
     */
    public function sendTestMessage(string $testPhoneNumber): array
    {
        return $this->sendMessage($testPhoneNumber, "This is a test message from your LaundryPro system. WhatsApp integration is working!");
    }

    /**
     * A centralized method to handle making requests to the WhatsApp API.
     *
     * @param string $url The full endpoint URL.
     * @param array $payload The data to be sent in the request body.
     * @return array
     */
    private function sendRequest(string $url, array $payload): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->acceptJson()
                ->asJson() // Ensure the content-type header is set to application/json
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info("WhatsApp API request successful.", ['url' => $url, 'response' => $response->json()]);
                return ['status' => 'success', 'data' => $response->json()];
            } else {
                Log::error("WhatsApp API request failed.", [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->json() ?? $response->body()
                ]);
                $errorMessage = $response->json('data.message', 'API request failed with status ' . $response->status());
                return ['status' => 'error', 'message' => $errorMessage, 'data' => $response->json()];
            }

        } catch (\Exception $e) {
            Log::critical("Exception occurred while communicating with WhatsApp API.", [
                'url' => $url,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['status' => 'error', 'message' => 'A critical error occurred while sending the message. Check logs.'];
        }
    }

    /**
     * Formats a raw phone number into the required "chatId" format.
     *
     * @param string $phoneNumber
     * @return string
     */
    private function formatChatId(string $phoneNumber): string
    {
        // Sanitize the number to remove anything that isn't a digit
        $digitsOnly = preg_replace('/[^0-9]/', '', $phoneNumber);
        // Append the required suffix for a personal chat
        return $digitsOnly . '@c.us';
    }
}