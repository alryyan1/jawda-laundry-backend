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
        Schema::dropIfExists('pricing_rules');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offering_id')->constrained();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->decimal('price_per_sq_meter', 10, 2);
            $table->timestamps();
        });
    }
};
