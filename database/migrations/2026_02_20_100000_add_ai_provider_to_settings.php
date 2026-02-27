<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->string('ai_provider', 20)->default('gemini')->after('ai_api_key');
        });

        // Update default model to Gemini
        DB::table('tbl_settings')->update(['ai_model' => 'gemini-2.0-flash']);
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn('ai_provider');
        });

        DB::table('tbl_settings')->update(['ai_model' => 'claude-sonnet-4-5-20250929']);
    }
};
