<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add creator tier to users
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->smallInteger('creator_tier')->default(0); // 0=none, 1=bronze, 2=silver, 3=gold, 4=platinum
            $table->decimal('custom_commission_rate', 5, 2)->nullable(); // Overrides default commission per tier
        });

        // Creator tiers config table
        Schema::create('tbl_creator_tiers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // Bronze, Silver, Gold, Platinum
            $table->smallInteger('level'); // 1, 2, 3, 4
            $table->integer('min_followers')->default(0);
            $table->integer('min_total_views')->default(0);
            $table->integer('min_total_likes')->default(0);
            $table->decimal('commission_rate', 5, 2)->default(10.00); // Commission percentage (lower = better for creator)
            $table->string('badge_color', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tip amounts config table
        Schema::create('tbl_tip_amounts', function (Blueprint $table) {
            $table->id();
            $table->integer('coins')->default(0);
            $table->string('label', 50)->nullable(); // e.g., "Small Tip", "Big Tip"
            $table->string('emoji', 10)->nullable(); // Display emoji
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default creator tiers
        \Illuminate\Support\Facades\DB::table('tbl_creator_tiers')->insert([
            ['name' => 'Bronze', 'level' => 1, 'min_followers' => 1000, 'min_total_views' => 10000, 'min_total_likes' => 500, 'commission_rate' => 15.00, 'badge_color' => '#CD7F32', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Silver', 'level' => 2, 'min_followers' => 5000, 'min_total_views' => 100000, 'min_total_likes' => 5000, 'commission_rate' => 12.00, 'badge_color' => '#C0C0C0', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gold', 'level' => 3, 'min_followers' => 25000, 'min_total_views' => 500000, 'min_total_likes' => 25000, 'commission_rate' => 8.00, 'badge_color' => '#FFD700', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Platinum', 'level' => 4, 'min_followers' => 100000, 'min_total_views' => 2000000, 'min_total_likes' => 100000, 'commission_rate' => 5.00, 'badge_color' => '#E5E4E2', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed default tip amounts
        \Illuminate\Support\Facades\DB::table('tbl_tip_amounts')->insert([
            ['coins' => 5, 'label' => 'Nice', 'emoji' => null, 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['coins' => 10, 'label' => 'Great', 'emoji' => null, 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['coins' => 25, 'label' => 'Amazing', 'emoji' => null, 'sort_order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['coins' => 50, 'label' => 'Super', 'emoji' => null, 'sort_order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['coins' => 100, 'label' => 'Legendary', 'emoji' => null, 'sort_order' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_tip_amounts');
        Schema::dropIfExists('tbl_creator_tiers');
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['creator_tier', 'custom_commission_rate']);
        });
    }
};
