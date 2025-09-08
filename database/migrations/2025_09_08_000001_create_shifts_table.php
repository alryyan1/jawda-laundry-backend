<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opened_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_cash', 10, 2)->default(0);
            $table->decimal('closing_cash', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->after('user_id');
                $table->index('shift_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'shift_id')) {
                $table->dropIndex(['shift_id']);
                $table->dropColumn('shift_id');
            }
        });

        Schema::dropIfExists('shifts');
    }
};


