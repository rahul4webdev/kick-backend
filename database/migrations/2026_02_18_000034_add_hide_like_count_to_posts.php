<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->boolean('hide_like_count')->default(false)->after('is_pinned');
        });

        Schema::table('tbl_users', function (Blueprint $table) {
            $table->boolean('hide_others_like_count')->default(false)->after('is_moderator');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropColumn('hide_like_count');
        });

        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn('hide_others_like_count');
        });
    }
};
