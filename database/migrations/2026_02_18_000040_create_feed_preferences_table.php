<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_feed_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('interest_id');
            $table->smallInteger('weight')->default(0); // -1=less, 0=normal, 1=more
            $table->timestamps();

            $table->unique(['user_id', 'interest_id']);
            $table->foreign('user_id')->references('id')->on('tbl_users')->cascadeOnDelete();
            $table->foreign('interest_id')->references('id')->on('interests')->cascadeOnDelete();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_feed_preferences');
    }
};
