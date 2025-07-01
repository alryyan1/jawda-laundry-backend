<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('customer_type_id')
                  ->nullable()
                  ->constrained('customer_types')
                  ->onDelete('set null'); // Or restrict/cascade

            $table->foreignId('user_id') // Staff member who created/manages
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->timestamps();
            $table->softDeletes(); // Recommended for customers
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};