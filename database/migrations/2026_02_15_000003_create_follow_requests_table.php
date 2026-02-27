<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('from_user_id');
            $table->unsignedBigInteger('to_user_id');
            $table->smallInteger('status')->default(0); // 0=pending, 1=accepted, 2=rejected
            $table->timestampsTz();

            $table->foreign('from_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->foreign('to_user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['from_user_id', 'to_user_id']);
        });

        // Composite index for fetching pending requests for a user
        DB::statement('CREATE INDEX idx_follow_requests_to_user ON follow_requests (to_user_id, status) WHERE status = 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_requests');
    }
};
