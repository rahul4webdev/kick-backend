<?php

namespace App\Http\Controllers;

use App\Models\AffiliateApplication;
use App\Models\GlobalFunction;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AffiliateApplicationController extends Controller
{
    /**
     * Submit affiliate application.
     * Auto-approves if user meets criteria (hybrid approach).
     */
    public function submitApplication(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        // Check existing
        $existing = AffiliateApplication::where('user_id', $user->id)
            ->whereIn('status', [AffiliateApplication::STATUS_PENDING, AffiliateApplication::STATUS_APPROVED])
            ->first();

        if ($existing) {
            $msg = $existing->isApproved()
                ? 'You are already an approved affiliate.'
                : 'You already have a pending application.';
            return response()->json(['status' => false, 'message' => $msg]);
        }

        $validator = Validator::make($request->all(), [
            'niche_category' => 'nullable|string|max:100',
            'social_links' => 'nullable|array',
            'bio' => 'nullable|string|max:2000',
            'content_examples' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Fetch user stats
        $followerCount = DB::table('tbl_followers')->where('to_user_id', $user->id)->count();
        $totalPosts = DB::table('tbl_post')->where('user_id', $user->id)->count();
        $totalViews = DB::table('tbl_post')->where('user_id', $user->id)->sum('views');

        // Check auto-approve criteria
        $settings = DB::table('tbl_settings')->first();
        $minFollowers = $settings->affiliate_auto_approve_min_followers ?? 1000;
        $minPosts = $settings->affiliate_auto_approve_min_posts ?? 10;
        $autoApprove = ($followerCount >= $minFollowers && $totalPosts >= $minPosts);

        $application = AffiliateApplication::create([
            'user_id' => $user->id,
            'follower_count' => $followerCount,
            'total_posts' => $totalPosts,
            'total_views' => $totalViews,
            'niche_category' => $request->niche_category,
            'social_links' => $request->social_links,
            'bio' => $request->bio,
            'content_examples' => $request->content_examples,
            'status' => $autoApprove ? AffiliateApplication::STATUS_APPROVED : AffiliateApplication::STATUS_PENDING,
            'auto_approved' => $autoApprove,
            'reviewed_at' => $autoApprove ? now() : null,
        ]);

        if ($autoApprove) {
            Users::where('id', $user->id)->update(['is_approved_affiliate' => true]);
        }

        $message = $autoApprove
            ? 'Congratulations! You have been auto-approved as an affiliate based on your profile.'
            : 'Application submitted. Our team will review and get back to you.';

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'application' => $application,
                'auto_approved' => $autoApprove,
            ],
        ]);
    }

    /**
     * Fetch user's affiliate application status.
     */
    public function fetchMyApplication(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $application = AffiliateApplication::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        return response()->json([
            'status' => true,
            'message' => $application ? 'Application fetched' : 'No application found',
            'data' => $application,
        ]);
    }

    // ═══════════════════════════════════════════
    //  ADMIN
    // ═══════════════════════════════════════════

    public function listApplications_Admin(Request $request)
    {
        $start = $request->input('start', 0);
        $length = $request->input('length', 20);
        $search = $request->input('search')['value'] ?? '';
        $statusFilter = $request->input('status_filter', null);

        $query = AffiliateApplication::with('user:id,username,fullname,profile_photo,is_verify');

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('niche_category', 'ILIKE', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('username', 'ILIKE', "%{$search}%")
                            ->orWhere('fullname', 'ILIKE', "%{$search}%");
                    });
            });
        }

        $total = AffiliateApplication::count();
        $filtered = $query->count();
        $applications = $query->orderByDesc('created_at')
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $applications,
        ]);
    }

    public function approveApplication(Request $request)
    {
        $application = AffiliateApplication::findOrFail($request->application_id);

        if ($application->status !== AffiliateApplication::STATUS_PENDING) {
            return response()->json(['status' => false, 'message' => 'Not in pending status.']);
        }

        DB::transaction(function () use ($application, $request) {
            $application->update([
                'status' => AffiliateApplication::STATUS_APPROVED,
                'admin_notes' => $request->admin_notes,
                'reviewed_by' => null,
                'reviewed_at' => now(),
            ]);

            Users::where('id', $application->user_id)->update(['is_approved_affiliate' => true]);
        });

        return response()->json(['status' => true, 'message' => 'Affiliate approved.']);
    }

    public function rejectApplication(Request $request)
    {
        $application = AffiliateApplication::findOrFail($request->application_id);

        if ($application->status !== AffiliateApplication::STATUS_PENDING) {
            return response()->json(['status' => false, 'message' => 'Not in pending status.']);
        }

        $application->update([
            'status' => AffiliateApplication::STATUS_REJECTED,
            'rejection_reason' => $request->rejection_reason,
            'reviewed_by' => null,
            'reviewed_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Affiliate application rejected.']);
    }

    public function affiliateApplicationsAdmin()
    {
        return view('affiliate_applications');
    }
}
