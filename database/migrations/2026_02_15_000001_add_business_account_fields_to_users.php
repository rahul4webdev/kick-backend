<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            // Account type: 0=personal, 1=influencer/creator, 2=business, 3=production_house, 4=news_media
            $table->smallInteger('account_type')->default(0)->after('is_moderator');
            $table->smallInteger('business_status')->default(0)->after('account_type'); // 0=not_applied, 1=pending, 2=approved, 3=rejected
            $table->unsignedBigInteger('profile_category_id')->nullable()->after('business_status');
            $table->unsignedBigInteger('profile_sub_category_id')->nullable()->after('profile_category_id');
            $table->boolean('is_private')->default(false)->after('profile_sub_category_id');
            $table->boolean('is_monetized')->default(false)->after('is_private');
            $table->smallInteger('monetization_status')->default(0)->after('is_monetized'); // 0=not_applied, 1=pending, 2=approved, 3=rejected

            // Enhanced device capture
            $table->string('device_brand', 50)->nullable()->after('device_token');
            $table->string('device_model', 100)->nullable()->after('device_brand');
            $table->string('device_os', 20)->nullable()->after('device_model');
            $table->string('device_os_version', 20)->nullable()->after('device_os');
            $table->string('device_carrier', 50)->nullable()->after('device_os_version');

            // Interest tracking (comma-separated IDs, same pattern as saved_music_ids)
            $table->text('interest_ids')->nullable()->after('saved_music_ids');

            // Foreign keys (will be added after interests/categories tables exist)
        });

        // CHECK constraint for account_type
        DB::statement('ALTER TABLE tbl_users ADD CONSTRAINT chk_account_type CHECK (account_type IN (0, 1, 2, 3, 4))');

        // Index on account_type for filtering business users
        DB::statement('CREATE INDEX idx_users_account_type ON tbl_users (account_type) WHERE account_type > 0');
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn([
                'account_type', 'business_status', 'profile_category_id', 'profile_sub_category_id',
                'is_private', 'is_monetized', 'monetization_status',
                'device_brand', 'device_model', 'device_os', 'device_os_version', 'device_carrier',
                'interest_ids',
            ]);
        });
    }
};
