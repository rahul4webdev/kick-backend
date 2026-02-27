<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->string('groq_api_key', 200)->nullable();
            $table->string('groq_model', 100)->default('llama-3.3-70b-versatile');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn(['groq_api_key', 'groq_model']);
        });
    }
};
