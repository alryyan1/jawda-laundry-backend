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
        // Add UltraMsg settings to the settings table
        $ultramsgSettings = [
            [
                'key' => 'ultramsg_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'whatsapp',
                'display_name' => 'Enable UltraMsg',
                'description' => 'Enable UltraMsg WhatsApp API',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ultramsg_token',
                'value' => 'b6ght2y2ff7rbha6',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'UltraMsg Token',
                'description' => 'The UltraMsg API token',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'ultramsg_instance_id',
                'value' => 'instance139458',
                'type' => 'string',
                'group' => 'whatsapp',
                'display_name' => 'UltraMsg Instance ID',
                'description' => 'The UltraMsg instance ID',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($ultramsgSettings as $setting) {
            // Check if setting already exists
            $existing = DB::table('settings')->where('key', $setting['key'])->first();
            
            if (!$existing) {
                DB::table('settings')->insert($setting);
            }
        }

        // Update existing WhatsApp settings to clarify they are for WA Client
        DB::table('settings')
            ->where('key', 'whatsapp_api_token')
            ->update([
                'display_name' => 'Legacy WA Client Token',
                'description' => 'The legacy WA Client Access Token (for backward compatibility)',
                'updated_at' => now(),
            ]);

        DB::table('settings')
            ->where('key', 'whatsapp_instance_id')
            ->update([
                'display_name' => 'Legacy WA Client Instance ID',
                'description' => 'The legacy WA Client Instance ID (for backward compatibility)',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove UltraMsg settings
        DB::table('settings')->whereIn('key', [
            'ultramsg_enabled',
            'ultramsg_token',
            'ultramsg_instance_id'
        ])->delete();

        // Restore original display names
        DB::table('settings')
            ->where('key', 'whatsapp_api_token')
            ->update([
                'display_name' => 'WhatsApp Access Token',
                'description' => 'The WhatsApp Access Token from WA Client',
                'updated_at' => now(),
            ]);

        DB::table('settings')
            ->where('key', 'whatsapp_instance_id')
            ->update([
                'display_name' => 'WhatsApp Instance ID',
                'description' => 'The WhatsApp Instance ID from WA Client',
                'updated_at' => now(),
            ]);
    }
};
