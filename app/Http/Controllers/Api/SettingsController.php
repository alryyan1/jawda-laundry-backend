<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Get all settings
     */
    public function index(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getAllWithMetadata();
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get public settings (accessible without auth)
     */
    public function public(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getPublic();
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch public settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get settings by group
     */
    public function getByGroup(string $group): JsonResponse
    {
        try {
            $settings = $this->settingsService->getByGroup($group);
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to fetch {$group} settings",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific setting
     */
    public function show(string $key): JsonResponse
    {
        try {
            $value = $this->settingsService->get($key);
            
            if ($value === null) {
                return response()->json([
                    'success' => false,
                    'message' => "Setting '{$key}' not found"
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $value
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update settings
     */
    public function update(Request $request): JsonResponse
    {
        try {
            // Get current settings to know which keys are valid and their types
            $currentSettings = $this->settingsService->getAll();
            
            // Build validation rules based on current setting types
            $rules = ['settings' => 'required|array'];
            
            foreach ($currentSettings as $key => $value) {
                if (is_bool($value)) {
                    $rules["settings.{$key}"] = 'nullable|boolean';
                } elseif (is_int($value) || is_numeric($value)) {
                    $rules["settings.{$key}"] = 'nullable|numeric';
                } elseif ($key === 'company_email') {
                    $rules["settings.{$key}"] = 'nullable|email|max:255';
                } else {
                    $rules["settings.{$key}"] = 'nullable|string|max:255';
                }
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $this->settingsService->updateMultiple($request->input('settings'));
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Settings updated successfully',
                    'data' => $result['updated']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Some settings failed to update',
                    'errors' => $result['errors'],
                    'updated' => $result['updated']
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a single setting
     */
    public function updateSingle(Request $request, string $key): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'value' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $this->settingsService->set($key, $request->input('value'));
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Setting updated successfully',
                    'data' => [
                        'key' => $key,
                        'value' => $request->input('value')
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Setting '{$key}' not found or failed to update"
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear settings cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $this->settingsService->clearCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Settings cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear settings cache',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get company information
     */
    public function companyInfo(): JsonResponse
    {
        try {
            $companyInfo = $this->settingsService->getCompanyInfo();
            
            return response()->json([
                'success' => true,
                'data' => $companyInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch company information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get app branding
     */
    public function appBranding(): JsonResponse
    {
        try {
            $appBranding = $this->settingsService->getAppBranding();
            
            return response()->json([
                'success' => true,
                'data' => $appBranding
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch app branding',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get POS configuration
     */
    public function posConfig(): JsonResponse
    {
        try {
            $posConfig = $this->settingsService->getPosConfig();
            
            return response()->json([
                'success' => true,
                'data' => $posConfig
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch POS configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WhatsApp configuration
     */
    public function whatsappConfig(): JsonResponse
    {
        try {
            $whatsappConfig = $this->settingsService->getWhatsAppConfig();
            
            return response()->json([
                'success' => true,
                'data' => $whatsappConfig
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch WhatsApp configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get theme configuration
     */
    public function themeConfig(): JsonResponse
    {
        try {
            $themeConfig = $this->settingsService->getThemeConfig();
            
            return response()->json([
                'success' => true,
                'data' => $themeConfig
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch theme configuration',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
