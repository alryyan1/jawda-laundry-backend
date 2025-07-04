<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
// use App\Models\Setting; // If you have a Setting model

class SettingController extends Controller
{
 
    /**
     * Display the current application settings.
     */
    public function index(Request $request)
    {


        // Fetch settings directly from the config file
        // We use 'app_settings' as the config file name (config/app_settings.php)
        $settings = config('app_settings');

        // Filter out any null values if desired, or return all
        // $settings = array_filter($settings, fn($value) => !is_null($value));

        return response()->json(['data' => $settings]);
    }

    /**
     * Update application settings.
     * This method updates the .env file. BE CAREFUL.
     * A database approach is generally safer and more robust.
     */
    public function update(Request $request)
    {

        // Get current settings to know which keys are valid
        $currentSettings = config('app_settings');
        $validKeys = array_keys($currentSettings);

        // Define validation rules based on expected types from config
        $rules = [];
        foreach ($currentSettings as $key => $value) {
            $rule = ['nullable', 'string', 'max:255']; // Default
            if (is_int($value)) {
                $rule = ['nullable', 'integer', 'min:0'];
            } elseif (is_bool($value)) {
                $rule = ['nullable', 'boolean'];
            } elseif ($key === 'company_email') {
                $rule = ['nullable', 'email', 'max:255'];
            }
            $rules[$key] = $rule;
        }

        $validatedData = $request->validate($rules);

        // --- Updating .env file ---
        // This is a sensitive operation. Ensure proper server permissions and backups.
        $envFilePath = base_path('.env');
        $envFileContent = File::get($envFilePath);

        foreach ($validatedData as $key => $value) {
            if (!in_array($key, $validKeys)) continue; // Skip if key not in our defined settings

            $envKey = 'APP_SETTINGS_' . strtoupper($key); // Match .env variable naming convention
            $escapedValue = is_string($value) && (str_contains($value, ' ') || str_contains($value, '#')) ? "\"{$value}\"" : $value;
            $escapedValue = is_null($value) ? '' : $escapedValue; // Handle null to empty string for .env

            // Replace or add the line in .env
            if (str_contains($envFileContent, "{$envKey}=")) {
                // Update existing line
                $envFileContent = preg_replace("/^{$envKey}=.*/m", "{$envKey}={$escapedValue}", $envFileContent);
            } else {
                // Add new line if it doesn't exist
                $envFileContent .= "\n{$envKey}={$escapedValue}";
            }
        }

        try {
            File::put($envFilePath, $envFileContent);

            // Clear config cache so Laravel reloads the .env values
            Artisan::call('config:clear'); // Important for changes to take effect
            Artisan::call('config:cache');  // Recache for production (optional here, but good practice)

            // Fetch the newly updated settings from the reloaded config
            $newSettings = config('app_settings');

            return response()->json([
                'message' => 'Settings updated successfully. Config cache cleared.',
                'data' => $newSettings
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to update .env file for settings: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update settings file on server.'], 500);
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
        return $result;
        // if ($result['status'] === 'success') {
        //     return response()->json([
        //         'message' => 'Test message sent successfully!',
        //         'response' => $result['data']
        //     ]);
        // } else {
        //     return response()->json([
        //         'message' => 'Failed to send test message.',
        //         'details' => $result['message'] ?? 'An unknown error occurred.',
        //         'api_response' => $result['data'] ?? null
        //     ], 500);
        // }
    }
}