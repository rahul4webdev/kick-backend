<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->smallInteger('visibility')->default(0)->after('can_comment');
        });

        DB::statement('ALTER TABLE tbl_post ADD CONSTRAINT chk_post_visibility CHECK (visibility IN (0, 1, 2))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tbl_post DROP CONSTRAINT IF EXISTS chk_post_visibility');

        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
};
