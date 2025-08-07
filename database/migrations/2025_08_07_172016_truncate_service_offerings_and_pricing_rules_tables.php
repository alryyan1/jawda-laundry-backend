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
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Truncate pricing_rules table first (due to foreign key constraints)
        DB::table('pricing_rules')->truncate();
        
        // Truncate service_offerings table
        DB::table('service_offerings')->truncate();
        
        // Reset auto-increment counters
        DB::statement('ALTER TABLE pricing_rules AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE service_offerings AUTO_INCREMENT = 1');
        
        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is destructive and cannot be reversed
        // The data will need to be re-seeded if needed
    }
};
