<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_user_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->float('lat');
            $table->float('lon');
            $table->boolean('is_sharing')->default(false);
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->unique('user_id');
            $table->index(['is_sharing', 'location_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_locations');
    }
};
