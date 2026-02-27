<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_green_screen_bgs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('image', 500)->nullable();
            $table->string('video', 500)->nullable();
            $table->string('type', 20)->default('image'); // image, video
            $table->string('category', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_green_screen_bgs');
    }
};
