<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService; // Import the service for settings
use App\Services\WhatsAppService; // Import the service for WhatsApp
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * The settings service instance.
     */
    protected SettingsService $settings;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\SettingsService  $settingsService
     * @return void
     */
    public function __construct(SettingsService $settingsService)
    {
        $this->settings = $settingsService;

        // Protect all methods in this controller with a single permission
        $this->middleware('can:settings:manage-application');
    }

    /**
     * Get all settings, structured by group for the frontend.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // We use our service to get all settings, which might be cached
        // The frontend will receive a structure like:
        // { "general": { "company_name": "LaundryPro", ... }, "whatsapp": { "api_token": "...", ... } }
        return response()->json([
            'general' => $this->settings->getAll('general'),
            'whatsapp' => $this->settings->getAll('whatsapp'),
        ]);
    }

    /**
     * Update application settings.
     * Expects data structured with group prefixes, e.g., "general[company_name]".
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // General Settings
            'general' => 'sometimes|array',
            'general.company_name' => 'nullable|string|max:255',
            'general.company_address' => 'nullable|string|max:1000',
            'general.company_phone' => 'nullable|string|max:50',
            'general.default_currency' => ['sometimes', 'required', 'string', Rule::in(['USD', 'EUR', 'SAR', 'INR'])], // Example currency list

            // WhatsApp Settings
            'whatsapp' => 'sometimes|array',
            'whatsapp.api_url' => 'nullable|url|max:255',
            'whatsapp.instance_id' => 'nullable|string|max:255',
            'whatsapp.api_token' => 'nullable|string|max:255',
        ]);

        try {
            // Loop through the validated data groups (e.g., 'general', 'whatsapp')
            foreach ($validated as $group => $settings) {
                // Loop through each key-value pair in the group
                foreach ($settings as $key => $value) {
                    // The key in the database will be "group_key", e.g., "whatsapp_api_token"
                    $this->settings->set("{$group}_{$key}", $value, $group);
                }
            }
            
            // Clear the application cache to ensure the new settings are loaded everywhere.
            // This is important because our SettingsService uses caching.
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            return response()->json(['message' => 'Settings updated successfully.']);

        } catch (\Exception $e) {
            Log::error("Failed to update settings: " . $e->getMessage());
            return response()->json(['message' => 'An error occurred while saving settings.'], 500);
        }
    }

    /**
     * Send a test WhatsApp message using the saved credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Services\WhatsAppService  $whatsAppService Laravel will inject this service
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTestWhatsapp(Request $request, WhatsAppService $whatsAppService)
    {
        $validated = $request->validate([
            'test_phone_number' => 'required|string|regex:/^[0-9]+$/|min:7',
        ]);

        if (!$whatsAppService->isConfigured()) {
            return response()->json([
                'message' => 'WhatsApp API credentials are not configured in settings. Please save your Instance ID and Token first.'
            ], 400); // 400 Bad Request
        }

        $result = $whatsAppService->sendTestMessage($validated['test_phone_number']);

        if ($result['status'] === 'success') {
            return response()->json([
                'message' => 'Test message sent successfully!',
                'response' => $result['data'] // Include the API response for debugging
            ]);
        } else {
            // Pass the error message and details from the service to the frontend
            return response()->json([
                'message' => 'Failed to send test message.',
                'details' => $result
            ], 500); // 500 Internal Server Error or 422 if it's a validation-like issue
        }
    }
}