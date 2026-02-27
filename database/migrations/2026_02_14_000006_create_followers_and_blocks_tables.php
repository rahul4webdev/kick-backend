<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_followers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->timestampsTz();

            $table->foreign('from_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['from_user_id', 'to_user_id']);
            $table->index('to_user_id');
        });

        Schema::create('tbl_user_block', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->timestampsTz();

            $table->foreign('from_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['from_user_id', 'to_user_id']);
            $table->index('to_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_block');
        Schema::dropIfExists('tbl_followers');
    }
};
