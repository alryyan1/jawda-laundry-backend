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
        Schema::create('user_main_navs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('key')->unique(); // Unique navigation key
            $table->json('title'); // Multilingual title: {'en': 'Dashboard', 'ar': 'لوحة التحكم'}
            $table->string('icon')->nullable(); // Icon name (e.g., 'LayoutDashboard')
            $table->string('route')->nullable(); // Route path (e.g., '/dashboard')
            $table->integer('sort_order')->default(0); // For ordering
            $table->boolean('is_active')->default(true); // Whether this nav item is active
            $table->json('permissions')->nullable(); // Required permissions to show this nav
            $table->timestamps();
            
            // Ensure each user can only have one nav item with the same key
            $table->unique(['user_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_main_navs');
    }
};
