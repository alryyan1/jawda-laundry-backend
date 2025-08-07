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
        Schema::create('customer_product_service_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_offering_id')->constrained()->onDelete('cascade');
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->decimal('custom_price_per_sq_meter', 10, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('min_quantity')->nullable();
            $table->decimal('min_area_sq_meter', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure unique combination of customer, product type, and service offering
            $table->unique(['customer_id', 'product_type_id', 'service_offering_id'], 'cpso_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_product_service_offerings');
    }
}; 