<?php

namespace App\Traits;

use App\Models\KycVerificationLog;
use App\Models\SumsubKyc;
use App\Models\User;
use App\Services\SumsubService;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Monolog\Level;
use PHPUnit\Exception;

trait SumsubCallbackTrait
{

    protected function handleApplicantCreated(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];


        try {
            SumsubKyc::create([
                'user_id' => $externalUserId,
                'level_name' => $levelName,
                'status' => "Applicant Created",

            ]);


        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

        Log::info("Applicant Created: $applicantId");
    }

    protected function handleApplicantPending(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];

        Log::info("Applicant has uploaded the required documents and their status changed to pending: $applicantId");
    }

    protected function handleApplicantReviewed(array $payload)
    {

        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewAnswers = $payload["reviewResult"]["reviewAnswer"] === "RED"
            ? implode(", ", $payload["reviewResult"]["rejectLabels"])
            : "Review Approved";
        $status = $payload["reviewResult"]["reviewAnswer"] === "RED"
            ? "Verification Failed"
            : "Verification Approved";

        try {
            $sumsubkey = SumsubKyc::create([
                'user_id' => $externalUserId,
                'level_name' => $levelName,
                'status' => $status,
                'comment' => $reviewAnswers,

            ]);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }


        $recordId = $sumsubkey->id;

        $sumsubService = new SumsubService();
        $sumsubService->storeDocumentsLocally($applicantId, $recordId);

        Log::info("Applicant action verification has been completed.: $reviewAnswers");

    }

    protected function handleApplicantOnHold(array $payload)
    {

        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];
        $comment = $payload['reviewResult']['reviewAnswer'] ?? 'No reason provided';

        Log::info("Application On Hold: $applicantId, Reason: $comment");
    }

    protected function handleActionPending(array $payload)
    {

        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];


        Log::info("Applicant action status changed to pending.: $applicantId");
    }

    protected function handleApplicantActionReviewed(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewAnswers = $payload['reviewResult']['reviewAnswer'] ?? [];
        $reviewStatus = $payload['reviewStatus'];

        try {
            SumsubKyc::create([
                'user_id' => $externalUserId,
                'level_name' => $levelName,
                'status' => "Applicant Review Completed",
                'comment' => $reviewAnswers,

            ]);

        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }


        Log::info("Applicant action verification has been completed.: $applicantId");
    }

    protected function handleApplicantActionOnHold(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];

        Log::info("Applicant action verification has been paused.: $applicantId");
    }

    protected function handleApplicantPersonalInfoChanged(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];

        try {
            SumsubKyc::create([
                'user_id' => $externalUserId,
                'level_name' => $levelName,
                'status' => "Pending",
                'comment' => "Applicant has uploaded the required documents and their status changed to pending",
            ]);
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
        }


        Log::info("Applicant personal information has been changed, or the applicant is in the completed status and information in the applicant documents has also been changed: $applicantId");
    }

    protected function handleApplicantActivated(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];

        Log::info("Applicant profile has been set to active.: $applicantId");
    }

    protected function handleApplicantDeactivated(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];

        Log::info("Applicant profile has been set to deactivated.: $applicantId");
    }

    protected function handleApplicantDeleted(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];

        Log::info("Applicant has been permanently deleted.: $applicantId");
    }

    protected function handleApplicantReset(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];

        Log::info("Applicant has been permanently deleted.: $applicantId");
    }

    protected function handleApplicantLevelChanged(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];

        Log::info("Applicant level has been changed.: $applicantId");
    }

    protected function handleApplicantWorkflowCompleted(array $payload)
    {
        $applicantId = $payload['applicantId'];
        $levelName = $payload['levelName'];
        $externalUserId = $payload['externalUserId'];
        $createdAtMs = $payload['createdAtMs'];
        $reviewStatus = $payload['reviewStatus'];
        $comment = $payload['reviewResult']['reviewAnswer'] ?? 'No reason provided';
        $rejectLabels = $payload['reviewResult']['rejectLabels'] ?? 'No reason provided';
        Log::info("Workflow has been completed for an applicant.: $applicantId");
    }

}
