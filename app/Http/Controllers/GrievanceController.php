<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Grievance;
use App\Models\GrievanceResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GrievanceController extends Controller
{
    /**
     * Submit a new grievance
     */
    public function submitGrievance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|in:content_removal,account_issue,privacy,harassment,data_request,other',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = GlobalFunction::getAuthUser();
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        $settings = GlobalSettings::getCached();
        $deadlineDays = $settings->grievance_deadline_days ?? 15;

        $grievance = new Grievance();
        $grievance->user_id = $user->id;
        $grievance->ticket_number = Grievance::generateTicketNumber();
        $grievance->category = $request->category;
        $grievance->subject = $request->subject;
        $grievance->description = $request->description;
        $grievance->status = 0; // received
        $grievance->priority = $request->category === 'harassment' ? 3 : 1;
        $grievance->deadline_at = Carbon::now()->addDays($deadlineDays);

        if ($request->hasFile('attachment')) {
            $grievance->attachment = GlobalFunction::uploadFile($request->file('attachment'), 'grievances');
        }

        $grievance->save();

        return GlobalFunction::sendDataResponse(true, 'Grievance submitted successfully. Your ticket number is: ' . $grievance->ticket_number, [
            'ticket_number' => $grievance->ticket_number,
            'id' => $grievance->id,
            'deadline' => $grievance->deadline_at,
        ]);
    }

    /**
     * Get user's grievances
     */
    public function myGrievances(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        $grievances = Grievance::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return GlobalFunction::sendDataResponse(true, 'Success', $grievances);
    }

    /**
     * Get single grievance with responses
     */
    public function getGrievance(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        $grievance = Grievance::with('responses')
            ->where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$grievance) {
            return GlobalFunction::sendSimpleResponse(false, 'Grievance not found');
        }

        return GlobalFunction::sendDataResponse(true, 'Success', $grievance);
    }

    /**
     * User adds a follow-up response
     */
    public function addResponse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'grievance_id' => 'required|exists:tbl_grievances,id',
            'message' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = GlobalFunction::getAuthUser();
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        $grievance = Grievance::where('id', $request->grievance_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$grievance) {
            return GlobalFunction::sendSimpleResponse(false, 'Grievance not found');
        }

        if (in_array($grievance->status, [3, 4])) {
            return GlobalFunction::sendSimpleResponse(false, 'This grievance has been closed');
        }

        $response = new GrievanceResponse();
        $response->grievance_id = $grievance->id;
        $response->responder_id = $user->id;
        $response->is_admin = false;
        $response->message = $request->message;
        $response->save();

        return GlobalFunction::sendSimpleResponse(true, 'Response added successfully');
    }

    /**
     * Get GRO contact information (public endpoint)
     */
    public function getGROInfo()
    {
        $settings = GlobalSettings::getCached();

        return GlobalFunction::sendDataResponse(true, 'Success', [
            'name' => $settings->gro_name ?? 'Grievance Officer',
            'email' => $settings->gro_email ?? '',
            'phone' => $settings->gro_phone ?? '',
            'address' => $settings->gro_address ?? '',
            'response_time' => ($settings->grievance_deadline_days ?? 15) . ' days',
        ]);
    }
}
