<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->string('og_title', 200)->nullable()->after('metadata');
            $table->string('og_description', 500)->nullable()->after('og_title');
            $table->string('og_image', 500)->nullable()->after('og_description');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropColumn(['og_title', 'og_description', 'og_image']);
        });
    }
};
