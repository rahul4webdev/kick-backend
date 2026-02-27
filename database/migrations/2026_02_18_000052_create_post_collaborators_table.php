<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_collaborators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('user_id'); // the invited collaborator
            $table->unsignedBigInteger('invited_by'); // the post owner who invited
            $table->smallInteger('status')->default(0); // 0=pending, 1=accepted, 2=declined
            $table->timestampsTz();

            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['post_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_collaborators');
    }
};
