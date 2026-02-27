<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_shared_access', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account_user_id');    // The account being managed
            $table->unsignedBigInteger('member_user_id');     // The team member
            $table->smallInteger('role')->default(3);          // 1=admin, 2=editor, 3=viewer
            $table->smallInteger('status')->default(0);        // 0=pending, 1=accepted, 2=declined
            $table->jsonb('permissions')->nullable();           // Granular permissions JSON
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestampsTz();

            $table->foreign('account_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('member_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('tbl_users')->onDelete('set null');

            $table->unique(['account_user_id', 'member_user_id']);
            $table->index(['member_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_shared_access');
    }
};
