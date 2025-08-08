<?php

namespace App\Http\Controllers;

use App\Events\SumSubRejected;
use App\Events\SumSubVerified;
use App\Models\KycLevel;
use App\Models\KycVerificationLog;
use App\Models\User;
use App\Notifications\SumSubRejectedNotification;
use App\Notifications\SumSubVerifiedNotification;
use App\Notifications\UserRegisteredNotification;
use App\Services\SumsubService;
use App\Services\UserKycService;
use App\Traits\SumsubCallbackTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class SumSubController extends Controller
{
    use SumsubCallbackTrait;
    protected SumsubService $sumsubService;
    protected UserKycService $userKycService;

    public function __construct(SumsubService $sumsubService,UserKycService $userKycService)
    {
        $this->sumsubService = $sumsubService;
        $this->userKycService = $userKycService;
    }

    private function generateToken($external_id,$level_name)
    {
        // $externalUserId =$external_id;
        $externalUserId =28;
        $levelName = $level_name;
        return $this->sumsubService->getAccessToken($externalUserId, $levelName);
    }

    public function createApplicant(Request $request)
    {
        $validated = $request->validate([
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'levelName' => 'nullable|string',
            'externalUserId' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'country' => 'required|string',
            'placeOfBirth' => 'required|string',
            'formattedAddress' => 'nullable|string',
            'dob' => 'nullable|string',
        ]);

        try {
            $applicant = $this->sumsubService->createApplicant($validated);

            return response()->success($applicant, 'Applicant created successfully');
        } catch (\Exception $e) {
            return response()->error('Failed to create applicant', $e->getMessage(), 500);
        }
    }

    public function sumsubVerification()
    {
        $external_id=rand(1,150);
        $level_name="level1";
        $token = $this->generateToken($external_id,$level_name);
        $token = $token["token"];
//        $token = "_act-sbx-jwt-eyJhbGciOiJub25lIn0.eyJqdGkiOiJfYWN0LXNieC1mMWQzYzFmYy1kMWI5LTQ0ZTAtYWZjMS01YWIwYzU3YmZlNGQtdjIiLCJ1cmwiOiJodHRwczovL2FwaS5zdW1zdWIuY29tIn0.-v2";
        return view("sumsub", compact('token'));
    }

    //This function will check to see either update kyc log or create new kyc log
    public function findOrCreateKycLog(int $userId, int $kycLevelVerificationOrder, string $status, ?string $notes = null)
    {
        $level = KycLevel::where("verification_order", $kycLevelVerificationOrder)->first();

        if (!$level) {
            return response()->error('KYC Level not found', 404);
        }

        $log = KycVerificationLog::where('user_id', $userId)
            ->where('kyc_level_id', $level->id)
            ->latest()
            ->first();

        if ($log) {
            return $this->userKycService->updateKycLog($userId, $kycLevelVerificationOrder,$status, $notes);
        } else {
            return $this->userKycService->createKycLog($userId, $kycLevelVerificationOrder, $status, $notes);
        }
    }


    protected function getActiveKycLevelForUser(User $user): int
    {
        $log = KycVerificationLog::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'on_hold', 'reviewed', 'updated','rejected']) // Non-final statuses
            ->latest()
            ->first();

        if ($log) {
            return KycLevel::find($log->kyc_level_id)?->verification_order ?? ($user->kyc_level_number + 1);
        }

        // Default to next level if no active logs found
        return $user->kyc_level_number + 1;
    }

    /*
     * This function is used to handle all the webhooks call back from sumsub server
     */

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $event = $payload["type"] ?? null;
        $userId = $payload['externalUserId'] ?? null;
        $user = $userId ? User::where("sumsub_id",$userId)->first() : null;

        $currentLevel = $user->kyc_level_number ?? 0;
        // $nextLevel = $currentLevel + 1;
        $nextLevel = $this->getActiveKycLevelForUser($user);

        switch ($event) {
            case 'applicantCreated':
                $this->handleApplicantCreated($payload);
                if ($user) {
                    $hasLogs = KycVerificationLog::where('user_id', $user->id)->exists();
                    if ($hasLogs) {
                        $this->findOrCreateKycLog($user->id, $nextLevel, 'pending', 'Applicant Created');
                    }
                }
                break;

            case 'applicantPending':
                $this->handleApplicantPending($payload);
                if ($user) {
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'pending', 'Applicant documents uploaded and pending review.');
                }
                break;

//            case 'applicantReviewed':
//                $this->handleApplicantReviewed($payload);
//                if ($user) {
//                    $status = $payload["reviewResult"]["reviewAnswer"] === "RED" ? 'rejected' : 'approved';
//                    $comment = $status === 'rejected'
//                        ? implode(", ", $payload["reviewResult"]["rejectLabels"])
//                        : 'Applicant review approved.';
//                    $this->findOrCreateKycLog($user->id, $nextLevel, $status, $comment);
//
//                    //on sum sub create completion updating the user level of kyc
//                    $levelNumber = $user->kyc_level_number+1;
//                    $this->userKycService->updateLevel($user->id, $levelNumber);
//
//                }
//                break;

            case 'applicantReviewed':
                $this->handleApplicantReviewed($payload);
                if ($user) {
                    $status = $payload["reviewResult"]["reviewAnswer"] === "RED" ? 'rejected' : 'approved';
                    $comment = $status === 'rejected'
                        ? implode(", ", $payload["reviewResult"]["rejectLabels"])
                        : 'Applicant review approved.';

                    $this->findOrCreateKycLog($user->id, $nextLevel, $status, $comment);

                    //Only update level if approved
                    if ($status === 'approved') {
                        //SumSub Verification Event
                        event(new SumSubVerified($user));
                        // Notify all admins
                        $admins = User::where("email", "admin@crm.com")->get();
                        foreach ($admins as $admin) {
                            $admin->notify(new SumSubVerifiedNotification($user));
                        }

                        $levelNumber = $user->kyc_level_number + 1;
                        $this->userKycService->updateLevel($user->id, $levelNumber);
                    }else{
                        //SumSub Verification Event
                        event(new SumSubRejected($user));
                        // Notify all admins
                        $admins = User::where("email", "admin@crm.com")->get();
                        foreach ($admins as $admin) {
                            $admin->notify(new SumSubRejectedNotification($user));
                        }
                    }
                }
                break;

            case 'applicantOnHold':
                $this->handleApplicantOnHold($payload);
                if ($user) {
                    $comment = $payload['reviewResult']['reviewAnswer'] ?? 'No reason provided';
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'on_hold', $comment);
                }
                break;

            case 'applicantActionPending':
                $this->handleActionPending($payload);
                if ($user) {
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'pending', 'Action required by applicant.');
                }
                break;

            case 'applicantActionReviewed':
                $this->handleApplicantActionReviewed($payload);
                if ($user) {
                    $comment = $payload['reviewResult']['reviewAnswer'] ?? 'Action reviewed.';
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'reviewed', $comment);
                }
                break;

            case 'applicantActionOnHold':
                $this->handleApplicantActionOnHold($payload);
                if ($user) {
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'on_hold', 'Applicant action put on hold.');
                }
                break;

            case 'applicantPersonalInfoChanged':
                $this->handleApplicantPersonalInfoChanged($payload);
                if ($user) {
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'updated', 'Applicant updated personal information.');
                }
                break;

            case 'applicantActivated':
                $this->handleApplicantActivated($payload);
                if ($user) {
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'activated', 'Applicant profile activated.');
                }
                break;

            case 'applicantDeactivated':
                $this->handleApplicantDeactivated($payload);
                if ($user) {
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'deactivated', 'Applicant profile deactivated.');
                }
                break;

            case 'applicantDeleted':
                $this->handleApplicantDeleted($payload);
                if ($user) {
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'deleted', 'Applicant deleted.');
                }
                break;

            case 'applicantReset':
                $this->handleApplicantReset($payload);
                if ($user) {
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'reset', 'Applicant reset their verification.');
                }
                break;

            case 'applicantLevelChanged':
                $this->handleApplicantLevelChanged($payload);
                if ($user) {
                    $this->findOrCreateKycLog($user->id, $nextLevel, 'level_changed', 'Applicant level changed.');
                }
                break;

            case 'applicantWorkflowCompleted':
                $this->handleApplicantWorkflowCompleted($payload);
                if ($user) {
                    $status = $payload["reviewResult"]["reviewAnswer"] ?? 'completed';
                    $comment = $payload["reviewResult"]["rejectLabels"] ?? 'Workflow completed.';
                    $this->findOrCreateKycLog(
                        $user->id,
                        $nextLevel,
                        "approved",
                        is_array($comment) ? implode(', ', $comment) : $comment
                    );
                }
                break;

            default:
                Log::warning('Unhandled Sumsub Event', [
                    'event' => $event,
                    'payload' => $payload
                ]);
        }

        return response()->json(['status' => 'ok']);
    }


    /*
     * This function is used to get the token for initiating a token request for sumsub web sdk
     */
    public function getSumsumToken(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'level_name' => 'required|string',
        ]);

        $user = User::find($request->user_id);

        if (is_null($user->sumsub_id)) {
            $externalUserId = time(). $user->id;
            $user->sumsub_id = $externalUserId;
            $user->save();
        }

        $accessToken = $this->sumsubService->getAccessToken($user->sumsub_id, $request->level_name);


        return response()->success(["token"=>$accessToken["token"]],"Sum sub token retrieved Successfully", 200);
    }


}
