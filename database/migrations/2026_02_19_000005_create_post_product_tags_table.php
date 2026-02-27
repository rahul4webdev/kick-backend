<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_post_product_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('product_id');
            $table->string('label', 100)->nullable();
            $table->timestampsTz();

            $table->foreign('post_id')->references('id')->on('tbl_post')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('tbl_products')->onDelete('cascade');
            $table->unique(['post_id', 'product_id']);
            $table->index('post_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_post_product_tags');
    }
};
