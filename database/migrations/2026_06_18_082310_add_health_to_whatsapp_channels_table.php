<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_channels', function (Blueprint $table) {
            $table->string('label')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->boolean('webhook_ok')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_channels', function (Blueprint $table) {
            $table->dropColumn(['label', 'last_inbound_at', 'webhook_ok']);
        });
    }
};
