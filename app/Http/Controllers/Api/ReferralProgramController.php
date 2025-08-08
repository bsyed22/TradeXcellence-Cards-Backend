<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferralProgram;
use App\Models\ReferralBonusClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReferralProgramController extends Controller
{
    public function index()
    {
        $programs = ReferralProgram::latest()->get();
        return response()->success($programs, "Referral programs fetched successfully");
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'program_name' => 'required|string',
            'program_type' => 'required|in:public,private',
            'bonus_type' => 'required|in:percentage,fixed',
            'bonus_amount' => 'required|numeric|min:0',
            'bonus_validity_type' => 'required|in:expires_on,max_claims',
            'expires_at' => 'nullable|date|required_if:bonus_validity_type,expires_on',
            'max_claims' => 'nullable|integer|required_if:bonus_validity_type,max_claims',
        ]);

        if ($validator->fails()) {
            return response()->error("Validation failed", $validator->errors(), 422);
        }

        $program = ReferralProgram::create($validator->validated());

        return response()->success($program, "Referral program created successfully", 201);
    }

    public function show($id)
    {
        $program = ReferralProgram::find($id);
        if (!$program) {
            return response()->error("Referral program not found", null, 404);
        }

        return response()->success($program, "Referral program details");
    }

    public function update(Request $request, $id)
    {
        $program = ReferralProgram::find($id);
        if (!$program) {
            return response()->error("Referral program not found", null, 404);
        }

        $validator = Validator::make($request->all(), [
            'program_name' => 'sometimes|string',
            'program_type' => 'sometimes|in:public,private',
            'bonus_type' => 'sometimes|in:percentage,fixed',
            'bonus_amount' => 'sometimes|numeric|min:0',
            'bonus_validity_type' => 'sometimes|in:expires_on,max_claims',
            'expires_at' => 'nullable|date|required_if:bonus_validity_type,expires_on',
            'max_claims' => 'nullable|integer|required_if:bonus_validity_type,max_claims',
        ]);

        if ($validator->fails()) {
            return response()->error("Validation failed", $validator->errors(), 422);
        }

        $program->update($validator->validated());

        return response()->success($program, "Referral program updated successfully");
    }

    public function destroy($id)
    {
        $program = ReferralProgram::find($id);
        if (!$program) {
            return response()->error("Referral program not found", null, 404);
        }

        $program->delete();
        return response()->success(null, "Referral program deleted");
    }
}
