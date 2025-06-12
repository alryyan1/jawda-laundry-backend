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
        Schema::create('service_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained('product_types')->onDelete('cascade');
            $table->foreignId('service_action_id')->constrained('service_actions')->onDelete('cascade');
            $table->string('name_override')->nullable()->comment('Custom name, else auto-generated');
            $table->text('description_override')->nullable();
            $table->decimal('default_price', 10, 2)->nullable();
            $table->string('pricing_strategy')->default('fixed')->comment('fixed, per_unit_product, dimension_based, customer_specific');
            $table->decimal('default_price_per_sq_meter', 10, 2)->nullable();
            $table->string('applicable_unit')->nullable()->comment('e.g. item, kg, sq_meter. Can be inherited from ProductType or specific here.');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['product_type_id', 'service_action_id'], 'product_action_unique'); // A product can only have one type of action defined once as a base offering
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_offerings');
    }
};
