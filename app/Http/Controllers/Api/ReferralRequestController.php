<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralRequestController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'referral_program_id' => 'required|exists:referral_programs,id',
        ]);

        $user = User::find($validated['user_id']);

        if ($user->referral_code) {
            return response()->error("User is already a referral (IB).");
        }

        if ($user->referralRequest && $user->referralRequest->status === 'pending') {
            return response()->error("Referral request already submitted.");
        }

        $refRequest = ReferralRequest::create([
            'user_id' => $user->id,
            'referral_program_id' => $validated['referral_program_id'],
            'status' => 'pending',
        ]);

        return response()->success($refRequest, "Referral request submitted.");
    }

    public function list()
    {
        return response()->success(
            ReferralRequest::with(['user', 'reviewer', 'referralProgram'])->orderByDesc('id')->get()
        );
    }

    public function approve(Request $request, $id)
    {
        $validated = $request->validate([
            'reviewed_by' => 'required|exists:users,id',
            'admin_comment' => 'nullable|string',
        ]);

        $refReq = ReferralRequest::findOrFail($id);

        if ($refReq->status !== 'pending') {
            return response()->error("This request has already been processed.");
        }

        $user = $refReq->user;
        $referralCode = strtoupper(Str::random(8));

        $refReq->update([
            'status' => 'approved',
            'admin_comment' => $validated['admin_comment'] ?? null,
            'reviewed_by' => $validated['reviewed_by'],
            'reviewed_at' => now(),
        ]);

        $user->update([
            'referral_code' => $referralCode,
            'referral_program' => $refReq->referral_program_id,
            ]);

        return response()->success($refReq->fresh(), "Referral approved.");
    }

    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'reviewed_by' => 'required|exists:users,id',
            'admin_comment' => 'required|string',
        ]);

        $refReq = ReferralRequest::findOrFail($id);

        if ($refReq->status !== 'pending') {
            return response()->error("This request has already been processed.");
        }

        $refReq->update([
            'status' => 'rejected',
            'admin_comment' => $validated['admin_comment'],
            'reviewed_by' => $validated['reviewed_by'],
            'reviewed_at' => now(),
        ]);

        return response()->success($refReq->fresh(), "Referral request rejected.");
    }
}
