<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminDepositStatusChanged;
use App\Mail\AdminDepositSubmitted;
use App\Mail\AdminUserKYCLinkEmail;
use App\Mail\UserDepositSubmitted;
use App\Mail\UserKYCLinkEmail;
use App\Mail\UserWelcomeEmail;
use App\Models\CardHolderLink;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{

    public function index()
    {
        $users = User::with('roles.permissions')->get();

        $users->each(function ($user) {
            $user->role = $user->getRoleNames()->first(); // Add 'role' attribute for response
        });

        return response()->success($users, "List of Users", 200);
    }

    public function sendKycLink(Request $request)
    {
        $user = User::where("physical_card_holder_id",$request->physical_card_holder_id)->first();
        if(!$user){
            return response()->error("User not found", 404);
        }else{

            if($user->kyc_link==null)
            {
                return  response()->error("KYC Link not found", 404);
            }
            $admins = User::role('admin')->get(); // works with Spatie\Permission\Traits\HasRoles

            foreach ($admins as $admin) {
                if (!filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                try {
                    DB::table('notifications')->insert([
                        'id' => Str::uuid(),
                        'type' => 'manual', // optional, can be custom string
                        'notifiable_type' => \App\Models\User::class,
                        'notifiable_id' => $admin->id,
                        'data' => json_encode([
                            'title' => 'KYC Link Generated',
                            'message' => 'New KYC Link generated for '.$user->name,
                            'action_url' => '/deposits/123'
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Mail::to($admin->email)->send(new AdminUserKYCLinkEmail($user));

                } catch (\Exception $e) {

                }
            }


            //Notification Work
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'manual', // optional, can be custom string
                'notifiable_type' => \App\Models\User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'KYC Verification',
                    'message' => 'KYC Link has been sent on your email. Check your email to complete your KYC',
                    'action_url' => '/deposits/123'
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Mail::to($user->email)->send(new UserKYCLinkEmail($user));
            return response()->success($user, "KYC Link Generated", 200);
        }
    }
    public function getUserByToken(Request $request)
    {
        $token = $request->bearerToken();

        // Find the token in the personal_access_tokens table
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.',
            ], 404);
        }

        // Get the user from token
        $user = $accessToken->tokenable;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Load relationships
        $user->load('customFields', 'kycLogs', 'wallets.currency');

        return response()->success($user, "User Profile Data", 200);
    }

    public function update(Request $request, $id)
    {

        // Find user or return error
        $user = User::find($id);
        if (!$user) {
            return response()->error(404, "User not found.");
        }

        // Validate input (optional but recommended)
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'kyc_link' => 'sometimes|string',
            'status' => 'sometimes|string',
            'password' => 'sometimes|string|min:6',
            'card_holder_id' => 'sometimes|integer',
            'physical_card_holder_id' => 'sometimes|integer',
            'kyc_verified' => 'sometimes|integer',
        ]);

        // Update fields if provided
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('kyc_link')) {
            $user->kyc_link = $request->kyc_link;
        }

        if ($request->has('status')) {
            $user->status = $request->status;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
//        dd("Asd");
        // Update full name if either first or last changed
        if ($request->has('card_holder_id')) {
            $user->card_holder_id = $request->card_holder_id;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->has('physical_card_holder_id')) {
            $user->physical_card_holder_id = $request->physical_card_holder_id;
        }
        if ($request->has('kyc_verified')) {
            $user->kyc_verified = $request->kyc_verified;
        }
        $user->save();

        return response()->success($user, "Profile Updated Successfully", 200);
    }

    public function profile(Request $request)
    {
        $token = $request->bearerToken();
        $data = User::findByToken($token);
        if(!$data){
            return response()->error("User not found.",null,404);
        }else{
            $data->role = $data->getRoleNames()->first();
            $data->load('roles.permissions');
            return response()->success($data,"User Profile",200);
        }
    }

    public function profileById($id)
    {
        $data = User::find($id);
        if(!$data){
            return response()->error("User not found.",null,404);
        }else{
            return response()->success($data,"User Profile",200);
        }
    }

    public function verifyEmailCode(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $bearerToken = $request->bearerToken();
        $user = User::findByToken($bearerToken);

        if (!$user) {
            return response()->error('Unauthorized or user not found.', null, 401);
        }

        if (isCodeValid($user->email_verification_code, $user->email_verification_code_expires_at, $request->code)) {
            Mail::to($user->email)->send(new UserWelcomeEmail($user));

            // Mark email as verified
            $user->update([
                'email_verification' => 1,
                'email_verified_at' => now(),
                'email_verification_code' => null,
                'email_verification_code_expires_at' => null,
            ]);

            return response()->success(null, 'Email verified successfully.');
        }

        return response()->error('Invalid or expired verification code.', null, 422);
    }
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $bearerToken = $request->bearerToken();
        $user = User::findByToken($bearerToken);

        if (!$user) {
            return response()->error('Unauthorized or user not found.', null, 401);
        }

        if (isCodeValid($user->email_verification_code, $user->email_verification_code_expires_at, $request->code)) {

            return response()->success(null, 'Email verified successfully.');
        }

        return response()->error('Invalid or expired verification code.', null, 422);
    }


    public function deleteUser($id)
    {
        User::find($id)->delete();
        return response()->success(null, "User deleted successfully.", 200);
    }

    public function dashboardSummary()
    {
        try {
            $totalCardBalance = CardHolderLink::sum('balance');
            $totalActiveCards = CardHolderLink::where('status', 'active')->count();
            $totalDeposit = Deposit::where("status", "approved")->sum('amount');
            $totalWithdrawals = Withdrawal::where("status", "approved")->sum('amount');

            $transactionList = [];

            $startDate = now()->format('Y-m-d');
            $endDate = now()->addYear()->endOfDay()->format('Y-m-d');

            $users = User::whereNotNull('card_holder_id')->get();

            foreach ($users as $user) {
                $cards = CardHolderLink::where('card_holder_id', $user->card_holder_id)
                    ->whereNotNull('card_id')
                    ->pluck('card_id');

                foreach ($cards as $cardId) {
                    $response = $this->settingsService->getCardTransactions(
                        $user->card_holder_id,
                        $cardId,
                        $startDate,
                        $endDate
                    );

                    if (!isset($response->original['success']) || !$response->original['success']) {
                        continue;
                    }

                    $transactions = $response->original['data'] ?? [];

                    foreach ($transactions as $transaction) {
                        if ((int)($transaction['transStatus'] ?? 0) === 1) {
                            $amount = floatval($transaction['amount'] ?? 0);

                            $transactionList[] = [
                                'date' => $transaction['transDate'] ?? null,
                                'amount' => number_format($amount, 2),
                                'transId' => $transaction['transactionId'] ?? null,
                            ];
                        }
                    }
                }
            }

            $summary = [
                'total_card_balance' => number_format($totalCardBalance, 2),
                'totalActiveCards' => $totalActiveCards,
                'totalDeposit' => number_format($totalDeposit, 2),
                'totalWithdrawals' => number_format($totalWithdrawals, 2),
                'transactions' => $transactionList,
            ];

            return response()->success($summary, 'Dashboard summary fetched successfully');
        } catch (\Exception $e) {
            return response()->error('Something went wrong', $e->getMessage(), 500);
        }
    }

}

