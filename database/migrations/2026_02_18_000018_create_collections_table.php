<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_collections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('name', 100);
            $table->unsignedBigInteger('cover_post_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->integer('post_count')->default(0);
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('post_saves', function (Blueprint $table) {
            $table->unsignedBigInteger('collection_id')->nullable()->after('user_id');
            $table->foreign('collection_id')->references('id')->on('tbl_collections')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('post_saves', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
            $table->dropColumn('collection_id');
        });
        Schema::dropIfExists('tbl_collections');
    }
};
