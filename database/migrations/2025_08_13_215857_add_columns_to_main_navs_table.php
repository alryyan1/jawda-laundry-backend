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
        Schema::table('main_navs', function (Blueprint $table) {
            $table->string('path')->nullable()->after('title');
            $table->string('icon')->nullable()->after('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_navs', function (Blueprint $table) {
            $table->dropColumn(['path', 'icon']);
        });
    }
};
