<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_hash_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('hashtag', 100)->unique();
            $table->bigInteger('post_count')->default(0);
            $table->boolean('on_explore')->default(false);
            $table->timestampsTz();
        });

        Schema::create('tbl_gifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('coin_price')->default(0);
            $table->string('image', 555)->nullable();
            $table->timestampsTz();
        });

        Schema::create('tbl_coin_plan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('image', 999)->nullable();
            $table->boolean('status')->default(true);
            $table->integer('coin_amount')->default(0);
            $table->float('coin_plan_price')->default(0);
            $table->string('playstore_product_id', 100)->nullable();
            $table->string('appstore_product_id', 100)->nullable();
            $table->timestampsTz();
        });

        Schema::create('tbl_redeem_gateways', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title', 255);
            $table->timestampsTz();
        });

        Schema::create('tbl_redeem_request', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('request_number', 255)->nullable();
            $table->string('gateway', 20)->nullable();
            $table->string('account', 100)->nullable();
            $table->string('amount', 100)->nullable();
            $table->integer('coins')->default(0);
            $table->float('coin_value')->default(0);
            $table->smallInteger('status')->default(0);
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_redeem_request');
        Schema::dropIfExists('tbl_redeem_gateways');
        Schema::dropIfExists('tbl_coin_plan');
        Schema::dropIfExists('tbl_gifts');
        Schema::dropIfExists('tbl_hash_tags');
    }
};
