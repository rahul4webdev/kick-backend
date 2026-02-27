<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_creator_ai_insights', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('insight_type', 50);
            $table->string('title', 200);
            $table->text('body');
            $table->jsonb('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestampTz('generated_at')->useCurrent();
            $table->timestampTz('expires_at')->nullable();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'generated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_creator_ai_insights');
    }
};
