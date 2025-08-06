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
        // Insert the new setting
        DB::table('settings')->insert([
            'key' => 'company_phone_2',
            'value' => '',
            'type' => 'string',
            'group' => 'company',
            'display_name' => 'Company Phone 2',
            'description' => 'The second phone number of your company',
            'is_public' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the setting
        DB::table('settings')->where('key', 'company_phone_2')->delete();
    }
};
