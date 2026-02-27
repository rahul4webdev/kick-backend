<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfileCategoriesSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();

        // account_type: 1=influencer, 2=business, 3=production_house, 4=news_media
        $categories = [
            // Influencer/Creator (account_type = 1)
            [
                'name' => 'Health & Fitness Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Fitness Trainer', 'Yoga Instructor', 'Nutritionist', 'Wellness Coach'],
            ],
            [
                'name' => 'Education Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Teacher', 'Tutor', 'Course Creator', 'Motivational Speaker'],
            ],
            [
                'name' => 'Entertainment Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Actor', 'Dancer', 'Singer', 'Stand-up Comedian', 'Content Creator'],
            ],
            [
                'name' => 'Technology Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Tech Reviewer', 'Developer', 'Gadget Enthusiast'],
            ],
            [
                'name' => 'Food Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Chef', 'Food Blogger', 'Recipe Creator', 'Food Critic'],
            ],
            [
                'name' => 'Travel Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Travel Blogger', 'Adventure Enthusiast', 'Travel Photographer'],
            ],
            [
                'name' => 'Fashion & Beauty Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Fashion Blogger', 'Makeup Artist', 'Stylist', 'Model'],
            ],
            [
                'name' => 'Gaming Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Gamer', 'Game Streamer', 'Esports Player', 'Game Reviewer'],
            ],
            [
                'name' => 'Music Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Musician', 'Music Producer', 'DJ', 'Songwriter'],
            ],
            [
                'name' => 'Photography Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Photographer', 'Videographer', 'Photo Editor'],
            ],
            [
                'name' => 'Sports Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Athlete', 'Sports Analyst', 'Coach', 'Fitness Influencer'],
            ],
            [
                'name' => 'Comedy Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Comedian', 'Sketch Artist', 'Meme Creator', 'Satirist'],
            ],
            [
                'name' => 'Lifestyle Creator',
                'account_type' => 1,
                'requires_approval' => false,
                'subs' => ['Lifestyle Blogger', 'Minimalist', 'Home Decor', 'Parenting'],
            ],

            // Business (account_type = 2)
            [
                'name' => 'Apparel & Fashion',
                'account_type' => 2,
                'requires_approval' => false,
                'subs' => ['Apparel Shop', 'Footwear Store', 'Accessories Store', 'Boutique'],
            ],
            [
                'name' => 'Electronics & Technology',
                'account_type' => 2,
                'requires_approval' => false,
                'subs' => ['Electronics Store', 'Mobile Shop', 'Computer Store', 'IT Company'],
            ],
            [
                'name' => 'Food & Beverage',
                'account_type' => 2,
                'requires_approval' => false,
                'subs' => ['Restaurant', 'Cafe', 'Bakery', 'Food Delivery', 'Catering'],
            ],
            [
                'name' => 'Service Company',
                'account_type' => 2,
                'requires_approval' => false,
                'subs' => ['Consulting', 'Marketing Agency', 'Design Studio', 'Legal Services'],
            ],
            [
                'name' => 'Retail & E-commerce',
                'account_type' => 2,
                'requires_approval' => false,
                'subs' => ['Online Store', 'Retail Shop', 'Wholesale', 'Marketplace'],
            ],
            [
                'name' => 'Real Estate',
                'account_type' => 2,
                'requires_approval' => false,
                'subs' => ['Real Estate Agency', 'Property Developer', 'Interior Design'],
            ],
            [
                'name' => 'Healthcare',
                'account_type' => 2,
                'requires_approval' => false,
                'subs' => ['Hospital', 'Clinic', 'Pharmacy', 'Diagnostic Lab', 'Wellness Center'],
            ],
            [
                'name' => 'Education & Training',
                'account_type' => 2,
                'requires_approval' => false,
                'subs' => ['School', 'Coaching Center', 'Online Academy', 'Training Institute'],
            ],
            [
                'name' => 'Manufacturing',
                'account_type' => 2,
                'requires_approval' => false,
                'subs' => ['Manufacturing Unit', 'Factory', 'Workshop'],
            ],

            // Production House (account_type = 3) — requires approval
            [
                'name' => 'Film Production',
                'account_type' => 3,
                'requires_approval' => true,
                'subs' => ['Feature Film', 'Short Film', 'Documentary', 'Animation'],
            ],
            [
                'name' => 'Music Production',
                'account_type' => 3,
                'requires_approval' => true,
                'subs' => ['Music Video', 'Album Production', 'Audio Production'],
            ],
            [
                'name' => 'Digital Content Production',
                'account_type' => 3,
                'requires_approval' => true,
                'subs' => ['Web Series', 'Podcast', 'YouTube Channel', 'OTT Content'],
            ],

            // News & Media (account_type = 4) — requires approval
            [
                'name' => 'News Channel',
                'account_type' => 4,
                'requires_approval' => true,
                'subs' => ['TV News Channel', 'Online News Portal', 'Regional News'],
            ],
            [
                'name' => 'Print Media',
                'account_type' => 4,
                'requires_approval' => true,
                'subs' => ['Newspaper', 'Magazine', 'Journal'],
            ],
            [
                'name' => 'Digital Media',
                'account_type' => 4,
                'requires_approval' => true,
                'subs' => ['Blog', 'Digital Magazine', 'News Aggregator', 'Media House'],
            ],
        ];

        $sortOrder = 0;
        foreach ($categories as $cat) {
            $categoryId = DB::table('profile_categories')->insertGetId([
                'name' => $cat['name'],
                'account_type' => $cat['account_type'],
                'requires_approval' => $cat['requires_approval'],
                'is_active' => true,
                'sort_order' => $sortOrder++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $subData = [];
            $subSort = 0;
            foreach ($cat['subs'] as $subName) {
                $subData[] = [
                    'category_id' => $categoryId,
                    'name' => $subName,
                    'is_active' => true,
                    'sort_order' => $subSort++,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('profile_sub_categories')->insert($subData);
        }
    }
}
