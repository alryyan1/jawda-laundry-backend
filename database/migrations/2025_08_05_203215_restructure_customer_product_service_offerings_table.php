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
        // Drop the existing table if it exists
        Schema::dropIfExists('customer_product_service_offerings');

        // Create the new table with structure identical to service_offerings plus customer_id and service_action_id
        Schema::create('customer_product_service_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_action_id')->constrained()->onDelete('cascade');
            
            // Fields from service_offerings table
            $table->string('name_override')->nullable();
            $table->text('description_override')->nullable();
            $table->decimal('default_price', 10, 2)->nullable();
            $table->decimal('default_price_per_sq_meter', 10, 2)->nullable();
            $table->string('applicable_unit')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Custom pricing fields (these will override the default prices)
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->decimal('custom_price_per_sq_meter', 10, 2)->nullable();
            
            // Additional customer-specific fields
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('min_quantity')->nullable();
            $table->decimal('min_area_sq_meter', 10, 2)->nullable();
            
            $table->timestamps();
            
            // Ensure unique combination of customer, product type, and service action
            $table->unique(['customer_id', 'product_type_id', 'service_action_id'], 'customer_product_service_unique');
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
