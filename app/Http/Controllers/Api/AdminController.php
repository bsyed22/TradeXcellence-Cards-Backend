<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\UserWelcomeEmail;
use App\Models\CardHolderLink;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function transactions($id = null)
    {

        $deposits = Deposit::with(['user','card'])
            ->when($id, fn($q) => $q->where('user_id', $id))
            ->get()
            ->map(function ($item) {
                $item->type = 'deposit';
                return $item; // ← keep as model
            });

        $withdrawals = Withdrawal::with(['user','card'])
            ->when($id, fn($q) => $q->where('user_id', $id))
            ->get()
            ->map(function ($item) {
                $item->type = 'withdrawal';
                return $item; // ← keep as model
            });

        $combined = $deposits->concat($withdrawals)
            ->sortByDesc('created_at')
            ->values(); // Resets keys

        return response()->success($combined, "Transactions fetched successfully", 200);

    }


    public function verifyEmailByAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {

            return response()->error('Validation failed. User not found', $validator->errors(), 422);

        }

        $user = User::find($request->user_id);

        if ($user->email_verification) {
            return response()->error("Email already verified", 422);
        }

        $user->update([
            'email_verification' => 1,
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ]);


        if(!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            // Skip sending email
        } else {
            Mail::to($user->email)->send(new UserWelcomeEmail($user));
        }


        return response()->success("Email verified successfully",$user ,200);
    }

    public function dashboardSummary()
    {
        try {
            $totalCardBalance = CardHolderLink::sum('balance');
            $totalActiveUsers = User::where('status', 'active')->count();
            $totalCards = CardHolderLink::count();
            $totalDeposit = Deposit::where("status","approved")->sum('amount');
            $totalWithdrawal = Withdrawal::where("status","approved")->sum('amount');
            $latestUsers = User::orderBy('created_at', 'desc')->take(10)->get();

            $cards = CardHolderLink::all();

            $deposits = Deposit::with(['user', 'card'])
                ->get()
                ->map(function ($item) {
                    $item->type = 'deposit';
                    return $item;
                });


            $withdrawals = Withdrawal::with(['user', 'card'])
                ->get()
                ->map(function ($item) {
                    $item->type = 'withdrawal';
                    return $item;
                });

            $latestTransactions = $deposits->merge($withdrawals)
                ->sortByDesc('created_at')
                ->take(10) // limit to latest 10 records
                ->values(); // reindex the collection

            $summary = [
                'total_card_balance' => (float) $totalCardBalance,
                'total_active_users' => $totalActiveUsers,
                'total_cards' => $totalCards,
                'total_deposit' => (float) $totalDeposit,
                'total_withdrawal' => (float) $totalWithdrawal,
                'latest_transactions' => $latestTransactions,
                'latest_users' => $latestUsers,
                'cards'=>$cards
            ];

            return response()->success($summary, 'Dashboard summary fetched successfully');
        } catch (\Exception $e) {
            return response()->error('Something went wrong', $e->getMessage(), 500);
        }
    }

}
