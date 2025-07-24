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
        Schema::create('product_type_inventory_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity_required', 10, 2);
            $table->string('unit', 50);
            $table->timestamps();
            
            // Prevent duplicate requirements
            $table->unique(['product_type_id', 'inventory_item_id'], 'pt_ir_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_type_inventory_requirements');
    }
};
