<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_collaborators', function (Blueprint $table) {
            $table->string('role', 20)->default('collaborator')->after('status');
            $table->decimal('credit_share', 5, 2)->default(0.00)->after('role');
        });

        Schema::table('tbl_post', function (Blueprint $table) {
            $table->boolean('is_collaborative')->default(false)->after('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::table('post_collaborators', function (Blueprint $table) {
            $table->dropColumn(['role', 'credit_share']);
        });

        Schema::table('tbl_post', function (Blueprint $table) {
            $table->dropColumn('is_collaborative');
        });
    }
};
