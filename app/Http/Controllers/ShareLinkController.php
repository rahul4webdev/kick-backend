<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Posts;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShareLinkController extends Controller
{
    //

    public function encryptedId(Request $request)
    {
        $encryptedId = $request->encryptedId;
        $decoded = base64_decode($encryptedId);

        $result = null;
        $thumbUrl = null;
        $type = null;

        if (preg_match('/^post_(\d+)$/', $decoded, $matches)) {
            // Case: post
            $itemId = (int) $matches[1];
            $result = Posts::find($itemId);
            $type = 'Post';
            $title = $result->description;
            if($result->description == null){
                $title = 'Post By '.$result->user->fullname;
            }
            $thumbUrl = GlobalFunction::generateFileUrl($result->thumbnail);
            if ($result->post_type == Constants::postTypeImage) {
                $thumbUrl = GlobalFunction::generateFileUrl($result->images[0]->image);
            }
        } elseif (preg_match('/^reel_(\d+)$/', $decoded, $matches)) {
            // Case: drama
            $itemId = (int) $matches[1];
            $result = Posts::find($itemId);
            $type = 'Reel';
            $title = $result->description;
            if($result->description == null){
                $title = 'Post By '.$result->user->fullname;
            }
            $thumbUrl = GlobalFunction::generateFileUrl($result->thumbnail);
        } elseif (preg_match('/^user_(\d+)$/', $decoded, $matches)) {
            // Case: drama
            $itemId = (int) $matches[1];
            $result = Users::find($itemId);
            $type = 'User';
            $title = $result->fullname;
            $thumbUrl = GlobalFunction::generateFileUrl($result->profile_photo);
        } else {
            abort(404, 'Invalid ID format');
        }

        if (!$result) {
            abort(404, ucfirst($type) . ' not found');
        }

        $setting = GlobalSettings::getCached();

        return view('shareLinkPage', [
            'encryptedId' => $encryptedId,
            'decoded' => $decoded,
            'type' => $type,
            'data' => $result,
            'title' => $title,
            'setting' => $setting,
            'thumbUrl' => $thumbUrl,
        ]);
    }

    public function embedPost(Request $request, $postId)
    {
        $post = Posts::with('user')->find($postId);
        if (!$post) {
            abort(404, 'Post not found');
        }

        $setting = GlobalSettings::getCached();
        $thumbUrl = GlobalFunction::generateFileUrl($post->thumbnail);
        $videoUrl = GlobalFunction::generateFileUrl($post->post_video);

        if ($post->post_type == Constants::postTypeImage && $post->images && count($post->images) > 0) {
            $thumbUrl = GlobalFunction::generateFileUrl($post->images[0]->image);
        }

        $title = $post->description ?? 'Post by ' . ($post->user->fullname ?? 'User');
        $username = $post->user->username ?? '';
        $profilePhoto = GlobalFunction::generateFileUrl($post->user->profile_photo);
        $appName = $setting->app_name ?? 'App';

        return view('embed', [
            'post' => $post,
            'title' => $title,
            'username' => $username,
            'profilePhoto' => $profilePhoto,
            'thumbUrl' => $thumbUrl,
            'videoUrl' => $videoUrl,
            'appName' => $appName,
            'setting' => $setting,
        ]);
    }

    /**
     * Clean URL: /p/{postId} — Post/Reel view with full OG metadata
     */
    public function viewPost(Request $request, $postId)
    {
        $post = Posts::with('user', 'images')->find($postId);
        if (!$post) {
            abort(404, 'Post not found');
        }

        $setting = GlobalSettings::getCached();
        $appName = $setting->app_name ?? 'App';

        // Use custom OG fields if set, otherwise generate from post data
        $ogTitle = $post->og_title ?? Str::limit($post->description, 100) ?? 'Post by ' . ($post->user->fullname ?? 'User');
        $ogDescription = $post->og_description ?? $this->generateDescription($post, $appName);
        $ogImage = $post->og_image
            ? GlobalFunction::generateFileUrl($post->og_image)
            : $this->resolveThumbUrl($post);

        $videoUrl = null;
        $ogType = 'article';
        if (in_array($post->post_type, [Constants::postTypeVideo, Constants::postTypeReel])) {
            $videoUrl = GlobalFunction::generateFileUrl($post->post_video);
            $ogType = 'video.other';
        }

        $username = $post->user->username ?? '';
        $profilePhoto = GlobalFunction::generateFileUrl($post->user->profile_photo ?? '');

        // Generate base64 encoded ID for app deep link
        $shareKey = $post->post_type == Constants::postTypeReel ? 'reel' : 'post';
        $encryptedId = base64_encode("{$shareKey}_{$post->id}");

        return view('shareLinkPage', [
            'encryptedId' => $encryptedId,
            'decoded' => "{$shareKey}_{$post->id}",
            'type' => $post->post_type == Constants::postTypeReel ? 'Reel' : 'Post',
            'data' => $post,
            'title' => $ogTitle,
            'setting' => $setting,
            'thumbUrl' => $ogImage,
            'ogDescription' => $ogDescription,
            'ogType' => $ogType,
            'videoUrl' => $videoUrl,
            'username' => $username,
            'appName' => $appName,
            'canonicalUrl' => url("/p/{$post->id}"),
        ]);
    }

    /**
     * Clean URL: /r/{postId} — Alias for reel posts
     */
    public function viewReel(Request $request, $postId)
    {
        return $this->viewPost($request, $postId);
    }

    /**
     * Clean URL: /u/{username} — User profile with OG metadata
     */
    public function viewUser(Request $request, $username)
    {
        $user = Users::where('username', $username)->first();
        if (!$user) {
            abort(404, 'User not found');
        }

        $setting = GlobalSettings::getCached();
        $appName = $setting->app_name ?? 'App';

        $title = $user->fullname ?? '@' . $user->username;
        $description = $user->bio ?? "Check out @{$user->username} on {$appName}";
        $thumbUrl = GlobalFunction::generateFileUrl($user->profile_photo ?? '');

        $encryptedId = base64_encode("user_{$user->id}");

        return view('shareLinkPage', [
            'encryptedId' => $encryptedId,
            'decoded' => "user_{$user->id}",
            'type' => 'User',
            'data' => $user,
            'title' => $title,
            'setting' => $setting,
            'thumbUrl' => $thumbUrl,
            'ogDescription' => $description,
            'ogType' => 'profile',
            'videoUrl' => null,
            'username' => $user->username,
            'appName' => $appName,
            'canonicalUrl' => url("/u/{$user->username}"),
        ]);
    }

    /**
     * API: Generate/update OG metadata for a post
     */
    public function generateShareableCard(Request $request)
    {
        $userId = GlobalFunction::getAuthUser()->id;
        $postId = $request->post_id;

        $post = Posts::where('id', $postId)->where('user_id', $userId)->first();
        if (!$post) {
            return GlobalFunction::sendSimpleResponse(false, 'Post not found');
        }

        $setting = GlobalSettings::getCached();
        $appName = $setting->app_name ?? 'App';

        // Auto-generate OG metadata
        $post->og_title = Str::limit($post->description, 100) ?? 'Post by ' . ($post->user->fullname ?? 'User');
        $post->og_description = $this->generateDescription($post, $appName);
        $post->og_image = $post->thumbnail;
        $post->save();

        // Return the clean share URLs
        $shareKey = $post->post_type == Constants::postTypeReel ? 'r' : 'p';
        $cleanUrl = url("/{$shareKey}/{$post->id}");

        return GlobalFunction::sendDataResponse(true, 'Share card generated', [
            'clean_url' => $cleanUrl,
            'og_title' => $post->og_title,
            'og_description' => $post->og_description,
            'og_image' => GlobalFunction::generateFileUrl($post->og_image),
        ]);
    }

    // --- Helpers ---

    private function resolveThumbUrl(Posts $post): string
    {
        if ($post->post_type == Constants::postTypeImage && $post->images && count($post->images) > 0) {
            return GlobalFunction::generateFileUrl($post->images[0]->image);
        }
        return GlobalFunction::generateFileUrl($post->thumbnail ?? '');
    }

    private function generateDescription(Posts $post, string $appName): string
    {
        $username = $post->user->username ?? 'someone';
        $desc = $post->description ? Str::limit($post->description, 200) : '';

        $typeLabel = match ($post->post_type) {
            Constants::postTypeReel => 'reel',
            Constants::postTypeImage => 'photo',
            Constants::postTypeVideo => 'video',
            default => 'post',
        };

        if ($desc) {
            return "{$desc} — @{$username} on {$appName}";
        }
        return "Watch this {$typeLabel} by @{$username} on {$appName}";
    }
}
