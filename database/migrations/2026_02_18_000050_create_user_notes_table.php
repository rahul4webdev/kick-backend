<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('content', 60);
            $table->string('emoji', 10)->nullable();
            $table->timestampTz('expires_at');
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notes');
    }
};
