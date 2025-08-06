<?php

use App\Models\Setting;

if (!function_exists('app_setting')) {
    /**
     * Get a setting value from the database
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function app_setting(string $key, $default = null)
    {
        return Setting::getValue($key, $default);
    }
}

if (!function_exists('app_settings')) {
    /**
     * Get multiple settings from the database
     * 
     * @param array $keys
     * @return array
     */
    function app_settings(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = Setting::getValue($key);
        }
        return $result;
    }
}

if (!function_exists('app_settings_by_group')) {
    /**
     * Get all settings from a specific group
     * 
     * @param string $group
     * @return array
     */
    function app_settings_by_group(string $group): array
    {
        return Setting::getSettingsByGroup($group);
    }
}

if (!function_exists('app_settings_public')) {
    /**
     * Get all public settings
     * 
     * @return array
     */
    function app_settings_public(): array
    {
        return Setting::getPublicSettings();
    }
} 