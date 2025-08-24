<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove WA Client settings
        DB::table('settings')->whereIn('key', [
            'whatsapp_api_token',
            'whatsapp_instance_id',
            'whatsapp_api_url',
            'ultramsg_enabled'
        ])->delete();

        // Update existing UltraMsg settings
        DB::table('settings')
            ->where('key', 'ultramsg_token')
            ->update([
                'display_name' => 'UltraMsg Token',
                'description' => 'Your UltraMsg API token',
                'updated_at' => now(),
            ]);

        DB::table('settings')
            ->where('key', 'ultramsg_instance_id')
            ->update([
                'display_name' => 'UltraMsg Instance ID',
                'description' => 'Your UltraMsg instance ID',
                'updated_at' => now(),
            ]);

        // Update WhatsApp enabled description
        DB::table('settings')
            ->where('key', 'whatsapp_enabled')
            ->update([
                'display_name' => 'Enable WhatsApp',
                'description' => 'Enable WhatsApp notifications using UltraMsg API',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore WA Client settings (if needed for rollback)
        $waclientSettings = [
            [
                'key' => 'whatsapp_api_token',
                'value' => '',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'WA Client Token',
                'description' => 'The WA Client Access Token',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whatsapp_instance_id',
                'value' => '',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'WA Client Instance ID',
                'description' => 'The WA Client Instance ID',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ultramsg_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'whatsapp',
                'display_name' => 'Enable UltraMsg',
                'description' => 'Enable UltraMsg WhatsApp API',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($waclientSettings as $setting) {
            $existing = DB::table('settings')->where('key', $setting['key'])->first();
            if (!$existing) {
                DB::table('settings')->insert($setting);
            }
        }

        // Restore original descriptions
        DB::table('settings')
            ->where('key', 'whatsapp_enabled')
            ->update([
                'display_name' => 'Enable WhatsApp',
                'description' => 'Enable WhatsApp notifications',
                'updated_at' => now(),
            ]);
    }
};
