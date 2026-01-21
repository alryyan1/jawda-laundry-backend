<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Drop foreign key constraints on `orders` table first
        Schema::table('orders', function (Blueprint $table) {
            // Check if foreign keys exist before dropping. 
            // It's hard to know exact index names, but we can try dropping by column name or standard index name.
            // Using array syntax drops the index if it follows convention.

            // Try dropping FKs if they exist. Use try-catch or checks if possible, but standard migration schema methods usually just attempt it.
            // We will attempt to drop the foreign key by the column name array.

            if (Schema::hasColumn('orders', 'table_id')) {
                // The constraint name is likely `orders_table_id_foreign`
                try {
                    $table->dropForeign(['table_id']);
                } catch (\Exception $e) {
                    // FK might not exist or have different name
                }
                $table->dropColumn('table_id');
            }

            if (Schema::hasColumn('orders', 'dining_table_id')) {
                try {
                    $table->dropForeign(['dining_table_id']);
                } catch (\Exception $e) {
                    // FK might not exist
                }
                $table->dropColumn('dining_table_id');
            }
        });

        // 2. Drop dependent tables (reservations)
        Schema::dropIfExists('table_reservations');

        // 3. Drop primary tables
        Schema::dropIfExists('dining_tables');
        Schema::dropIfExists('restaurant_tables');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is destructive and intended to remove features.
        // Re-creating them would require initial creation logic which is complex to replicate here perfectly without original context.
        // Leaving empty or just basic placeholder.

        /*
        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('dining_table_id')->nullable()->constrained('dining_tables')->nullOnDelete();
        });
        */
    }
};
