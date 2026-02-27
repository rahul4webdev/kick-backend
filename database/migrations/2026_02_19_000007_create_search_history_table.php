<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE tbl_search_history (
                id BIGSERIAL PRIMARY KEY,
                user_id BIGINT REFERENCES tbl_users(id) ON DELETE CASCADE,
                keyword VARCHAR(255) NOT NULL,
                search_type VARCHAR(30) DEFAULT 'posts',
                result_count INTEGER DEFAULT 0,
                created_at TIMESTAMPTZ DEFAULT NOW()
            )
        ");

        DB::statement("CREATE INDEX idx_search_history_user ON tbl_search_history (user_id)");
        DB::statement("CREATE INDEX idx_search_history_keyword ON tbl_search_history (LOWER(keyword))");
        DB::statement("CREATE INDEX idx_search_history_created ON tbl_search_history (created_at DESC)");
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_search_history');
    }
};
