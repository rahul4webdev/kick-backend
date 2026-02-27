<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen post visibility constraint to allow 3 (subscriber-only)
        DB::statement('ALTER TABLE tbl_post DROP CONSTRAINT IF EXISTS chk_post_visibility');
        DB::statement('ALTER TABLE tbl_post ADD CONSTRAINT chk_post_visibility CHECK (visibility IN (0, 1, 2, 3))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tbl_post DROP CONSTRAINT IF EXISTS chk_post_visibility');
        DB::statement('ALTER TABLE tbl_post ADD CONSTRAINT chk_post_visibility CHECK (visibility IN (0, 1, 2))');
    }
};
