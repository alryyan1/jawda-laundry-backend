<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('predefined_sizes', function (Blueprint $table) {
            $table->id();
            // Link this size to a specific product type (e.g., 'Area Rug')
            $table->foreignId('product_type_id')->constrained('product_types')->onDelete('cascade');
            $table->string('name')->comment("e.g., 'Small Rug', 'Living Room Carpet'");
            $table->decimal('length_meters', 8, 2);
            $table->decimal('width_meters', 8, 2);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('predefined_sizes'); }
};