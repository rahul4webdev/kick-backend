<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_feed_keyword_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('keyword', 100);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['user_id', 'keyword']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_feed_keyword_filters');
    }
};
