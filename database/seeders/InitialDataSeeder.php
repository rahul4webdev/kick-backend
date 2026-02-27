<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin account (using Crypt::encrypt to match original system)
        DB::table('tbl_admin')->insertOrIgnore([
            'admin_name' => 'Admin',
            'admin_username' => 'admin',
            'admin_password' => Crypt::encrypt('admin123'),
            'admin_profile' => '',
            'user_type' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create default settings row
        DB::table('tbl_settings')->insertOrIgnore([
            'id' => 1,
            'app_name' => 'Kick',
            'currency' => '$',
            'coin_value' => 0.01,
            'min_redeem_coins' => 100,
            'max_upload_daily' => 10,
            'max_comment_daily' => 50,
            'max_comment_reply_daily' => 50,
            'max_story_daily' => 10,
            'max_comment_pins' => 3,
            'max_post_pins' => 3,
            'max_user_links' => 5,
            'max_images_per_post' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create default report reasons
        $reasons = [
            'Spam or misleading',
            'Nudity or sexual content',
            'Hate speech or symbols',
            'Violence or dangerous organizations',
            'Sale of illegal or regulated goods',
            'Bullying or harassment',
            'Intellectual property violation',
            'Suicide or self-injury',
            'Eating disorders',
            'Scam or fraud',
            'False information',
            'I just don\'t like it',
        ];

        foreach ($reasons as $reason) {
            DB::table('report_reasons')->insertOrIgnore([
                'title' => $reason,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create default user levels
        $levels = [
            ['level' => 0, 'coins_collection' => 0],
            ['level' => 1, 'coins_collection' => 100],
            ['level' => 2, 'coins_collection' => 500],
            ['level' => 3, 'coins_collection' => 1000],
            ['level' => 4, 'coins_collection' => 5000],
            ['level' => 5, 'coins_collection' => 10000],
        ];

        foreach ($levels as $level) {
            DB::table('user_levels')->insertOrIgnore([
                'level' => $level['level'],
                'coins_collection' => $level['coins_collection'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create default redeem gateways
        $gateways = ['PayPal', 'Bank Transfer', 'UPI'];
        foreach ($gateways as $gateway) {
            DB::table('tbl_redeem_gateways')->insertOrIgnore([
                'title' => $gateway,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
