<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Enhance products table
        Schema::table('tbl_products', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(10.00)->after('affiliate_commission_rate');
            $table->text('search_keywords')->nullable()->after('commission_rate');
            $table->boolean('featured_in_marketplace')->default(false)->after('search_keywords');
        });

        // Enhance post product tags for reel overlay positioning
        Schema::table('tbl_post_product_tags', function (Blueprint $table) {
            $table->decimal('display_position_x', 5, 2)->nullable()->after('label');
            $table->decimal('display_position_y', 5, 2)->nullable()->after('display_position_x');
            $table->integer('display_time_start_ms')->nullable()->after('display_position_y');
            $table->integer('display_time_end_ms')->nullable()->after('display_time_start_ms');
            $table->boolean('is_auto_affiliate')->default(false)->after('display_time_end_ms');
        });

        // Full-text search index on products
        DB::statement("ALTER TABLE tbl_products ADD COLUMN IF NOT EXISTS search_vector TSVECTOR");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_products_search ON tbl_products USING GIN(search_vector)");

        // Update search vectors for existing products
        DB::statement("
            UPDATE tbl_products SET search_vector =
                setweight(to_tsvector('english', COALESCE(name, '')), 'A') ||
                setweight(to_tsvector('english', COALESCE(description, '')), 'B') ||
                setweight(to_tsvector('english', COALESCE(search_keywords, '')), 'C')
        ");
    }

    public function down(): void
    {
        Schema::table('tbl_post_product_tags', function (Blueprint $table) {
            $table->dropColumn(['display_position_x', 'display_position_y', 'display_time_start_ms', 'display_time_end_ms', 'is_auto_affiliate']);
        });

        DB::statement("DROP INDEX IF EXISTS idx_products_search");
        DB::statement("ALTER TABLE tbl_products DROP COLUMN IF EXISTS search_vector");

        Schema::table('tbl_products', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'search_keywords', 'featured_in_marketplace']);
        });
    }
};
