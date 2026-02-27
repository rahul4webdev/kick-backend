<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_content_genres', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->smallInteger('content_type'); // 1=music, 2=trailer, 3=news, 4=short_story
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        DB::statement('CREATE INDEX idx_content_genres_type ON tbl_content_genres (content_type, is_active)');

        // Seed default genres
        $genres = [
            // Music Video genres (content_type=1)
            ['name' => 'Pop', 'content_type' => 1, 'sort_order' => 1],
            ['name' => 'Hip-Hop', 'content_type' => 1, 'sort_order' => 2],
            ['name' => 'Rock', 'content_type' => 1, 'sort_order' => 3],
            ['name' => 'R&B', 'content_type' => 1, 'sort_order' => 4],
            ['name' => 'Electronic', 'content_type' => 1, 'sort_order' => 5],
            ['name' => 'Classical', 'content_type' => 1, 'sort_order' => 6],
            ['name' => 'Folk', 'content_type' => 1, 'sort_order' => 7],
            ['name' => 'Devotional', 'content_type' => 1, 'sort_order' => 8],
            ['name' => 'Indie', 'content_type' => 1, 'sort_order' => 9],
            ['name' => 'Remix', 'content_type' => 1, 'sort_order' => 10],

            // Trailer genres (content_type=2)
            ['name' => 'Action', 'content_type' => 2, 'sort_order' => 1],
            ['name' => 'Comedy', 'content_type' => 2, 'sort_order' => 2],
            ['name' => 'Drama', 'content_type' => 2, 'sort_order' => 3],
            ['name' => 'Thriller', 'content_type' => 2, 'sort_order' => 4],
            ['name' => 'Horror', 'content_type' => 2, 'sort_order' => 5],
            ['name' => 'Romance', 'content_type' => 2, 'sort_order' => 6],
            ['name' => 'Sci-Fi', 'content_type' => 2, 'sort_order' => 7],
            ['name' => 'Documentary', 'content_type' => 2, 'sort_order' => 8],
            ['name' => 'Animation', 'content_type' => 2, 'sort_order' => 9],

            // News categories (content_type=3)
            ['name' => 'Politics', 'content_type' => 3, 'sort_order' => 1],
            ['name' => 'Business', 'content_type' => 3, 'sort_order' => 2],
            ['name' => 'Technology', 'content_type' => 3, 'sort_order' => 3],
            ['name' => 'Sports', 'content_type' => 3, 'sort_order' => 4],
            ['name' => 'Entertainment', 'content_type' => 3, 'sort_order' => 5],
            ['name' => 'Health', 'content_type' => 3, 'sort_order' => 6],
            ['name' => 'Science', 'content_type' => 3, 'sort_order' => 7],
            ['name' => 'World', 'content_type' => 3, 'sort_order' => 8],
            ['name' => 'Breaking', 'content_type' => 3, 'sort_order' => 9],

            // Short Story genres (content_type=4)
            ['name' => 'Drama', 'content_type' => 4, 'sort_order' => 1],
            ['name' => 'Romance', 'content_type' => 4, 'sort_order' => 2],
            ['name' => 'Thriller', 'content_type' => 4, 'sort_order' => 3],
            ['name' => 'Comedy', 'content_type' => 4, 'sort_order' => 4],
            ['name' => 'Horror', 'content_type' => 4, 'sort_order' => 5],
            ['name' => 'Mystery', 'content_type' => 4, 'sort_order' => 6],
            ['name' => 'Sci-Fi', 'content_type' => 4, 'sort_order' => 7],
            ['name' => 'Fantasy', 'content_type' => 4, 'sort_order' => 8],
        ];

        $now = now();
        foreach ($genres as &$genre) {
            $genre['created_at'] = $now;
            $genre['updated_at'] = $now;
        }
        DB::table('tbl_content_genres')->insert($genres);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_content_genres');
    }
};
