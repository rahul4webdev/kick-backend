<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->bigInteger('duet_source_post_id')->nullable()->after('linked_previous_post_id');
            $table->boolean('allow_duet')->default(true)->after('duet_source_post_id');
            $table->string('duet_layout', 20)->nullable()->after('allow_duet'); // side_by_side, top_bottom, pip
            $table->index('duet_source_post_id');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropIndex(['duet_source_post_id']);
            $table->dropColumn(['duet_source_post_id', 'allow_duet', 'duet_layout']);
        });
    }
};
