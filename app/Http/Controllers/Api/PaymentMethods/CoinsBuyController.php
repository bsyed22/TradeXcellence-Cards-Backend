<?php

namespace App\Http\Controllers\Api\PaymentMethods;

use App\Enum\CoinsBuyStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\CoinsBuy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoinsBuyController extends Controller
{
    protected CoinsBuy $coinsbuyService;

    public function __construct(CoinsBuy $coinsbuyService)
    {
        $this->coinsbuyService = $coinsbuyService;
    }

    /*
     * Deposit Method
     */
    public function deposit(Request $request)
    {

        $request->validate([
            'amount' => 'required'
        ]);

        DB::beginTransaction();

        try {
//            $wallet = Wallet::with("currency")->findOrFail($request->wallet_id);
            $currency = "USD";
            $amount = $request->amount;

            // Fetch payment method details
            $paymentMethod = PaymentMethod::where('name', 'Coinsbuy')->firstOrFail();
            $payment_method_settings = $paymentMethod->settings;
            $redirect_url = $payment_method_settings["deposit_callback_url"];
            $payment_page_redirect_url = $payment_method_settings["payment_page_redirect_url"];
            $payment_page_button_text = $payment_method_settings["payment_page_button_text"];
            $coinsbuy_wallet_id = $payment_method_settings["coinsbuy_wallet_id"];

            $transaction_fee = 0;

            // Calculate transaction fee
            if ($paymentMethod->deposit_charge_type === 'fixed') {
                $transaction_fee = $paymentMethod->deposit_fixed_charge;
            } elseif ($paymentMethod->deposit_charge_type === 'percentage') {
                $transaction_fee = ($request->amount * ($paymentMethod->deposit_percent_charge / 100));
            }

            if ($paymentMethod->deposit_fee_payer === 'user') {
                $amount += $transaction_fee;
            }

            // Initiating deposit transaction
            $response = $this->coinsbuyService->deposit([
                    'data' => [
                        'type' => 'deposit',
                        'attributes' => [
                            'label' => 'VortexFxAppollon',
                            'tracking_id' => 'Tracking-' . time(),
                            'status' => 2,
                            'confirmations_needed' => 2,
                            'callback_url' => $redirect_url,
                            'payment_page_redirect_url' => $payment_page_redirect_url,
                            "time_limit" => 60,
                            'payment_page_button_text' => $payment_page_button_text,
                            "target_amount_requested" => $request->amount,
                        ],
                        'relationships' => [
                            "currency" => [
                                "data" => [
                                    "type" => "currency",
                                    "id" => $request->currency,
                                ]
                            ],
                            'wallet' => [
                                'data' => [
                                    'type' => 'wallet',
                                    'id' => $coinsbuy_wallet_id,
                                ],

                            ],
                        ],
                    ],
                ]
            );

            $responseData = $response->getData(true);
            if (!$responseData["success"]) {
                DB::rollBack();
                return response()->error("Failed to initiate deposit", $responseData['data']);
            }


            // Extract the transaction ID from response
            $transactionId = $responseData["data"]["data"]["id"] ?? null;
            if (!$transactionId) {
                DB::rollBack();
                return response()->error("Transaction ID not found");
            }
            $token = $request->bearerToken();
            $user = User::findByToken($token);; // Save the admin user who took action
            if (!$user) {
                return response()->error("User not found", null, 404);
            }

            $deposit = Deposit::find($request->deposit_id);

            if (!$deposit) {
                return response()->error('Deposit not found.');
            }

            // Store deposit details in the database with status 'pending'
            $deposit->update([
                'wallet_id' => $request->wallet_id,
                'order_id' => $transactionId,
                'status' => 'pending',
                'currency' => $currency,
                'transaction_fee_payer' => $paymentMethod->deposit_fee_payer,
                'charge_type' => $paymentMethod->deposit_charge_type,
                'transaction_fee' => $transaction_fee,
                'amount' => $amount ? $amount : 0,
                'merchant' => 'Coinsbuy',
            ]);

                DB::commit();

            return response()->success(["redirect_url" => $responseData["data"]["data"]["attributes"]["payment_page"], "data" => $responseData["data"]["data"]], "Deposit initiated successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error("Error processing payment: " . $e->getMessage());
        }
    }

    /*
     * Withdraw Method
     */
    public function requestCoinsbuyWithdrawal(Request $request)
    {
        $request->validate([
            'withdrawal_address' => 'required',
            'amount' => 'required|numeric',
            'currency' => 'required|numeric',
            'beneficiary_first_name' => 'required|string',
            'beneficiary_last_name' => 'required|string'
        ]);

        $user = User::findByToken($request->bearerToken());

        if (!$user) {
            return response()->error('Unauthorized or user not found.', null, 401);
        }

//        $wallet = Wallet::with('currency')->findOrFail($request->wallet_id);
//        if ($wallet->balance < $request->amount) {
//            return response()->error('Insufficient balance');
//        }

        $paymentMethod = PaymentMethod::where('name', 'Coinsbuy')->firstOrFail();
        $settings = $paymentMethod->settings;
        $coinsbuy_wallet_id = $settings["coinsbuy_wallet_id"];

        $payoutChargesResponse = $this->coinsbuyService->payoutCharges([
            'data' => [
                'type' => 'payout-calculation',
                'attributes' => [
                    'amount' => $request->amount,
                    'to_address' => $request->withdrawal_address,
                ],
                'relationships' => [
                    'wallet' => ['data' => ['type' => 'wallet', 'id' => $coinsbuy_wallet_id]],
                    'currency' => ['data' => ['type' => 'currency', 'id' => $request->currency]],
                ],
            ]
        ]);

        $payoutChargesData = $payoutChargesResponse->getData(true);

        if (!$payoutChargesData['success']) {
            return response()->error("Failed to calculate payout charges", $payoutChargesData['data']);
        }

        $lowFee = (float)$payoutChargesData['data']['data']['attributes']['fee']['low'];
        $finalAmount = $request->amount + $lowFee;

        DB::beginTransaction();
        try {

            $withdrawal = Withdrawal::find($request->withdrawal_id);

            if (!$withdrawal) {
                return response()->error('Withdrawal not found.');
            }

            $withdrawal = $withdrawal->update([
                'user_id' => $user->id,
                'status' => 'pending',
                'currency' => "USD",
                'transaction_fee_payer' => $paymentMethod->withdraw_fee_payer,
                'charge_type' => $paymentMethod->withdraw_charge_type,
                'transaction_fee' => $lowFee,
                'amount' => $finalAmount,
                'merchant' => 'Coinsbuy',
                'request_data' => json_encode([
                    'withdrawal_address' => $request->withdrawal_address,
                    'amount' => $request->amount,
                    'currency' => $request->currency,
                    'beneficiary_first_name' => $request->beneficiary_first_name,
                    'beneficiary_last_name' => $request->beneficiary_last_name,
                    'user_country' => $user->country,
                    'user_address' => $user->address,
                ])
            ]);
//            $wallet->update([
//                "balance"=>$wallet->balance - $finalAmount,
//            ]);
            DB::commit();
            return response()->success($withdrawal, "Withdrawal request submitted");
            return response()->success(null, "Withdrawal request submitted");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error("Failed to create withdrawal request", $e->getMessage());
        }
    }


    public function handleCallback(Request $request)
    {
        Log::info($request->all());

        try {
            $payload = $request->all();

            // Extract main data
            $data = $payload['data'] ?? null;
            $attributes = $data['attributes'] ?? null;
            $type = $data['type'] ?? null;

            if (!$data || !$attributes || !$type) {
                return response()->error("Invalid callback response");
            }

            // Extract required values
            $transactionGuid = $data['id'] ?? null;
            $statusCode = $attributes['status'] ?? null;

            if (!$transactionGuid) {
                return response()->error("Transaction GUID is missing");
            }

            // Determine model based on type
            if ($type === 'deposit') {
                $record = Deposit::where('order_id', $transactionGuid)->first();
            } elseif ($type === 'payout') {
                $record = Withdrawal::where('payout_batch_id', $transactionGuid)->first();
            } else {
                return response()->error("Invalid transaction type");
            }

            if (!$record) {
                return response()->error(ucfirst($type) . " not found for transaction: $transactionGuid");
            }

            // Convert status code to status name
            $statusName = $statusCode !== null ? CoinsBuyStatusEnum::getStatusName((int)$statusCode) : null;

            if (!$statusName) {
                return response()->error("Invalid status code received");
            }

            // Update record status and transaction details
            $record->status = $statusName;
            $record->transaction_details = json_encode($request->all());
            $record->save();

            // Handle wallet balance update if transaction is paid
            if ($statusName === 'paid') {
//                $wallet = Wallet::find($record->wallet_id);
//                if (!$wallet) {
//                    return response()->error('Wallet not found');
//                }

                $amount = floatval($record->amount);
                if ($amount > 0) {
                    if ($type === 'deposit') {

                        //getting user by id from deposit
                        $user = User::find($record->user_id);

                        if (!$user) {
                            return response()->error('User not found');
                        }

                        //Email Verified Event
//                        event(new DepositSuccessful($user));

                        //notifications to admin
//                        $admins = User::role('admin')->get();
//                        foreach ($admins as $admin) {
//                            $admin->notify(new DepositSuccessfulNotification($user));
//                        }

                        //notify to admin
//                        $user->notify(new DepositSuccessfulNotification($user));

//                        $wallet->increment('balance', $amount);
                    } elseif ($type === 'payout') {
//                        $wallet->decrement('balance', $amount);
                    }
                } else {
                    return response()->error('Invalid transaction amount');
                }
            }

            return response()->success($payload, ucfirst($type) . " status updated successfully");
        } catch (\Exception $e) {
            return response()->error("Failed to process callback", ['error' => $e->getMessage()]);
        }
    }

    public function payoutCharges(Request $request)
    {
        $request->validate([
            'amount' => 'required',
            'to_address' => 'required',
            'coinbuy_wallet_id' => 'required',
            'currency' => 'required'
        ]);

        // Initiating deposit transaction
        $response = $this->coinsbuyService->payoutCharges([
                'data' => [
                    'type' => 'payout-calculation',
                    'attributes' => [
                        'amount' => $request->amount,
                        'to_address' => $request->to_address,
                    ],
                    'relationships' => [
                        'wallet' => [
                            'data' => [
                                'type' => 'wallet',
                                'id' => $request->coinbuy_wallet_id,
                            ],

                        ],
                        "currency" => [
                            "data" => [
                                "type" => "currency",
                                "id" => $request->currency,
                            ]
                        ]
                    ],
                ],
            ]
        );

        $responseData = $response->getData(true);
        if (!$responseData["success"]) {
            DB::rollBack();
            return response()->error("Failed to initiate payment", $responseData['data']);
        }

        return response()->success($responseData["data"]["data"], "Payment initiated successfully");

    }


    public function reviewCoinsbuyWithdrawal(Request $request, CoinsBuy $coinsBuyService)
    {
        $request->validate([
            'id' => 'required|exists:withdrawals,id',
            'action' => 'required|in:approve,reject',
            'comment' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $withdrawal = Withdrawal::findOrFail($request->id);

            if ($withdrawal->status !== 'pending') {
                return response()->error('Withdrawal is not pending');
            }

            if ($request->action === 'reject') {
                $withdrawal->update([
                    'status' => 'rejected',
                    'comments' => $request->comment,
                ]);
//                $wallet = Wallet::find($withdrawal->wallet_id);
//                if ($wallet) {
//                    $wallet->update([
//                        'balance' => $wallet->balance + $withdrawal->amount,
//                    ]);
//                }
                DB::commit();
                return response()->success($withdrawal, 'Withdrawal rejected');
            }

//            $wallet = Wallet::with('currency')->findOrFail($withdrawal->wallet_id);
//            if ($wallet->balance < $withdrawal->amount) {
//                return response()->error('Insufficient wallet balance');
//            }

            $paymentMethod = PaymentMethod::where('name', 'Coinsbuy')->firstOrFail();
            $settings = $paymentMethod->settings;
            $coinsbuy_wallet_id = $settings["coinsbuy_wallet_id"];
            $callback_url = $settings["withdraw_callback_url"];

            $data = json_decode($withdrawal->request_data, true);

            $payoutResponse = $coinsBuyService->withdraw([
                'data' => [
                    'type' => 'payout',
                    'attributes' => [
                        'label' => 'My Payout',
                        'amount' => $data['amount'],
                        'fee_amount' => $withdrawal->amount,
                        'address' => $data['withdrawal_address'],
                        'tracking_id' => 'Tracking-' . time(),
                        'confirmations_needed' => 2,
                        'callback_url' => $callback_url,
                        'travel_rule_info' => [
                            'beneficiary' => [
                                'beneficiaryPersons' => [[
                                    'naturalPerson' => [
                                        'name' => [[
                                            'nameIdentifier' => [[
                                                'primaryIdentifier' => $data['beneficiary_first_name'],
                                                'secondaryIdentifier' => $data['beneficiary_last_name']
                                            ]]
                                        ]],
                                        'geographicAddress' => [[
                                            'country' => $data['user_country'],
                                            'addressLine' => $data['user_address'],
                                            'addressType' => 'HOME'
                                        ]]
                                    ]
                                ]]
                            ]
                        ]
                    ],
                    'relationships' => [
                        'wallet' => ['data' => ['type' => 'wallet', 'id' => $coinsbuy_wallet_id]],
                        'currency' => ['data' => ['type' => 'currency', 'id' => $data['currency']]],
                    ]
                ]
            ]);

            $payoutResponse = $payoutResponse->getData(true);

            if (!$payoutResponse['success']) {
                DB::rollBack();
                return response()->error('Coinsbuy payout failed', $payoutResponse["data"]);
            }

//            $wallet->decrement('balance', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'pending',
//                'auto_approve' => $paymentMethod->is_automatic,
                'payout_batch_id' => $payoutResponse["data"]["id"] ?? null,
                'comments' => $request->comments,
            ]);

            DB::commit();
            return response()->success($withdrawal, 'Withdrawal approved and processed');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error('Failed to process withdrawal', $e->getMessage());
        }
    }


}
