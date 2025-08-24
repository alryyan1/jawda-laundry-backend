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
        // First, update any NULL values to a default value
        DB::table('customers')->whereNull('car_plate_number')->update(['car_plate_number' => 'N/A']);
        
        // Then make the column not nullable
        Schema::table('customers', function (Blueprint $table) {
            $table->string('car_plate_number')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('car_plate_number')->nullable()->change();
        });
    }
};
