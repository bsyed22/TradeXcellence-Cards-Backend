<?php

namespace App\Http\Controllers\Api\PaymentMethods;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\APSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class APSController extends Controller
{
    protected APSService $apsService;

    public function __construct(APSService $apsService)
    {
        $this->apsService = $apsService;
    }

    /*
     * This method is used to initiate deposit
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric'
        ]);

        $bearerToken = $request->bearerToken();
        $user = User::findByToken($bearerToken);;

        if (!$user) {
            return response()->error('Unauthorized or user not found.', null, 401);
        }

        DB::beginTransaction();

        try {
            $currency = "USD";
            $amount = $request->amount;

            // Fetch payment method details
            $paymentMethod = PaymentMethod::where('name', 'APS')->firstOrFail();
            $payment_method_settings = $paymentMethod->settings;
            $redirect_url = $payment_method_settings["redirect_url"];
            $status_url = $payment_method_settings["deposit_callback_url"];

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
            $response = $this->apsService->initiatePayment(
                $amount,
                [
                    "redirect_url" => $redirect_url,
                    "status_callback_url" => $status_url,
                    "external_id" => "VortexFX-Appollon-" . time(),
                    "payer_id" => $user->email,
                    "customer_ip_address" => $request->ip(),
                    "from_country" => $user->country,
                    "from_email" => $user->email,
                ]
            );
            $responseData = $response->getData(true);

            if (!$responseData["success"]) {
                DB::rollBack();
                return response()->error("Failed to initiate payment");
            }

            // Extract the transaction ID from response
            $transactionId = $responseData["data"]["id"] ?? null;

            if (!$transactionId) {
                DB::rollBack();
                return response()->error("Transaction ID not found");
            }
            $token = $request->bearerToken();
            $user =User::findByToken($token);; // Save the admin user who took action
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
                'user_id' => $user->id,
                'order_id' => $transactionId,
                'status' => 'pending',
                'currency' => $currency,
                'transaction_fee_payer' => $paymentMethod->deposit_fee_payer,
                'charge_type' => $paymentMethod->deposit_charge_type,
                'transaction_fee' => $transaction_fee,
                'amount' => $amount,
                'merchant' => 'APS',
            ]);

            DB::commit();

            return response()->success(["redirect_url" => $responseData["data"]["how"], "data" => $responseData], "Payment initiated successfully");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error("Error processing payment: " . $e->getMessage());
        }
    }

    /*
     * Withdrawl
     */
    public function requestWithdrawal(Request $request)
    {

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'wallet_id' => 'required|exists:wallets,id',
            'from_country' => 'required|string',
            'to_bank_card' => 'required|string',
            'to_bank_card_exp' => 'required|string',
            'to_first_name' => 'required|string',
            'to_last_name' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
//            $wallet = Wallet::with('currency')->findOrFail($request->wallet_id);
//
//            if ($wallet->balance < $request->amount) {
//                return response()->error('Insufficient balance');
//            }

            $paymentMethod = PaymentMethod::where('name', 'APS')->firstOrFail();

            $transaction_fee = match ($paymentMethod->withdraw_charge_type) {
                'fixed' => $paymentMethod->withdraw_fixed_charge,
                'percentage' => $request->amount * ($paymentMethod->withdraw_percent_charge / 100),
                default => 0,
            };

            $finalAmount = ($paymentMethod->withdraw_fee_payer === 'user')
                ? $request->amount + $transaction_fee
                : $request->amount;

            $token = $request->bearerToken();
            $user = User::findByToken($token);;
            if (!$user) {
                return response()->error("User not found", null, 404);
            }

            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'currency' => "USD",
                'transaction_fee_payer' => $paymentMethod->withdraw_fee_payer,
                'charge_type' => $paymentMethod->withdraw_charge_type,
                'transaction_fee' => $transaction_fee,
                'amount' => $finalAmount,
                'merchant' => 'APS',
                'request_data' => json_encode([
                    'from_country' => $request->from_country,
                    'to_bank_card' => $request->to_bank_card,
                    'to_bank_card_exp' => $request->to_bank_card_exp,
                    'to_first_name' => $request->to_first_name,
                    'to_last_name' => $request->to_last_name,
                    'amount' => $request->amount,
                ]),
            ]);
//            $wallet->update([
//                "balance"=>$wallet->balance - $finalAmount,
//            ]);


            DB::commit();
            return response()->success($withdrawal, 'Withdrawal request submitted');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error('Something went wrong', $e->getMessage());
        }
    }

    /*
     * Handling call back function from the APS deposit success call back
     */
    public function depositCallBack(Request $request)
    {
        try {
            // Extract parameters from the callback URL
            $status = $request->input('status'); // e.g., 'success'
            $transactionGuid = $request->input('transaction_guid'); // e.g., 'f25a3307-49aa-424d-8f56-ea409192636e'

            if (!$transactionGuid) {
                return response()->error("Transaction GUID is missing");
            }

            // Find the deposit record by transaction_guid (assuming it's stored in 'order_id' column)
            $deposit = Deposit::where('order_id', $transactionGuid)->first();

            if (!$deposit) {
                return response()->error("Deposit not found for transaction: $transactionGuid");
            }

            // Update status if transaction was successful
            if ($status === 'success') {
                $deposit->status = 'completed';
                $deposit->save();

//                $wallet = Wallet::find($deposit->wallet_id);
//
//                if (!$wallet) {
//                    return response()->error('Wallet not found');
//                }
//
//                $wallet->increment('balance', $deposit->amount);

                //getting user by id from deposit
                $user = User::find($deposit->user_id);

                if(!$user)
                {
                    return response()->error('User not found');
                }

                //Email Verified Event
//                event(new DepositSuccessful($user));

                //notifications to admin
//                $admins = User::role('admin')->get();
//                foreach ($admins as $admin) {
//                    $admin->notify(new DepositSuccessfulNotification($user));
//                }

                //notify to admin
//                $user->notify(new DepositSuccessfulNotification($user));

                return response()->success($request->all(), "Deposit status updated successfully");
            }

            return response()->error("Transaction status is not successful");
        } catch (\Exception $e) {
            return response()->error("Failed to process callback", $e->getMessage());
        }
    }

    public function payoutCallBack(Request $request)
    {

        try {
            // Extract parameters from request body (not query params)
            $status = $request->input('status'); // Corrected
            $transactionGuid = $request->input('transaction_id'); // Corrected

            if (!$transactionGuid) {
                return response()->error("Transaction ID is missing");
            }

            // Find the withdrawal record using the correct column
            $withdraw = Withdrawal::where('payout_batch_id', $transactionGuid)->first(); // Ensure the correct column

            if (!$withdraw) {
                return response()->error("Withdrawal not found for transaction: $transactionGuid");
            }

            // Update status if transaction was successful
            if ($status === 'done') { // Ensure 'done' means success
                $withdraw->status = 'completed';
                $withdraw->save();

//                $wallet = Wallet::find($withdraw->wallet_id);
//
//                if (!$wallet) {
//                    return response()->error('Wallet not found');
//                }

                // Deduct the withdrawn amount from wallet balance
//                $wallet->decrement('balance', $withdraw->amount);

                return response()->success($request->all(), "Withdraw status updated successfully");
            }

            return response()->error("Transaction status is not successful");
        } catch (\Exception $e) {
            return response()->error("Failed to process callback", $e->getMessage());
        }
    }


    /*
     * Get Transaction Details by id
     */
    public function getTransaction($transactionId)
    {
        return $this->apsService->getTransactionDetails($transactionId);
    }

    /*
     * Get Account Info
     */
    public function getInfo($merchatGUID)
    {
        return $this->apsService->getInfo($merchatGUID);
    }


}
