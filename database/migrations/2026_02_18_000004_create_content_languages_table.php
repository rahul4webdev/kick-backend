<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_content_languages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100);
            $table->string('code', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        // Seed default languages
        $languages = [
            ['name' => 'Hindi', 'code' => 'hi', 'sort_order' => 1],
            ['name' => 'English', 'code' => 'en', 'sort_order' => 2],
            ['name' => 'Punjabi', 'code' => 'pa', 'sort_order' => 3],
            ['name' => 'Tamil', 'code' => 'ta', 'sort_order' => 4],
            ['name' => 'Telugu', 'code' => 'te', 'sort_order' => 5],
            ['name' => 'Kannada', 'code' => 'kn', 'sort_order' => 6],
            ['name' => 'Malayalam', 'code' => 'ml', 'sort_order' => 7],
            ['name' => 'Bengali', 'code' => 'bn', 'sort_order' => 8],
            ['name' => 'Marathi', 'code' => 'mr', 'sort_order' => 9],
            ['name' => 'Gujarati', 'code' => 'gu', 'sort_order' => 10],
            ['name' => 'Bhojpuri', 'code' => 'bho', 'sort_order' => 11],
            ['name' => 'Haryanvi', 'code' => 'bgc', 'sort_order' => 12],
            ['name' => 'Rajasthani', 'code' => 'raj', 'sort_order' => 13],
            ['name' => 'Urdu', 'code' => 'ur', 'sort_order' => 14],
            ['name' => 'Spanish', 'code' => 'es', 'sort_order' => 15],
            ['name' => 'Arabic', 'code' => 'ar', 'sort_order' => 16],
            ['name' => 'French', 'code' => 'fr', 'sort_order' => 17],
            ['name' => 'Korean', 'code' => 'ko', 'sort_order' => 18],
            ['name' => 'Japanese', 'code' => 'ja', 'sort_order' => 19],
            ['name' => 'Chinese', 'code' => 'zh', 'sort_order' => 20],
        ];

        $now = now();
        foreach ($languages as &$language) {
            $language['created_at'] = $now;
            $language['updated_at'] = $now;
        }
        DB::table('tbl_content_languages')->insert($languages);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_content_languages');
    }
};
