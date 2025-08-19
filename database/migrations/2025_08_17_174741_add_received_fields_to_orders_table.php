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
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('received')->default(false)->after('whatsapp_pdf_sent');
            $table->timestamp('received_at')->nullable()->after('received');
            $table->boolean('order_receive_message_sent')->default(false)->after('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['received', 'received_at', 'order_receive_message_sent']);
        });
    }
};
