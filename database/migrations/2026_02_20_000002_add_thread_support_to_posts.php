<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->bigInteger('thread_id')->nullable()->after('id');
            $table->integer('thread_position')->nullable()->after('thread_id');
            $table->boolean('is_quote_repost')->default(false)->after('thread_position');
            $table->bigInteger('quoted_post_id')->nullable()->after('is_quote_repost');

            $table->index(['thread_id', 'thread_position'], 'idx_post_thread');
            $table->index('quoted_post_id', 'idx_post_quoted');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropIndex('idx_post_thread');
            $table->dropIndex('idx_post_quoted');
            $table->dropColumn(['thread_id', 'thread_position', 'is_quote_repost', 'quoted_post_id']);
        });
    }
};
