<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->boolean('two_fa_enabled')->default(false);
            $table->string('two_fa_secret', 64)->nullable();
            $table->jsonb('two_fa_backup_codes')->nullable();
            $table->timestamp('two_fa_verified_at')->nullable();
        });

        // Temp tokens for 2FA verification during login
        Schema::create('tbl_two_fa_tokens', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('token', 255);
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index('token');
            $table->index(['user_id', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['two_fa_enabled', 'two_fa_secret', 'two_fa_backup_codes', 'two_fa_verified_at']);
        });

        Schema::dropIfExists('tbl_two_fa_tokens');
    }
};
