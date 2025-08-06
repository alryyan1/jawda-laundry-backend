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
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('sequence_prefix', 10)->nullable()->after('image_url');
            $table->boolean('sequence_enabled')->default(false)->after('sequence_prefix');
            $table->integer('current_sequence')->default(0)->after('sequence_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn(['sequence_prefix', 'sequence_enabled', 'current_sequence']);
        });
    }
};
