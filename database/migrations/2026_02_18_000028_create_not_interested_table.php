<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tbl_not_interested', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('post_id');
            $table->timestampsTz();
            $table->unique(['user_id', 'post_id']);
        });

        Schema::table('tbl_not_interested', function (Blueprint $table) {
            $table->index('user_id', 'idx_not_interested_user');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tbl_not_interested');
    }
};
