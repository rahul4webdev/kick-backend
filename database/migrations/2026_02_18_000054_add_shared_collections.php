<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_shared column to tbl_collections
        Schema::table('tbl_collections', function (Blueprint $table) {
            $table->boolean('is_shared')->default(false)->after('is_default');
        });

        // Create collection members table
        Schema::create('tbl_collection_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('collection_id');
            $table->unsignedBigInteger('user_id');
            $table->smallInteger('role')->default(1); // 1=member, 2=admin
            $table->smallInteger('status')->default(0); // 0=pending, 1=accepted, 2=declined
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->timestampsTz();

            $table->foreign('collection_id')->references('id')->on('tbl_collections')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('invited_by')->references('id')->on('tbl_users')->onDelete('set null');
            $table->unique(['collection_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_collection_members');

        Schema::table('tbl_collections', function (Blueprint $table) {
            $table->dropColumn('is_shared');
        });
    }
};
