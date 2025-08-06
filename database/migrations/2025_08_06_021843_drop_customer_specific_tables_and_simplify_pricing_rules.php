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
        // Drop customer-specific tables
        Schema::dropIfExists('customer_product_service_offerings');
        Schema::dropIfExists('customer_product_types');

        // Simplify pricing_rules table by removing unnecessary columns
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropForeign(['customer_type_id']);
            $table->dropColumn([
                'customer_type_id',
                'valid_from',
                'valid_to',
                'min_quantity',
                'min_area_sq_meter'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate customer-specific tables (if needed for rollback)
        Schema::create('customer_product_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_type_id')->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['customer_id', 'product_type_id']);
        });

        Schema::create('customer_product_service_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_action_id')->constrained()->onDelete('cascade');
            $table->string('name_override')->nullable();
            $table->text('description_override')->nullable();
            $table->decimal('default_price', 10, 2)->nullable();
            $table->decimal('default_price_per_sq_meter', 10, 2)->nullable();
            $table->string('applicable_unit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('custom_price', 10, 2)->nullable();
            $table->decimal('custom_price_per_sq_meter', 10, 2)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('min_quantity')->nullable();
            $table->decimal('min_area_sq_meter', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'product_type_id', 'service_action_id'], 'customer_product_service_unique');
        });

        // Restore pricing_rules columns
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->foreignId('customer_type_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('min_quantity')->nullable();
            $table->decimal('min_area_sq_meter', 10, 2)->nullable();
        });
    }
};
