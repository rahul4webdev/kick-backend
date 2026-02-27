<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_images', function (Blueprint $table) {
            $table->string('alt_text', 500)->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('post_images', function (Blueprint $table) {
            $table->dropColumn('alt_text');
        });
    }
};
