<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->bigInteger('stitch_source_post_id')->nullable()->after('duet_layout');
            $table->integer('stitch_start_ms')->nullable()->after('stitch_source_post_id');
            $table->integer('stitch_end_ms')->nullable()->after('stitch_start_ms');
            $table->boolean('allow_stitch')->default(true)->after('stitch_end_ms');
            $table->index('stitch_source_post_id');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropIndex(['stitch_source_post_id']);
            $table->dropColumn(['stitch_source_post_id', 'stitch_start_ms', 'stitch_end_ms', 'allow_stitch']);
        });
    }
};
