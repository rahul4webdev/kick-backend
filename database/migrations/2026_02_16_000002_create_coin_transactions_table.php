<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_coin_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->smallInteger('type'); // 1=gift_received, 2=gift_sent, 3=purchase, 4=withdrawal, 5=ad_reward, 6=admin_credit, 7=registration_bonus
            $table->integer('coins')->default(0);
            $table->smallInteger('direction'); // 1=credit, 0=debit
            $table->unsignedBigInteger('related_user_id')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['user_id', 'direction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_coin_transactions');
    }
};
