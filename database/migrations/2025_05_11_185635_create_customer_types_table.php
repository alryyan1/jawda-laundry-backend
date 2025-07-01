<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            // Add other fields like discount_percentage if needed
            // $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();
            // $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_types');
    }
};