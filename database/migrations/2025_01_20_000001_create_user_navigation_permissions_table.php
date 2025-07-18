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
        Schema::create('user_navigation_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('navigation_item_id')->constrained()->onDelete('cascade');
            $table->boolean('is_granted')->default(true); // Whether this navigation item is granted or denied
            $table->timestamps();

            $table->unique(['user_id', 'navigation_item_id']);
            $table->index('user_id');
            $table->index('navigation_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_navigation_permissions');
    }
}; 