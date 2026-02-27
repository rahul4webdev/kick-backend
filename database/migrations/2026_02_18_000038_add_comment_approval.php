<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add is_approved to comments (default true for backward compat)
        Schema::table('tbl_comments', function (Blueprint $table) {
            $table->boolean('is_approved')->default(true)->after('is_pinned');
        });

        // Add comment_approval_enabled to users
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->boolean('comment_approval_enabled')->default(false)->after('hide_others_like_count');
        });
    }

    public function down()
    {
        Schema::table('tbl_comments', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn('comment_approval_enabled');
        });
    }
};
