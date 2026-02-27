<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_scheduled_lives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->timestamp('scheduled_at');
            $table->smallInteger('status')->default(1); // 1=upcoming, 2=live, 3=completed, 4=cancelled
            $table->integer('reminder_count')->default(0);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['status', 'scheduled_at']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('tbl_scheduled_live_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('scheduled_live_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('scheduled_live_id')->references('id')->on('tbl_scheduled_lives')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['scheduled_live_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_scheduled_live_reminders');
        Schema::dropIfExists('tbl_scheduled_lives');
    }
};
