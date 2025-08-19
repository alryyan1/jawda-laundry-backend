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
        // Add new setting for auto-sending receive order messages
        DB::table('settings')->insert([
            [
                'key' => 'pos_auto_send_receive_order_message',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'pos',
                'display_name' => 'Auto Send Receive Order Message',
                'description' => 'Automatically send WhatsApp message when order is received',
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
        // Remove the added setting
        DB::table('settings')->where('key', 'pos_auto_send_receive_order_message')->delete();
    }
};
