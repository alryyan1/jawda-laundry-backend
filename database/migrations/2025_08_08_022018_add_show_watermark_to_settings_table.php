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
        // Add the show_watermark setting
        DB::table('settings')->insert([
            'key' => 'show_watermark',
            'value' => 'false',
            'type' => 'boolean',
            'group' => 'pdf',
            'display_name' => 'Show Watermark on PDFs',
            'description' => 'Enable watermark on all generated PDF documents',
            'is_public' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the show_watermark setting
        DB::table('settings')->where('key', 'show_watermark')->delete();
    }
};
