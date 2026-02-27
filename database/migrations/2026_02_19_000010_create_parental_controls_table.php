<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_family_links', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('parent_user_id');
            $table->unsignedBigInteger('teen_user_id');
            $table->string('pairing_code', 8)->nullable();
            $table->smallInteger('status')->default(0);        // 0=pending, 1=linked, 2=unlinked
            $table->jsonb('controls')->nullable();              // Parental control settings JSON
            $table->timestampsTz();

            $table->foreign('parent_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('teen_user_id')->references('id')->on('tbl_users')->onDelete('cascade');

            $table->unique(['parent_user_id', 'teen_user_id']);
            $table->index(['teen_user_id', 'status']);
            $table->index('pairing_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_family_links');
    }
};
