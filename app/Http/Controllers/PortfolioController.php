<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Portfolio;
use App\Models\PortfolioSection;
use App\Models\Posts;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PortfolioController extends Controller
{
    /**
     * Create or update the current user's portfolio.
     */
    public function createOrUpdate(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'Account is frozen');
        }

        $portfolio = Portfolio::firstOrNew(['user_id' => $user->id]);
        $isNew = !$portfolio->exists;

        // Slug defaults to username, but can be customized
        if ($request->has('slug')) {
            $slug = Str::slug($request->slug);
            // Check uniqueness (excluding self)
            $existing = Portfolio::where('slug', $slug)
                ->where('user_id', '!=', $user->id)
                ->first();
            if ($existing) {
                return GlobalFunction::sendSimpleResponse(false, 'Slug already taken');
            }
            $portfolio->slug = $slug;
        } elseif ($isNew) {
            $portfolio->slug = $user->username;
        }

        if ($request->has('is_active')) $portfolio->is_active = $request->is_active;
        if ($request->has('theme')) $portfolio->theme = $request->theme;
        if ($request->has('custom_colors')) $portfolio->custom_colors = $request->custom_colors;
        if ($request->has('headline')) $portfolio->headline = $request->headline;
        if ($request->has('bio_override')) $portfolio->bio_override = $request->bio_override;
        if ($request->has('featured_post_ids')) $portfolio->featured_post_ids = $request->featured_post_ids;
        if ($request->has('show_products')) $portfolio->show_products = $request->show_products;
        if ($request->has('show_links')) $portfolio->show_links = $request->show_links;
        if ($request->has('show_subscription_cta')) $portfolio->show_subscription_cta = $request->show_subscription_cta;

        $portfolio->save();

        $portfolio->load('sections');

        return GlobalFunction::sendDataResponse(true, $isNew ? 'Portfolio created' : 'Portfolio updated', [
            'portfolio' => $this->formatPortfolio($portfolio),
        ]);
    }

    /**
     * Fetch current user's portfolio.
     */
    public function fetchMine(Request $request)
    {
        $user = GlobalFunction::getAuthUser();

        $portfolio = Portfolio::with('sections')
            ->where('user_id', $user->id)
            ->first();

        return GlobalFunction::sendDataResponse(true, 'Success', [
            'portfolio' => $portfolio ? $this->formatPortfolio($portfolio) : null,
        ]);
    }

    /**
     * Add a section to the portfolio.
     */
    public function addSection(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'Account is frozen');
        }

        $portfolio = Portfolio::where('user_id', $user->id)->first();
        if (!$portfolio) {
            return GlobalFunction::sendSimpleResponse(false, 'Create a portfolio first');
        }

        $section = new PortfolioSection();
        $section->portfolio_id = $portfolio->id;
        $section->section_type = $request->section_type ?? 'text';
        $section->title = $request->title;
        $section->content = $request->content;
        $section->data = $request->data;
        $section->sort_order = $request->sort_order ?? PortfolioSection::where('portfolio_id', $portfolio->id)->max('sort_order') + 1;
        $section->is_visible = $request->is_visible ?? true;
        $section->save();

        $portfolio->load('sections');

        return GlobalFunction::sendDataResponse(true, 'Section added', [
            'portfolio' => $this->formatPortfolio($portfolio),
        ]);
    }

    /**
     * Update a section.
     */
    public function updateSection(Request $request)
    {
        $user = GlobalFunction::getAuthUser();

        $section = PortfolioSection::whereHas('portfolio', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->find($request->section_id);

        if (!$section) {
            return GlobalFunction::sendSimpleResponse(false, 'Section not found');
        }

        if ($request->has('section_type')) $section->section_type = $request->section_type;
        if ($request->has('title')) $section->title = $request->title;
        if ($request->has('content')) $section->content = $request->content;
        if ($request->has('data')) $section->data = $request->data;
        if ($request->has('sort_order')) $section->sort_order = $request->sort_order;
        if ($request->has('is_visible')) $section->is_visible = $request->is_visible;
        $section->save();

        $portfolio = Portfolio::with('sections')->where('user_id', $user->id)->first();

        return GlobalFunction::sendDataResponse(true, 'Section updated', [
            'portfolio' => $this->formatPortfolio($portfolio),
        ]);
    }

    /**
     * Remove a section.
     */
    public function removeSection(Request $request)
    {
        $user = GlobalFunction::getAuthUser();

        $section = PortfolioSection::whereHas('portfolio', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->find($request->section_id);

        if (!$section) {
            return GlobalFunction::sendSimpleResponse(false, 'Section not found');
        }

        $section->delete();

        $portfolio = Portfolio::with('sections')->where('user_id', $user->id)->first();

        return GlobalFunction::sendDataResponse(true, 'Section removed', [
            'portfolio' => $this->formatPortfolio($portfolio),
        ]);
    }

    /**
     * Reorder sections.
     */
    public function reorderSections(Request $request)
    {
        $user = GlobalFunction::getAuthUser();

        $portfolio = Portfolio::where('user_id', $user->id)->first();
        if (!$portfolio) {
            return GlobalFunction::sendSimpleResponse(false, 'Portfolio not found');
        }

        // Expect array of { id, sort_order }
        $orders = $request->orders ?? [];
        foreach ($orders as $item) {
            PortfolioSection::where('id', $item['id'])
                ->where('portfolio_id', $portfolio->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        $portfolio->load('sections');

        return GlobalFunction::sendDataResponse(true, 'Sections reordered', [
            'portfolio' => $this->formatPortfolio($portfolio),
        ]);
    }

    /**
     * Web route: render public portfolio page.
     */
    public function viewPortfolio(Request $request, $slug)
    {
        $portfolio = Portfolio::with(['sections', 'user.links'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$portfolio) {
            // Fall back: try as username for OG metadata (T11 behavior)
            $user = Users::where('username', $slug)->first();
            if ($user) {
                // Check if user has portfolio by user_id
                $userPortfolio = Portfolio::with(['sections', 'user.links'])
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->first();
                if ($userPortfolio) {
                    $portfolio = $userPortfolio;
                } else {
                    // No portfolio — serve T11's user OG metadata page
                    return app(ShareLinkController::class)->viewUser($request, $slug);
                }
            } else {
                abort(404, 'Portfolio not found');
            }
        }

        // Increment view count
        $portfolio->increment('view_count');

        $user = $portfolio->user;
        $setting = GlobalSettings::getCached();
        $appName = $setting->app_name ?? 'App';

        // Fetch featured posts
        $featuredPosts = [];
        if (!empty($portfolio->featured_post_ids)) {
            $featuredPosts = Posts::with('user', 'images')
                ->whereIn('id', $portfolio->featured_post_ids)
                ->get()
                ->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'description' => $post->description,
                        'thumbnail' => GlobalFunction::generateFileUrl($post->thumbnail ?? ''),
                        'post_type' => $post->post_type,
                    ];
                });
        }

        // Fetch user products if enabled
        $products = [];
        if ($portfolio->show_products) {
            $products = \App\Models\Product::where('seller_id', $user->id)
                ->where('status', 2) // approved
                ->orderBy('sold_count', 'desc')
                ->limit(6)
                ->get()
                ->map(function ($product) {
                    $images = is_string($product->images) ? json_decode($product->images, true) : ($product->images ?? []);
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price_coins' => $product->price_coins,
                        'image' => !empty($images) ? GlobalFunction::generateFileUrl($images[0]) : '',
                    ];
                });
        }

        $profilePhoto = GlobalFunction::generateFileUrl($user->profile_photo ?? '');
        $headline = $portfolio->headline ?? $user->fullname;
        $bio = $portfolio->bio_override ?? $user->bio;

        return view('portfolio', [
            'portfolio' => $portfolio,
            'user' => $user,
            'setting' => $setting,
            'appName' => $appName,
            'headline' => $headline,
            'bio' => $bio,
            'profilePhoto' => $profilePhoto,
            'featuredPosts' => $featuredPosts,
            'products' => $products,
            'sections' => $portfolio->sections->where('is_visible', true),
            'links' => $portfolio->show_links ? ($user->links ?? []) : [],
        ]);
    }

    private function formatPortfolio(Portfolio $portfolio): array
    {
        $user = $portfolio->user;
        return [
            'id' => $portfolio->id,
            'user_id' => $portfolio->user_id,
            'slug' => $portfolio->slug,
            'is_active' => $portfolio->is_active,
            'theme' => $portfolio->theme,
            'custom_colors' => $portfolio->custom_colors,
            'headline' => $portfolio->headline,
            'bio_override' => $portfolio->bio_override,
            'featured_post_ids' => $portfolio->featured_post_ids,
            'show_products' => $portfolio->show_products,
            'show_links' => $portfolio->show_links,
            'show_subscription_cta' => $portfolio->show_subscription_cta,
            'view_count' => $portfolio->view_count,
            'portfolio_url' => url("/u/{$portfolio->slug}"),
            'sections' => $portfolio->sections->map(function ($s) {
                return [
                    'id' => $s->id,
                    'section_type' => $s->section_type,
                    'title' => $s->title,
                    'content' => $s->content,
                    'data' => $s->data,
                    'sort_order' => $s->sort_order,
                    'is_visible' => $s->is_visible,
                ];
            })->values(),
            'created_at' => $portfolio->created_at,
            'updated_at' => $portfolio->updated_at,
        ];
    }
}
