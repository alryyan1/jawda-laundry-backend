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
        Schema::table('expenses', function (Blueprint $table) {
            // Check if the column doesn't already exist to make the migration re-runnable
            if (!Schema::hasColumn('expenses', 'payment_method')) {
                // Add the new column, for example, after the 'amount' column
                $table->string('payment_method')->nullable()->after('amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // This allows you to rollback the migration
            if (Schema::hasColumn('expenses', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};