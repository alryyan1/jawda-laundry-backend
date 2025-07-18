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
        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Unique identifier (e.g., 'dashboard', 'orders', 'customers')
            $table->json('title'); // Multi-language title {"en": "Dashboard", "ar": "لوحة التحكم"}
            $table->string('icon')->nullable(); // Icon name for the menu item
            $table->string('route')->nullable(); // Frontend route path
            $table->unsignedBigInteger('parent_id')->nullable(); // For nested menu items
            $table->integer('sort_order')->default(0); // Display order
            $table->boolean('is_active')->default(true); // Admin can enable/disable items
            $table->boolean('is_default')->default(false); // System default items that can't be deleted
            $table->json('permissions')->nullable(); // Required permissions to access this item
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('navigation_items')->onDelete('cascade');
            $table->index(['parent_id', 'sort_order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('navigation_items');
    }
}; 