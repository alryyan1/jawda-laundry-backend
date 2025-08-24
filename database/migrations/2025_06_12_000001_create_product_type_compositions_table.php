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
            Schema::create('product_type_compositions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_type_id')->constrained('product_types')->onDelete('cascade');
        $table->foreignId('product_composition_id')->constrained('product_compositions')->onDelete('cascade');
        $table->text('description')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->unique(['product_type_id', 'product_composition_id'], 'ptc_unique');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_type_compositions');
    }
};
