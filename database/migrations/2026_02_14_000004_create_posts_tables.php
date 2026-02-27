<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_post', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('post_type')->default(1);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sound_id')->nullable();
            $table->string('description', 999)->nullable();
            $table->text('metadata')->nullable();
            $table->string('hashtags', 255)->nullable();
            $table->string('video', 555)->nullable();
            $table->string('thumbnail', 555)->nullable();
            $table->bigInteger('views')->default(0);
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('comments')->default(0);
            $table->bigInteger('saves')->default(0);
            $table->bigInteger('shares')->default(0);
            $table->text('mentioned_user_ids')->nullable();
            $table->boolean('is_trending')->default(false);
            $table->boolean('can_comment')->default(true);

            // Location
            $table->string('place_title', 200)->nullable();
            $table->float('place_lat')->nullable();
            $table->float('place_lon')->nullable();
            $table->string('state', 50)->nullable();
            $table->string('country', 100)->nullable();

            $table->boolean('is_pinned')->default(false);
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('sound_id')->references('id')->on('tbl_sound')->onDelete('set null');

            $table->index(['user_id', 'post_type']);
            $table->index(['user_id', 'is_pinned']);
            $table->index('sound_id');
        });

        DB::statement('ALTER TABLE tbl_post ADD CONSTRAINT chk_post_type CHECK (post_type IN (1, 2, 3, 4))');
        DB::statement("CREATE INDEX idx_post_description_fts ON tbl_post USING GIN (to_tsvector('english', COALESCE(description, '')))");
        DB::statement("CREATE INDEX idx_post_hashtags_gin ON tbl_post USING GIN (string_to_array(COALESCE(hashtags, ''), ','))");

        Schema::create('post_images', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->string('image');
            $table->timestampsTz();

            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_images');
        Schema::dropIfExists('tbl_post');
    }
};
