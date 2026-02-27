<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_calls', function (Blueprint $table) {
            $table->id();
            $table->string('room_id', 100)->unique();
            $table->unsignedBigInteger('caller_id');
            $table->smallInteger('call_type')->default(1); // 1=voice, 2=video
            $table->smallInteger('status')->default(0); // 0=ringing, 1=answered, 2=ended, 3=missed, 4=rejected
            $table->boolean('is_group')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_sec')->default(0);
            $table->timestamps();

            $table->foreign('caller_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['caller_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('tbl_call_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('call_id');
            $table->unsignedBigInteger('user_id');
            $table->smallInteger('status')->default(0); // 0=ringing, 1=joined, 2=left, 3=missed, 4=rejected
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->foreign('call_id')->references('id')->on('tbl_calls')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['call_id', 'user_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_call_participants');
        Schema::dropIfExists('tbl_calls');
    }
};
