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
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('default_locale', ['ar', 'en'])->default('ar')->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('locale', ['ar', 'en'])->nullable()->after('email');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->enum('locale', ['ar', 'en'])->nullable()->after('customer_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('default_locale');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
