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
        DB::table('navigation_items')
            ->where('key', 'dining')
            ->update([
                'title' => json_encode([
                    'en' => 'Section Management',
                    'ar' => 'إدارة الأقسام'
                ])
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('navigation_items')
            ->where('key', 'dining')
            ->update([
                'title' => json_encode([
                    'en' => 'Dining Management',
                    'ar' => 'إدارة المطعم'
                ])
            ]);
    }
};
