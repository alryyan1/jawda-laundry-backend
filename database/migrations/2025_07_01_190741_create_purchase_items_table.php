<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->onDelete('cascade');
            $table->string('item_name'); // e.g., "Tide Professional Detergent"
            $table->text('description')->nullable();
            $table->integer('quantity');
            $table->string('unit')->nullable()->comment('e.g., bottle, box, kg');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('sub_total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};