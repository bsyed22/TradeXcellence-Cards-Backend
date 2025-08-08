<?php

namespace App\Http\Controllers\Api\PaymentMethods;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\NowPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NowPaymentsController extends Controller
{
    protected NowPayments $nowPayments;

    public function __construct(NowPayments $nowPayments)
    {
        $this->nowPayments = $nowPayments;
    }

    /*
    * Deposit Method
    */
    public function deposit(Request $request)
    {
        $validator = Validator::make($request->all(), [
//            'wallet_id' => 'required|integer',
            'price_amount' => 'required|integer',
            //'price_currency' => 'required',
            //'pay_currency' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation error' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {

            $currency ="USD";
            // Fetch payment method details
            $paymentMethod = PaymentMethod::where('name', 'NowPayments')->firstOrFail();
            $payment_method_settings = $paymentMethod->settings;
            $callback_url = $payment_method_settings["callback_url"];

            $transaction_fee = 0;
            $amount = $transaction_fee + $validated['price_amount'];

            // Calculate transaction fee (commented out in original)
            // if ($paymentMethod->deposit_charge_type === 'fixed') {
            //     $transaction_fee = $paymentMethod->deposit_fixed_charge;
            // } elseif ($paymentMethod->deposit_charge_type === 'percentage') {
            //     $transaction_fee = ($request->amount * ($paymentMethod->deposit_percent_charge / 100));
            // }

            // if ($paymentMethod->deposit_fee_payer === 'user') {
            //     $amount += $transaction_fee;
            // }


            // Initiating deposit transaction
            $response = $this->nowPayments->deposit([
                    'price_amount' => $amount,
                    'price_currency' => $currency,
                    'ipn_callback_url' => $callback_url,
                    'success_url' => $callback_url,
                    'cancel_url' => $callback_url,
                ]
            );

            $responseData = $response->getData(true);
            if (!$responseData["success"]) {
                DB::rollBack();
                return response()->error("Failed to initiate Deposit", $responseData['data']);
            }


            // Extract the transaction ID from response
            $transactionId = $responseData["data"]["id"] ?? null;
            if (!$transactionId) {
                DB::rollBack();
                return response()->error("Transaction ID not found");
            }

            $token = $request->bearerToken();
            $user = User::findByToken($token); // Save the admin user who took action
            if (!$user) {
                return response()->error("User not found", null, 404);
            }

            // Store deposit details in the database with status 'pending'
//            Deposit::create([
//                'wallet_id' => $validated['wallet_id'],
//                'user_id' => $user->id,
//                'order_id' => $transactionId,
//                'status' => 'pending',
//                'currency' => $currency,
//                'transaction_fee_payer' => $paymentMethod->deposit_fee_payer,
//                'charge_type' => $paymentMethod->deposit_charge_type,
//                'transaction_fee' => $transaction_fee,
//                'amount' => $amount ? $amount : 0,
//                'merchant' => 'NowPayments',
//            ]);

            DB::commit();

            return response()->success(["redirect_url" => $responseData["data"]["invoice_url"], "data" => $responseData["data"]], "Payment initiated successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error("Error processing payment: " . $e->getMessage());
        }
    }

    /*
     * Withdraw Method
     */
    public function requestNowPaymentsWithdrawal(Request $request)
    {
        $validator = Validator::make($request->all(), [
//            'wallet_id' => 'required|numeric|min:1',
            'amount' => 'required|numeric|min:0.01',
            'address' => 'required|string',
            'currency' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation Error' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

//        $wallet = Wallet::with('currency')->findOrFail($validated['wallet_id']);
//        if ($wallet->balance < $validated['amount']) {
//            return response()->error('Insufficient balance');
//        }

        $paymentMethod = PaymentMethod::where('name', 'NowPayments')->firstOrFail();

        $transaction_fee = match ($paymentMethod->withdraw_charge_type) {
            'fixed' => $paymentMethod->withdraw_fixed_charge,
            'percentage' => $validated['amount'] * ($paymentMethod->withdraw_percent_charge / 100),
            default => 0,
        };

        $finalAmount = ($paymentMethod->withdraw_fee_payer === 'user')
            ? $validated['amount'] + $transaction_fee
            : $validated['amount'];

        $token = $request->bearerToken();
        $user = User::findByToken($token); // Save the admin user who took action
        if (!$user) {
            return response()->error("User not found", null, 404);
        }

        // Save withdrawal request in DB
//        $withdrawal = Withdrawal::create([
//            'wallet_id' => $wallet->id,
//            'user_id' => $user->id,
//            'status' => 'pending',
//            'currency' => strtoupper($validated['currency']),
//            'amount' => $validated['amount'],
//            'merchant' => 'NowPayments',
//            'transaction_fee_payer' => $paymentMethod->withdraw_fee_payer,
//            'charge_type' => $paymentMethod->withdraw_charge_type,
//            'transaction_fee' => $transaction_fee,
//            'request_data' => json_encode([
//                'address' => $validated['address'],
//                'amount' => $validated['amount'],
//                'currency' => $validated['currency'],
//            ]),
//        ]);
//        $wallet->update([
//            "balance" => $wallet->balance - $finalAmount,
//        ]);

        return response()->success('Withdrawal request submitted. Awaiting processing.', [], 200);
    }


    /*
 * Get List of Available Currencies from Now Payment Portal
 */
    public function currencies()
    {
        try {
            $response = $this->nowPayments->currencies();

            $responseData = $response->getData(true);
            if (!$responseData["success"]) {
                DB::rollBack();
                return response()->error("Failed to get currencies list", $responseData['data']);
            }

            return response()->success($responseData["data"]["selectedCurrencies"], "Currencies List");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error("Error processing payment: " . $e->getMessage());
        }
    }

    /*
   * Generate JWT token
   */
    public function jwtToken()
    {
        try {
            $response = $this->nowPayments->generateJWTToken();
            if (!$response) {
                return response()->error("Failed to get JWT Token");
            }
            return response()->success($response, "JWT Token");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error("Error getting JWT Token: " . $e->getMessage());
        }
    }

    public function handleCallback(Request $request)
    {
        try {
            $payload = $request->all();

            // Extract main data
            $invoiceId = $payload['invoice_id'] ?? null;
            $paymentStatus = $payload['payment_status'] ?? null;
            $amountPaid = floatval($payload['actually_paid'] ?? 0);

            if (!$invoiceId || !$paymentStatus) {
                return response()->error("Invalid callback response");
            }

            // Check if it's a Deposit (Purchase) or a Withdrawal
            $record = Deposit::where('order_id', $invoiceId)->orWhere('purchase_id', $invoiceId)->first();
            $transactionType = 'deposit';

            if (!$record) {
                $record = Withdrawal::where('payout_batch_id', $invoiceId)->first();
                $transactionType = 'withdrawal';
            }

            if (!$record) {
                return response()->error("Transaction not found for invoice ID: $invoiceId");
            }

            // Map payment status
            $statusMap = [
                'waiting' => 'pending',
                'confirming' => 'processing',
                'confirmed' => 'paid',
                'sending' => 'processing',
                'partially_paid' => 'partially_paid',
                'finished' => 'completed',
                'refunded' => 'refunded',
                'failed' => 'failed',
                'expired' => 'expired'
            ];
            $statusName = $statusMap[$paymentStatus] ?? 'unknown';

            if ($statusName === 'unknown') {
                return response()->error("Invalid payment status received");
            }

            // Update record
            $record->status = $statusName;
            $record->transaction_details = json_encode($payload);
            $record->save();

            // Handle wallet balance update only when payment is successful
            if (in_array($statusName, ['paid', 'completed'])) {
                $wallet = Wallet::find($record->wallet_id);
                if (!$wallet) {
                    return response()->error('Wallet not found');
                }

                if ($amountPaid > 0) {
                    if ($transactionType === 'deposit') {
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

//                        $wallet->increment('balance', $record->amount);
                    } elseif ($transactionType === 'withdrawal') {
//                        $wallet->decrement('balance', $record->amount);
                    }
                } else {
                    return response()->error('Invalid transaction amount');
                }
            }

            return response()->success($payload, ucfirst($transactionType) . " status updated successfully");
        } catch (\Exception $e) {
            return response()->error("Failed to process callback", ['error' => $e->getMessage()]);
        }
    }

}
