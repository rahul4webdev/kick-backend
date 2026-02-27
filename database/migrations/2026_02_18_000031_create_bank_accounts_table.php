<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('label', 100)->nullable();
            $table->string('gateway', 100);
            $table->string('account_holder_name', 200)->nullable();
            $table->text('account_details');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_bank_accounts');
    }
};
