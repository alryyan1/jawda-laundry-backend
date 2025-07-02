<?php

namespace App\Services;

use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Illuminate\Support\Facades\Config;

/**
 * Service class for managing application settings stored in .env and config files.
 */
class SettingsService
{
    /**
     * Get a setting value by its dot-notation config key.
     * The config file itself handles the fallback to .env and default values.
     *
     * @param string $key e.g., 'app_settings.company_name' or 'whatsapp.api_token'
     * @param mixed $default A fallback default if the config key doesn't exist at all.
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Config::get($key, $default);
    }

    /**
     * Get all settings from a specific config file as an associative array.
     *
     * @param string $file e.g., 'app_settings' or 'whatsapp'
     * @return array
     */
    public function getAll(string $file): array
    {
        return Config::get($file, []);
    }

    /**
     * Set and save multiple key-value pairs to the .env file.
     *
     * @param array $data An associative array where keys are the .env variable names.
     *                    e.g., ['APP_SETTINGS_COMPANY_NAME' => 'New Laundry Name', 'WHATSAPP_API_ENABLED' => 'true']
     * @return void
     */
    public function setAndSave(array $data): void
    {
        if (empty($data)) {
            return;
        }
        
        // Use the DotenvEditor package to safely update keys.
        // This preserves comments and formatting in your .env file.
        DotenvEditor::setKeys($data);
        DotenvEditor::save();
    }
}