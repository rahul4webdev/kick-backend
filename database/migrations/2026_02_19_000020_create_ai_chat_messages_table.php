<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('session_id', 100)->nullable();
            $table->text('user_message');
            $table->text('ai_response')->nullable();
            $table->string('status', 20)->default('completed'); // pending, completed, failed
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('session_id');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ai_chat_messages');
    }
};
