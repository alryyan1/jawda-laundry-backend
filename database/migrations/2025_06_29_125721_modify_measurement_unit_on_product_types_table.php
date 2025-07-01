<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            // Drop the old column if it exists
            if (Schema::hasColumn('product_types', 'base_measurement_unit')) {
                $table->dropColumn('base_measurement_unit');
            }

            // Add the new boolean column
            if (!Schema::hasColumn('product_types', 'is_dimension_based')) {
                $table->boolean('is_dimension_based')->default(false)->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_types', function (Blueprint $table) {
            if (Schema::hasColumn('product_types', 'is_dimension_based')) {
                $table->dropColumn('is_dimension_based');
            }
            if (!Schema::hasColumn('product_types', 'base_measurement_unit')) {
                $table->string('base_measurement_unit')->nullable()->comment('item, kg, sq_meter');
            }
        });
    }
};