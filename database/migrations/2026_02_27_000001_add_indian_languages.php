<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $indianLanguages = [
            ['code' => 'bn', 'title' => 'বাংলা', 'localized_title' => 'Bengali', 'csv_file' => 'uploads/bengali.csv', 'status' => true, 'is_default' => false],
            ['code' => 'ta', 'title' => 'தமிழ்', 'localized_title' => 'Tamil', 'csv_file' => 'uploads/tamil.csv', 'status' => true, 'is_default' => false],
            ['code' => 'te', 'title' => 'తెలుగు', 'localized_title' => 'Telugu', 'csv_file' => 'uploads/telugu.csv', 'status' => true, 'is_default' => false],
            ['code' => 'mr', 'title' => 'मराठी', 'localized_title' => 'Marathi', 'csv_file' => 'uploads/marathi.csv', 'status' => true, 'is_default' => false],
            ['code' => 'gu', 'title' => 'ગુજરાતી', 'localized_title' => 'Gujarati', 'csv_file' => 'uploads/gujarati.csv', 'status' => true, 'is_default' => false],
            ['code' => 'kn', 'title' => 'ಕನ್ನಡ', 'localized_title' => 'Kannada', 'csv_file' => 'uploads/kannada.csv', 'status' => true, 'is_default' => false],
            ['code' => 'ml', 'title' => 'മലയാളം', 'localized_title' => 'Malayalam', 'csv_file' => 'uploads/malayalam.csv', 'status' => true, 'is_default' => false],
            ['code' => 'pa', 'title' => 'ਪੰਜਾਬੀ', 'localized_title' => 'Punjabi', 'csv_file' => 'uploads/punjabi.csv', 'status' => true, 'is_default' => false],
            ['code' => 'or', 'title' => 'ଓଡ଼ିଆ', 'localized_title' => 'Odia', 'csv_file' => 'uploads/odia.csv', 'status' => true, 'is_default' => false],
            ['code' => 'as', 'title' => 'অসমীয়া', 'localized_title' => 'Assamese', 'csv_file' => 'uploads/assamese.csv', 'status' => true, 'is_default' => false],
            ['code' => 'ur', 'title' => 'اردو', 'localized_title' => 'Urdu', 'csv_file' => 'uploads/urdu.csv', 'status' => true, 'is_default' => false],
            ['code' => 'mai', 'title' => 'मैथिली', 'localized_title' => 'Maithili', 'csv_file' => 'uploads/maithili.csv', 'status' => true, 'is_default' => false],
            ['code' => 'sa', 'title' => 'संस्कृतम्', 'localized_title' => 'Sanskrit', 'csv_file' => 'uploads/sanskrit.csv', 'status' => true, 'is_default' => false],
            ['code' => 'kok', 'title' => 'कोंकणी', 'localized_title' => 'Konkani', 'csv_file' => 'uploads/konkani.csv', 'status' => true, 'is_default' => false],
            ['code' => 'doi', 'title' => 'डोगरी', 'localized_title' => 'Dogri', 'csv_file' => 'uploads/dogri.csv', 'status' => true, 'is_default' => false],
            ['code' => 'ks', 'title' => 'कॉशुर', 'localized_title' => 'Kashmiri', 'csv_file' => 'uploads/kashmiri.csv', 'status' => true, 'is_default' => false],
            ['code' => 'mni', 'title' => 'মৈতৈলোন্', 'localized_title' => 'Manipuri', 'csv_file' => 'uploads/manipuri.csv', 'status' => true, 'is_default' => false],
            ['code' => 'brx', 'title' => 'बड़ो', 'localized_title' => 'Bodo', 'csv_file' => 'uploads/bodo.csv', 'status' => true, 'is_default' => false],
            ['code' => 'sat', 'title' => 'ᱥᱟᱱᱛᱟᱲᱤ', 'localized_title' => 'Santali', 'csv_file' => 'uploads/santali.csv', 'status' => true, 'is_default' => false],
            ['code' => 'sd', 'title' => 'سنڌي', 'localized_title' => 'Sindhi', 'csv_file' => 'uploads/sindhi.csv', 'status' => true, 'is_default' => false],
            ['code' => 'ne', 'title' => 'नेपाली', 'localized_title' => 'Nepali', 'csv_file' => 'uploads/nepali.csv', 'status' => true, 'is_default' => false],
        ];

        $now = now();
        foreach ($indianLanguages as $lang) {
            // Only insert if code doesn't already exist
            $exists = DB::table('languages')->where('code', $lang['code'])->exists();
            if (!$exists) {
                DB::table('languages')->insert(array_merge($lang, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        $codes = ['bn', 'ta', 'te', 'mr', 'gu', 'kn', 'ml', 'pa', 'or', 'as', 'ur', 'mai', 'sa', 'kok', 'doi', 'ks', 'mni', 'brx', 'sat', 'sd', 'ne'];
        DB::table('languages')->whereIn('code', $codes)->delete();
    }
};
