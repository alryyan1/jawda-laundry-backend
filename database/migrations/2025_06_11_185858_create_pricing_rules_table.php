<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offering_id')->constrained('service_offerings')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->foreignId('customer_type_id')->nullable()->constrained('customer_types')->onDelete('cascade');

            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('price_per_sq_meter', 10, 2)->nullable();

            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('min_quantity')->unsigned()->nullable();
            $table->decimal('min_area_sq_meter', 8, 2)->unsigned()->nullable();
            $table->timestamps();

            // Indexes for faster lookups
            $table->index(['service_offering_id', 'customer_id', 'valid_from', 'valid_to'], 'pricing_customer_date_idx');
            $table->index(['service_offering_id', 'customer_type_id', 'valid_from', 'valid_to'], 'pricing_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};