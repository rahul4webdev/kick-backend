<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->jsonb('captions')->nullable();
            $table->boolean('has_captions')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropColumn(['captions', 'has_captions']);
        });
    }
};
