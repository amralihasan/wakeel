<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('customer_phone');
            $table->string('name')->nullable();
            $table->unsignedBigInteger('budget_max')->nullable();
            $table->integer('preferred_rooms')->nullable();
            $table->string('preferred_location')->nullable();
            $table->foreignId('interested_unit_id')->nullable()->constrained('units')->onDelete('set null');
            $table->integer('score')->default(0);
            $table->string('tier')->nullable();
            $table->string('status')->default('new');
            $table->string('source')->default('other');
            $table->timestamps();

            $table->unique(['company_id', 'customer_phone']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
