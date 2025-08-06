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
        // Drop tables in reverse order to handle foreign key constraints
        Schema::dropIfExists('product_type_inventory_requirements');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is for dropping inventory tables
        // No rollback needed as inventory functionality is being removed
    }
};
