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
        Schema::create('whatsapp_channels', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->string('channel_id')->unique();
            $table->string('status')->default('available'); // available, assigned, retired
            $table->foreignId('assigned_company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_channels');
    }
};
