<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Detergent Purchase", "Rent for May", "Electricity Bill"
            $table->string('category')->nullable(); // e.g., "Supplies", "Utilities", "Rent", "Salaries"
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->timestamp('expense_date');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // User who recorded it
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};