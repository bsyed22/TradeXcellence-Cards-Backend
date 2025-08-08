<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminCardCreated;
use App\Mail\UserCardCreated;
use App\Models\CardHolderLink;
use App\Models\Deposit;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ALT5Controller extends Controller
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function handleCallback(Request $request)
    {
        Log::info($request->all());

        // Validate input
        $validator = Validator::make($request->all(), [
            'ref_id' => 'required',
            'status' => 'required|string',
            'total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::info("Validation Failed");
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        Log::info("Validation successful");

        $refID = $request->input('ref_id');
        $status = $request->input('status');
        $amount = round($request->input('total'), 2);

        $tolerance = $amount * 0.05;
        $min = max(0, $amount - $tolerance);
        $max = $amount + $tolerance;

        if ($status !== "Paid") {
            Log::info("Callback received for a non-paid status.", $request->all());
            return response()->error("Payment status is not 'Paid'.", null, 400);
        }

        $deposit = Deposit::where('callback_id', $refID)
            ->where('status', 'pending')
            ->whereBetween('amount', [$min, $max])
            ->first();

        if (!$deposit) {
            Log::warning("Deposit not found or already processed for ref_id: {$refID}");
            return response()->error("Deposit not found or already processed.", null, 404);
        }

        $user = $deposit->user;
        if (!$user || !$user->card_holder_id) {
            return response()->error("Cardholder ID not found for the user.", null, 422);
        }

        $cardType = $deposit->card_type ?? optional(CardHolderLink::where("card_id", $deposit->card_id)->first())->type;

        $newCardId = $deposit->card_id;

        // Step 1: Create card if needed
        if ($deposit->card_id === null) {
            $createResponse = $this->settingsService->createCard($user->email, $deposit->alias, $user->card_holder_id, $cardType);
            $createData = $createResponse->getData();

            if (!$createData->success) {
                return response()->error("Failed to create card: " . ($createData->message ?? 'Unknown API error during card creation'), null, 500);
            }

            $cardData = $createData->data->raw;
            if (!isset($cardData->cardId)) {
                return response()->error("Card created but cardId missing in response.", null, 500);
            }

            $newCardId = $cardData->cardId;

            // Allow external system to settle card before querying
            sleep(60);
        }

        // Step 2: Get card details
        $cardDetailsResponse = $this->settingsService->getCardDetails($cardType, $newCardId);
        $cardDetails = $cardDetailsResponse->getData();

        if (!$cardDetails->success) {
            return response()->error("Failed to get card details: " . ($cardDetails->message ?? 'Unknown API error'), null, 500);
        }

        $cardDetailsData = $cardDetails->data;
        if (!isset($cardDetailsData->cardNumber)) {
            return response()->error("Card details missing card number.", null, 500);
        }

        // Step 3: Load card
        $loadResult = $this->settingsService->loadCard($deposit->amount, $user->card_holder_id, $newCardId, $cardType);

        $loadResponseData = $loadResult->getData();

        if (!$loadResponseData->success) {
            $innerMessage = $loadResponseData->data->body->errorMessage[0] ?? null;
            $errorMessage = $innerMessage ?: $loadResponseData->message ?? 'Unknown error during card loading';
            return response()->error("Failed to load card: " . $errorMessage, null, 500);
        }

        $apiData = $loadResponseData->data;
        if (!isset($apiData->errorCode) || $apiData->errorCode !== 0) {
            $errorDetails = !empty($apiData->errorMessage) ? json_encode($apiData->errorMessage) : 'Unknown API error';
            return response()->error("Failed to load card. API responded with: " . $errorDetails, null, 500);
        }

        // Step 4: Save to DB
        return DB::transaction(function () use ($request, $deposit, $user, $newCardId, $cardDetailsData) {
            $card = null;

            if ($deposit->card_id === null) {
                $card = CardHolderLink::create([
                    'user_id' => $user->id,
                    'card_holder_id' => $user->card_holder_id,
                    'card_id' => $newCardId,
                    'type' => $deposit->card_type,
                    'card_number' => substr($cardDetailsData->cardNumber, -4),
                    'card_holder_name' => $user->name,
                    'email' => $user->email,
                    'alias' => $deposit->alias,
                    'status' => "active",
                    'fee_paid' => 1

                ]);

            } else {
                // Load existing card details from DB if already assigned
                $card = CardHolderLink::where("card_id", $deposit->card_id)->first();
            }

            $deposit->update([
                'txn_hash' => $request->input('transaction_id'),
//                'fee' => $request->input('fee') ?? 0,
                'currency' => $request->input('currency'),
                'status' => 'Approved',
                'card_id' => $newCardId,
            ]);

            // Get all users with 'admin' role
            $admins = User::role('admin')->get();

            foreach ($admins as $admin) {
                if (!filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                try {
                    DB::table('notifications')->insert([
                        'id' => Str::uuid(),
                        'type' => 'manual',
                        'notifiable_type' => \App\Models\User::class,
                        'notifiable_id' => $admin->id,
                        'data' => json_encode([
                            'title' => 'New '.$card->type." card created",
                            'message' => $card->card_holder_name.' has created a new '.$card->type." card. The card ID is: ".$card->card_id,
                            'action_url' => '/deposits/123'
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Mail::to($admin->email)->send(new AdminCardCreated($card));
                } catch (\Exception $e) {
                    Log::error("Failed to notify admin: ".$e->getMessage());
                }
            }

            try {
                Mail::to($card->email)->send(new UserCardCreated($card));
            } catch (\Exception $e) {
                Log::error("Failed to send card creation email to user: ".$e->getMessage());
            }

            return response()->success("Deposit Successful", 200);
        });
    }
}
