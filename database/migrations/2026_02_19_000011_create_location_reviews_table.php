<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_location_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('place_title', 200);
            $table->float('place_lat');
            $table->float('place_lon');
            $table->smallInteger('rating');               // 1-5 stars
            $table->text('review_text')->nullable();
            $table->jsonb('photos')->nullable();           // Array of photo URLs
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');

            $table->unique(['user_id', 'place_title', 'place_lat', 'place_lon'], 'uq_user_location');
            $table->index(['place_lat', 'place_lon']);
            $table->index('place_title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_location_reviews');
    }
};
