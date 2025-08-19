<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SettingsService
{
    /**
     * Get a setting value
     */
    public function get(string $key, $default = null)
    {
        return Setting::getValue($key, $default);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, $value, string $type = 'string'): bool
    {
        return Setting::setValue($key, $value, $type);
    }

    /**
     * Get all settings
     */
    public function getAll(): array
    {
        return Setting::getAllSettings();
    }

    /**
     * Get settings by group
     */
    public function getByGroup(string $group): array
    {
        return Setting::getSettingsByGroup($group);
    }

    /**
     * Get public settings
     */
    public function getPublic(): array
    {
        return Setting::getPublicSettings();
    }

    /**
     * Update multiple settings
     */
    public function updateMultiple(array $settings): array
    {
        $results = [];
        $errors = [];

        foreach ($settings as $key => $value) {
            try {
                $setting = Setting::where('key', $key)->first();
                
                if (!$setting) {
                    $errors[] = "Setting '{$key}' not found";
                    continue;
                }

                $success = Setting::setValue($key, $value, $setting->type);
                
                if ($success) {
                    $results[$key] = $value;
                } else {
                    $errors[] = "Failed to update setting '{$key}'";
                }
            } catch (\Exception $e) {
                Log::error("Error updating setting {$key}: " . $e->getMessage());
                $errors[] = "Error updating setting '{$key}': " . $e->getMessage();
            }
        }

        // Clear all caches after bulk update
        Setting::clearCache();

        return [
            'success' => empty($errors),
            'updated' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Get company settings
     */
    public function getCompanySettings(): array
    {
        return $this->getByGroup('company');
    }

    /**
     * Get app settings
     */
    public function getAppSettings(): array
    {
        return $this->getByGroup('app');
    }

    /**
     * Get general settings
     */
    public function getGeneralSettings(): array
    {
        return $this->getByGroup('general');
    }

    /**
     * Get POS settings
     */
    public function getPosSettings(): array
    {
        return $this->getByGroup('pos');
    }

    /**
     * Get WhatsApp settings
     */
    public function getWhatsAppSettings(): array
    {
        return $this->getByGroup('whatsapp');
    }

    /**
     * Get theme settings
     */
    public function getThemeSettings(): array
    {
        return $this->getByGroup('theme');
    }

    /**
     * Get invoice settings
     */
    public function getInvoiceSettings(): array
    {
        return $this->getByGroup('invoice');
    }

    /**
     * Get payment settings
     */
    public function getPaymentSettings(): array
    {
        return $this->getByGroup('payment');
    }

    /**
     * Check if WhatsApp is enabled
     */
    public function isWhatsAppEnabled(): bool
    {
        return $this->get('whatsapp_enabled', false);
    }

    /**
     * Get WhatsApp configuration
     */
    public function getWhatsAppConfig(): array
    {
        return [
            'enabled' => $this->get('whatsapp_enabled', false),
            'notification_number' => $this->get('whatsapp_notification_number', ''),
            'country_code' => $this->get('whatsapp_country_code', '968'),
            // UltraMsg configuration
            'ultramsg_token' => $this->get('ultramsg_token', ''),
            'ultramsg_instance_id' => $this->get('ultramsg_instance_id', ''),
        ];
    }

    /**
     * Get POS configuration
     */
    public function getPosConfig(): array
    {
        return [
            'auto_show_pdf' => $this->get('pos_auto_show_pdf', false),
            'show_products_as_list' => $this->get('pos_show_products_as_list', false),
            'auto_send_whatsapp_invoice' => $this->get('pos_auto_send_whatsapp_invoice', false),
            'auto_send_whatsapp_text' => $this->get('pos_auto_send_whatsapp_text', false),
        ];
    }

    /**
     * Get theme configuration
     */
    public function getThemeConfig(): array
    {
        return [
            'primary_color' => $this->get('theme_primary_color', 'sky'),
            'secondary_color' => $this->get('theme_secondary_color', 'blue'),
        ];
    }

    /**
     * Get company information
     */
    public function getCompanyInfo(): array
    {
        return [
            'name' => $this->get('company_name', 'My Awesome Company'),
            'address' => $this->get('company_address', ''),
            'phone' => $this->get('company_phone', ''),
            'email' => $this->get('company_email', ''),
            'logo_url' => $this->get('company_logo_url', null),
        ];
    }

    /**
     * Get app branding
     */
    public function getAppBranding(): array
    {
        return [
            'name' => $this->get('app_name', 'Jawda Restaurant'),
            'description' => $this->get('app_description', 'RESTAURANT MANAGEMENT SYSTEM'),
        ];
    }

    /**
     * Clear all settings cache
     */
    public function clearCache(): void
    {
        Setting::clearCache();
    }

    /**
     * Get all settings with metadata for admin interface
     */
    public function getAllWithMetadata(): array
    {
        $settings = Setting::all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = [
                'value' => Setting::getValue($setting->key),
                'type' => $setting->type,
                'group' => $setting->group,
                'display_name' => $setting->display_name,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
            ];
        }

        return $result;
    }
} 