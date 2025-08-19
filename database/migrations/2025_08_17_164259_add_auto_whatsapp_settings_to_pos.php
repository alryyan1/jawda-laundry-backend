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
        // Add new POS settings for auto WhatsApp notifications
        DB::table('settings')->insert([
            [
                'key' => 'pos_auto_send_whatsapp_invoice',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'pos',
                'display_name' => 'Auto Send WhatsApp Invoice',
                'description' => 'Automatically send PDF invoice via WhatsApp when order is completed',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'pos_auto_send_whatsapp_text',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'pos',
                'display_name' => 'Auto Send WhatsApp Text',
                'description' => 'Automatically send WhatsApp text message when order is completed',
                'is_public' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the added settings
        DB::table('settings')->whereIn('key', [
            'pos_auto_send_whatsapp_invoice',
            'pos_auto_send_whatsapp_text'
        ])->delete();
    }
};
