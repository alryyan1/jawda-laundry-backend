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
        // Add the new whatsapp_instance_id setting
        DB::table('settings')->insert([
            'key' => 'whatsapp_instance_id',
            'value' => '68968AFE5FF3D',
            'type' => 'string',
            'group' => 'whatsapp',
            'display_name' => 'WhatsApp Instance ID',
            'description' => 'The WhatsApp Instance ID from WA Client',
            'is_public' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update the existing whatsapp_api_token value
        DB::table('settings')
            ->where('key', 'whatsapp_api_token')
            ->update([
                'value' => '68968ae964aac',
                'display_name' => 'WhatsApp Access Token',
                'description' => 'The WhatsApp Access Token from WA Client',
                'updated_at' => now(),
            ]);

        // Remove the old whatsapp_api_url setting
        DB::table('settings')
            ->where('key', 'whatsapp_api_url')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the new whatsapp_instance_id setting
        DB::table('settings')
            ->where('key', 'whatsapp_instance_id')
            ->delete();

        // Restore the old whatsapp_api_url setting
        DB::table('settings')->insert([
            'key' => 'whatsapp_api_url',
            'value' => 'https://waapi.app/api/v1/instances/45517',
            'type' => 'string',
            'group' => 'whatsapp',
            'display_name' => 'WhatsApp API URL',
            'description' => 'The WhatsApp API URL',
            'is_public' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Restore the old whatsapp_api_token value
        DB::table('settings')
            ->where('key', 'whatsapp_api_token')
            ->update([
                'value' => 'AZ9XyS2kVjPBpPUWldVn2PlH4SKQUo5kxKo3tW7i35a32c37',
                'display_name' => 'WhatsApp API Token',
                'description' => 'The WhatsApp API token',
                'updated_at' => now(),
            ]);
    }
};
