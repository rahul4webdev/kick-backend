<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tbl_comments', function (Blueprint $table) {
            $table->boolean('is_creator_liked')->default(false);
            $table->integer('score')->default(0);
            $table->index(['post_id', 'score'], 'idx_comments_score');
        });
    }

    public function down()
    {
        Schema::table('tbl_comments', function (Blueprint $table) {
            $table->dropIndex('idx_comments_score');
            $table->dropColumn(['is_creator_liked', 'score']);
        });
    }
};
