<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 // New migration file
public function up(): void {
    Schema::table('expenses', function (Blueprint $table) {
        $table->dropColumn('category'); // Drop the old string column
        $table->foreignId('expense_category_id')->nullable()->after('name')->constrained('expense_categories')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropColumn('expense_category_id');
            $table->string('category')->nullable()->change();
        });
    }
};
