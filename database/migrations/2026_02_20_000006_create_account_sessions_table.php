<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_account_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('device_id', 200);
            $table->bigInteger('user_id');
            $table->string('auth_token', 500);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_used_at')->useCurrent();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['device_id', 'user_id']);
        });

        // Index for fast device lookups
        Schema::table('tbl_account_sessions', function (Blueprint $table) {
            $table->index('device_id', 'idx_account_sessions_device');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_account_sessions');
    }
};
