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
        Schema::table('settings', function (Blueprint $table) {
            // Add missing columns
            $table->string('type')->default('string')->after('value');
            $table->string('display_name')->after('group');
            $table->text('description')->nullable()->after('display_name');
            $table->boolean('is_public')->default(false)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['type', 'display_name', 'description', 'is_public']);
        });
    }
};
