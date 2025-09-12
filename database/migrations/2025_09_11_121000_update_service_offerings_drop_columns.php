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
        Schema::table('service_offerings', function (Blueprint $table) {
            if (Schema::hasColumn('service_offerings', 'name_override')) {
                $table->dropColumn('name_override');
            }
            if (Schema::hasColumn('service_offerings', 'default_price_per_sq_meter')) {
                $table->dropColumn('default_price_per_sq_meter');
            }
            if (Schema::hasColumn('service_offerings', 'applicable_unit')) {
                $table->dropColumn('applicable_unit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_offerings', function (Blueprint $table) {
            if (!Schema::hasColumn('service_offerings', 'name_override')) {
                $table->string('name_override')->nullable()->comment('Custom display name if needed');
            }
            if (!Schema::hasColumn('service_offerings', 'default_price_per_sq_meter')) {
                $table->decimal('default_price_per_sq_meter', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('service_offerings', 'applicable_unit')) {
                $table->string('applicable_unit')->nullable()->comment('item, kg, sq_meter - clarifies default_price unit');
            }
        });
    }
};


