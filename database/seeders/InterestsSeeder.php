<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InterestsSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        $interests = [
            'Health & Fitness',
            'Education',
            'Sports',
            'Entertainment',
            'Technology',
            'Food & Cooking',
            'Travel',
            'Fashion & Beauty',
            'Gaming',
            'Music',
            'Art & Design',
            'Business & Finance',
            'News & Politics',
            'Science',
            'Comedy',
            'Lifestyle',
            'Nature & Animals',
            'DIY & Crafts',
            'Automotive',
            'Photography',
        ];

        $data = [];
        foreach ($interests as $index => $name) {
            $data[] = [
                'name' => $name,
                'icon' => null,
                'is_active' => true,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('interests')->insert($data);
    }
}
