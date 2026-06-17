<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('type');
            $table->integer('rooms');
            $table->integer('area');
            $table->unsignedBigInteger('price');
            $table->string('location');
            $table->unsignedBigInteger('down_payment')->nullable();
            $table->integer('installment_years')->nullable();
            $table->string('status')->default('available');
            $table->date('delivery_date')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
