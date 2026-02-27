<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('icon', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('profile_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->smallInteger('account_type'); // maps to tbl_users.account_type (1,2,3,4)
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE profile_categories ADD CONSTRAINT chk_profile_cat_account_type CHECK (account_type IN (1, 2, 3, 4))');

        Schema::create('profile_sub_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('category_id');
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->foreign('category_id')->references('id')->on('profile_categories')->onDelete('cascade');
            $table->index('category_id');
        });

        // Now add foreign keys to tbl_users for profile_category_id and profile_sub_category_id
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->foreign('profile_category_id')->references('id')->on('profile_categories')->onDelete('set null');
            $table->foreign('profile_sub_category_id')->references('id')->on('profile_sub_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropForeign(['profile_category_id']);
            $table->dropForeign(['profile_sub_category_id']);
        });

        Schema::dropIfExists('profile_sub_categories');
        Schema::dropIfExists('profile_categories');
        Schema::dropIfExists('interests');
    }
};
