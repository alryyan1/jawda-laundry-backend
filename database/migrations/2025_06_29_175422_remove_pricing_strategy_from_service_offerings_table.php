<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_offerings', function (Blueprint $table) {
            if (Schema::hasColumn('service_offerings', 'pricing_strategy')) {
                $table->dropColumn('pricing_strategy');
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_offerings', function (Blueprint $table) {
            if (!Schema::hasColumn('service_offerings', 'pricing_strategy')) {
                $table->string('pricing_strategy')->default('fixed')->after('default_price');
            }
        });
    }
};