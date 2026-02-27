<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->boolean('is_ai_generated')->default(false)->after('can_comment');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropColumn('is_ai_generated');
        });
    }
};
