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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained('product_types')->onDelete('cascade');
            $table->string('sku')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('unit', 50); // pieces, kg, liters, etc.
            $table->decimal('min_stock_level', 10, 2)->default(0);
            $table->decimal('max_stock_level', 10, 2)->nullable();
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
