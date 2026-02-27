<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->smallInteger('type')->default(0);
            $table->string('content', 999);
            $table->string('thumbnail', 999)->nullable();
            $table->unsignedBigInteger('sound_id')->nullable();
            $table->string('duration', 10)->nullable();
            $table->text('view_by_user_ids')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('sound_id')->references('id')->on('tbl_sound')->onDelete('set null');
            $table->index(['user_id', 'created_at']);
        });

        DB::statement('ALTER TABLE stories ADD CONSTRAINT chk_story_type CHECK (type IN (0, 1))');
    }

    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
