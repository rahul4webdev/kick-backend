<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            // ─── IMA Mid-Roll ───
            $table->integer('ima_midroll_frequency')->default(0)->after('ima_ad_tag_ios');
            $table->string('ima_midroll_ad_tag_android', 1024)->nullable()->after('ima_midroll_frequency');
            $table->string('ima_midroll_ad_tag_ios', 1024)->nullable()->after('ima_midroll_ad_tag_android');

            // ─── IMA Post-Roll ───
            $table->integer('ima_postroll_frequency')->default(0)->after('ima_midroll_ad_tag_ios');
            $table->string('ima_postroll_ad_tag_android', 1024)->nullable()->after('ima_postroll_frequency');
            $table->string('ima_postroll_ad_tag_ios', 1024)->nullable()->after('ima_postroll_ad_tag_android');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn([
                'ima_midroll_frequency',
                'ima_midroll_ad_tag_android', 'ima_midroll_ad_tag_ios',
                'ima_postroll_frequency',
                'ima_postroll_ad_tag_android', 'ima_postroll_ad_tag_ios',
            ]);
        });
    }
};
