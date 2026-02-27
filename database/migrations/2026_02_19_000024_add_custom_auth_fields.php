<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add custom auth fields to users table
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->string('password_hash', 255)->nullable()->after('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_code', 6)->nullable();
            $table->timestamp('email_verification_expires_at')->nullable();
            $table->string('password_reset_code', 6)->nullable();
            $table->timestamp('password_reset_expires_at')->nullable();
        });

        // Add email verification toggle to settings
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->boolean('email_verification_enabled')->default(false);
            $table->string('smtp_host', 255)->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username', 255)->nullable();
            $table->string('smtp_password', 255)->nullable();
            $table->string('smtp_encryption', 10)->nullable()->default('tls');
            $table->string('smtp_from_email', 255)->nullable();
            $table->string('smtp_from_name', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['password_hash', 'email_verified_at', 'email_verification_code', 'email_verification_expires_at', 'password_reset_code', 'password_reset_expires_at']);
        });

        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn(['email_verification_enabled', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name']);
        });
    }
};
