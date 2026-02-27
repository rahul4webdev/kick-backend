<?php

namespace App\Http\Controllers;

use App\Models\Appeal;
use App\Models\GlobalFunction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppealController extends Controller
{
    /**
     * Submit an appeal against a moderation decision
     */
    public function submitAppeal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'appeal_type' => 'required|string|in:post_removal,account_ban,account_freeze,violation',
            'reason' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = GlobalFunction::getAuthUser();
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        // Check if user already has a pending appeal for the same reference
        $existingAppeal = Appeal::where('user_id', $user->id)
            ->where('appeal_type', $request->appeal_type)
            ->where('reference_id', $request->reference_id)
            ->whereIn('status', [0, 1])
            ->first();

        if ($existingAppeal) {
            return GlobalFunction::sendSimpleResponse(false, 'You already have a pending appeal for this action');
        }

        $appeal = new Appeal();
        $appeal->user_id = $user->id;
        $appeal->appeal_type = $request->appeal_type;
        $appeal->reference_id = $request->reference_id;
        $appeal->reason = $request->reason;
        $appeal->additional_context = $request->additional_context;
        $appeal->status = 0; // pending
        $appeal->save();

        return GlobalFunction::sendDataResponse(true, 'Appeal submitted successfully. We will review your appeal and get back to you.', [
            'id' => $appeal->id,
        ]);
    }

    /**
     * Get user's appeals
     */
    public function myAppeals(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        $appeals = Appeal::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return GlobalFunction::sendDataResponse(true, 'Success', $appeals);
    }

    /**
     * Get single appeal details
     */
    public function getAppeal(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        $appeal = Appeal::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$appeal) {
            return GlobalFunction::sendSimpleResponse(false, 'Appeal not found');
        }

        return GlobalFunction::sendDataResponse(true, 'Success', $appeal);
    }
}
