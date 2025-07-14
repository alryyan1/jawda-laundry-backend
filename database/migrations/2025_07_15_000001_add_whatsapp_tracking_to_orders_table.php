<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('whatsapp_text_sent')->default(false)->comment('Track if WhatsApp text message was sent');
            $table->boolean('whatsapp_pdf_sent')->default(false)->comment('Track if WhatsApp PDF invoice was sent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_text_sent', 'whatsapp_pdf_sent']);
        });
    }
}; 