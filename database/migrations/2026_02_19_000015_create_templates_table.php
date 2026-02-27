<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->string('preview_video', 500)->nullable();
            $table->integer('clip_count')->default(1);
            $table->integer('duration_sec')->default(15);
            $table->string('category', 100)->nullable();
            $table->bigInteger('music_id')->nullable();
            $table->jsonb('transition_data')->nullable(); // [{type: 'fade', duration_ms: 500}, ...]
            $table->boolean('is_active')->default(true);
            $table->integer('use_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('category');
        });

        Schema::create('tbl_template_clips', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('template_id')->unsigned();
            $table->integer('clip_index')->default(0); // 0-based position
            $table->integer('duration_ms')->default(3000); // Duration in milliseconds
            $table->string('label', 100)->nullable(); // e.g. "Intro shot", "Close-up"
            $table->string('transition_to_next', 50)->default('cut'); // cut, fade, zoom, slide, dissolve
            $table->integer('transition_duration_ms')->default(300);
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('tbl_templates')->onDelete('cascade');
            $table->index(['template_id', 'clip_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_template_clips');
        Schema::dropIfExists('tbl_templates');
    }
};
