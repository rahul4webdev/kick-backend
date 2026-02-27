<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->smallInteger('sensitive_content_level')->default(1)->after('quiet_mode_auto_reply');
            // 0 = allow, 1 = limit (default), 2 = limit even more
        });
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn('sensitive_content_level');
        });
    }
};
