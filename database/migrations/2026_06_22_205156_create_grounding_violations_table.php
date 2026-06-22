<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grounding_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('model_used')->nullable();
            $table->text('original_text');
            $table->text('safe_fallback_text')->nullable();
            $table->string('action_taken'); // blocked, regenerated, fallback_sent
            $table->text('violation_reason')->nullable();
            $table->json('retrieved_facts')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grounding_violations');
    }
};
