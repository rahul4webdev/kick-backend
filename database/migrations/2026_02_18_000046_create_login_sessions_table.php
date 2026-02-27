<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_login_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('device', 50)->nullable();
            $table->string('device_brand', 100)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('device_os', 50)->nullable();
            $table->string('device_os_version', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('login_method', 30)->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamp('logged_in_at')->useCurrent();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'is_current']);
            $table->index(['user_id', 'logged_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_login_sessions');
    }
};
