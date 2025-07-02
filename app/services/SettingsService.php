<?php
namespace App\Services;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Get a setting value by its key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Cache::rememberForever('setting_' . $key, function () use ($key, $default) {
            return Setting::where('key', $key)->first()?->value ?? $default;
        });
    }

    /**
     * Set a setting value.
     *
     * @param string $key
     * @param mixed $value
     * @param string $group
     * @return Setting
     */
    public function set(string $key, $value, string $group = 'general'): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
        
        // Forget the old cache value so it can be refreshed on next get()
        Cache::forget('setting_' . $key);

        return $setting;
    }

    /**
     * Get all settings, optionally filtered by group.
     *
     * @param string|null $group
     * @return \Illuminate\Support\Collection
     */
    public function getAll(string $group = null)
    {
        $query = Setting::query();
        if ($group) {
            $query->where('group', $group);
        }
        return $query->pluck('value', 'key');
    }
}