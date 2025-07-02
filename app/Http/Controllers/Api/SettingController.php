<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
// use App\Models\Setting; // If you have a Setting model

class SettingController extends Controller
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settingsService)
    {
        $this->settings = $settingsService;
        // Protect all methods with a single permission for managing app settings
        $this->middleware('can:settings:manage-application');
    }

    /**
     * Get all settings, structured by their config file name for the frontend.
     */
    public function index()
    {
        return response()->json([
            'general' => $this->settings->getAll('app_settings'),
            'whatsapp' => $this->settings->getAll('whatsapp'),
        ]);
    }

    /**
     * Update the specified application settings.
     * (Requires admin or specific permissions)
     */
    public function update(Request $request)
    {
        // $this->authorize('update', Setting::class); // Example authorization

        $validated = $request->validate([
            'general' => 'sometimes|array',
            'general.company_name' => 'sometimes|nullable|string|max:255',
            'general.company_address' => 'sometimes|nullable|string|max:1000',
            'general.company_phone' => 'sometimes|nullable|string|max:50',
            'general.currency_symbol' => 'sometimes|nullable|string|max:5',
            
            'whatsapp' => 'sometimes|array',
            'whatsapp.api_url' => 'sometimes|nullable|url',
            'whatsapp.api_token' => 'sometimes|nullable|string',
            'whatsapp.enabled' => 'sometimes|boolean',
        ]);

        try {
            // Prepare data for .env update
            $envData = [];
            
            if (isset($validated['general'])) {
                foreach ($validated['general'] as $key => $value) {
                    // Map frontend field names to .env variable names
                    $envKeyMap = [
                        'company_name' => 'APP_SETTINGS_COMPANY_NAME',
                        'company_address' => 'APP_SETTINGS_COMPANY_ADDRESS',
                        'company_phone' => 'APP_SETTINGS_COMPANY_PHONE',
                        'currency_symbol' => 'APP_SETTINGS_CURRENCY_SYMBOL',
                    ];
                    
                    $envKey = $envKeyMap[$key] ?? 'APP_SETTINGS_' . strtoupper($key);
                    $envData[$envKey] = $value;
                }
            }
            
            if (isset($validated['whatsapp'])) {
                foreach ($validated['whatsapp'] as $key => $value) {
                    // Map frontend field names to .env variable names
                    $envKeyMap = [
                        'enabled' => 'WHATSAPP_API_ENABLED',
                        'api_url' => 'WHATSAPP_API_URL',
                        'api_token' => 'WHATSAPP_API_TOKEN',
                    ];
                    
                    $envKey = $envKeyMap[$key] ?? 'WHATSAPP_' . strtoupper($key);
                    
                    // Convert boolean to string for .env file
                    if (is_bool($value)) {
                        $envData[$envKey] = $value ? 'true' : 'false';
                    } else {
                        $envData[$envKey] = $value;
                    }
                }
            }

            // Update .env file
            if (!empty($envData)) {
                $this->settings->setAndSave($envData);
                
                // Clear config cache to reload the updated .env values
                \Illuminate\Support\Facades\Artisan::call('config:clear');
            }

            return response()->json(['message' => 'Settings updated successfully.']);
        } catch (\Exception $e) {
            Log::error("Failed to update settings: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update settings.'], 500);
        }
    }

    /**
     * Send a test WhatsApp message.
     */
    public function sendTestWhatsapp(Request $request, WhatsAppService $whatsAppService)
    {
        $validated = $request->validate([
            'test_phone_number' => 'required|string|regex:/^[0-9]+$/|min:7',
        ]);

        if (!$whatsAppService->isConfigured()) {
            return response()->json([
                'message' => 'WhatsApp API is not configured or is disabled in settings. Please save your credentials first.'
            ], 400);
        }

        $result = $whatsAppService->sendTestMessage($validated['test_phone_number']);

        if ($result['status'] === 'success') {
            return response()->json([
                'message' => 'Test message sent successfully!',
                'response' => $result['data']
            ]);
        } else {
            return response()->json([
                'message' => 'Failed to send test message.',
                'details' => $result['message'] ?? 'An unknown error occurred.',
                'api_response' => $result['data'] ?? null
            ], 500);
        }
    }
}