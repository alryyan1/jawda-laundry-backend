<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
 
    /**
     * Display the current application settings.
     */
    public function index(Request $request)
    {
        // Use the new database settings approach
        $settingsService = app(\App\Services\SettingsService::class);
        $settings = $settingsService->getAll();

        return response()->json(['data' => $settings]);
    }

    /**
     * Update application settings.
     * This method updates the database settings.
     */
    public function update(Request $request)
    {
        try {
            // Get current settings to know which keys are valid
            $settingsService = app(\App\Services\SettingsService::class);
            $currentSettings = $settingsService->getAll();
            $validKeys = array_keys($currentSettings);

            // Define validation rules based on expected types from database
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

            // Update settings in database
            $result = $settingsService->updateMultiple($validatedData);

            if ($result['success']) {
                return response()->json([
                    'message' => 'Settings updated successfully.',
                    'data' => $result['updated']
                ]);
            } else {
                return response()->json([
                    'message' => 'Some settings failed to update.',
                    'errors' => $result['errors'],
                    'updated' => $result['updated']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error("Failed to update database settings: " . $e->getMessage());
            return response()->json(['message' => 'Failed to update settings in database.'], 500);
        }
    }


    /**
     * Upload company logo.
     */
    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            $settingsService = app(\App\Services\SettingsService::class);
            // Delete old logo if it exists
            $oldLogoUrl = $settingsService->get('company_logo_url');
            if ($oldLogoUrl) {
                // Extract path from URL to delete from storage
                $oldPath = str_replace(asset('storage/'), '', $oldLogoUrl);
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Store the new logo
            $path = $request->file('logo')->store('logos', 'public');
            $logoUrl = asset('storage/' . $path);


            // Update the database with the new logo URL
            $settingsService->set('company_logo_url', $logoUrl);

            return response()->json([
                'message' => 'Logo uploaded successfully.',
                'logo_url' => $logoUrl
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to upload logo: " . $e->getMessage());
            return response()->json(['message' => 'Failed to upload logo.'], 500);
        }
    }

    /**
     * Delete company logo.
     */
    public function deleteLogo()
    {
        try {
            $settingsService = app(\App\Services\SettingsService::class);
            $logoUrl = $settingsService->get('company_logo_url');
            if ($logoUrl) {
                // Extract path from URL to delete from storage
                $path = str_replace(asset('storage/'), '', $logoUrl);
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            // Update the database to remove the logo URL
            $settingsService->set('company_logo_url', null);

            return response()->json([
                'message' => 'Logo deleted successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to delete logo: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete logo.'], 500);
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