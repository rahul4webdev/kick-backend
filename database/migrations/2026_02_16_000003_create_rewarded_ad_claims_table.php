<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_rewarded_ad_claims', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->date('claim_date');
            $table->smallInteger('claim_count')->default(1);
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique(['user_id', 'claim_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_rewarded_ad_claims');
    }
};
