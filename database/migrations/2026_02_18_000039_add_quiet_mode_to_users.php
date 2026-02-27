<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->boolean('quiet_mode_enabled')->default(false)->after('comment_approval_enabled');
            $table->timestampTz('quiet_mode_until')->nullable()->after('quiet_mode_enabled');
            $table->string('quiet_mode_auto_reply', 255)->nullable()->after('quiet_mode_until');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['quiet_mode_enabled', 'quiet_mode_until', 'quiet_mode_auto_reply']);
        });
    }
};
