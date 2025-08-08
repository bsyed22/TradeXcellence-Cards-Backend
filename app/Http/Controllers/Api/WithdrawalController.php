<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminUserKYCLinkEmail;
use App\Mail\AdminUserRegistered;
use App\Mail\AdminWithdrawalStatusChanged;
use App\Mail\AdminWithdrawalSubmitted;
use App\Mail\UserWithdrawalStatusChanged;
use App\Mail\UserWithdrawalSubmitted;
use App\Models\CardHolderLink;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.0001',
            'txn_hash' => 'nullable|string',
            'notes' => 'nullable|string',
            'card_id' => 'required|numeric|min:0.0001',
            'wallet_address' => 'required|string|max:255',
            'blockchain' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $withdrawal = Withdrawal::create([
            'user_id' => $request->user_id,
            'card_id' => $request->card_id,
            'amount' => $request->amount,
            'txn_hash' => $request->txn_hash,
            'notes' => $request->notes,
            'wallet_address' => $request->wallet_address,
            'blockchain' => $request->blockchain,
        ]);

        $user = User::find($withdrawal->user_id);
        $admin = User::where("email", "admin@vortexfx.com")->first();

        //Notification Work
        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'manual', // optional, can be custom string
            'notifiable_type' => \App\Models\User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Withdrawal Request',
                'message' => 'New Withdrawal Request has  been created.',
                'action_url' => '/deposits/123'
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
                        'title' => 'Withdrawal Request',
                        'message' => 'New Withdrawal Request Received from.' . $user->name,
                        'action_url' => '/deposits/123'
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Mail::to($admin->email)->send(new AdminWithdrawalStatusChanged($withdrawal));

            } catch (\Exception $e) {

            }
        }


        Mail::to($user->email)->send(new UserWithdrawalSubmitted($withdrawal));

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request submitted successfully.',
            'data' => $withdrawal,
        ]);
    }

    public function approve($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        $withdrawal->update(['status' => 'approved']);

        $user = User::find($withdrawal->user_id);

        //Notification Work
        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'manual', // optional, can be custom string
            'notifiable_type' => \App\Models\User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Withdrawal Request Status Updated',
                'message' => 'Withdrawal Request Approved.',
                'action_url' => '/deposits/123'
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);


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
                        'title' => 'Withdrawal Request Status Updated',
                        'message' => 'New Withdrawal Request from.' . $user->name . " has been approved",
                        'action_url' => '/deposits/123'
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Mail::to($admin->email)->send(new AdminWithdrawalStatusChanged($withdrawal));

            } catch (\Exception $e) {

            }
        }



        Mail::to($user->email)->send(new UserWithdrawalStatusChanged($withdrawal));

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal approved successfully.',
            'data' => $withdrawal,
        ]);
    }

    public function reject($id)
    {
        $withdrawal = Withdrawal::findOrFail($id);
        $withdrawal->update(['status' => 'rejected']);

        $user = User::find($withdrawal->user_id);
        $admins = User::role('admin')->get(); // works with Spatie\Permission\Traits\HasRoles

        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'manual', // optional, can be custom string
            'notifiable_type' => \App\Models\User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Withdrawal Request Status Updated',
                'message' => 'Deposit Request Rejected.',
                'action_url' => '/deposits/123'
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
                        'title' => 'Withdrawal Request Status Updated',
                        'message' => 'New Withdrawal Request from.'.$user->name." has been rejected",
                        'action_url' => '/deposits/123'
                    ]),
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Mail::to($admin->email)->send(new AdminWithdrawalStatusChanged($withdrawal));

            } catch (\Exception $e) {

            }
        }


        Mail::to($user->email)->send(new UserWithdrawalStatusChanged($withdrawal));

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal rejected successfully.',
            'data' => $withdrawal,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            $withdrawal = Withdrawal::findOrFail($id);

            // Validate only fields provided in the request
            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|exists:users,id',
                'amount' => 'sometimes|numeric|min:0.0001',
                'card_id' => 'sometimes|numeric|min:0.0001',
                'wallet_address' => 'sometimes|string|max:255',
                'blockchain' => 'sometimes|string|max:100',
                'txn_hash' => 'sometimes|string|nullable',
                'transaction_id' => 'sometimes|integer|min:1|nullable',
                'notes' => 'sometimes|string|nullable',
                'status' => 'sometimes|in:pending,approved,rejected',
            ]);

            if ($validator->fails()) {
                return response()->error('Validation failed.', $validator->errors(), 422);
            }

            // Only update provided fields
            $withdrawal->fill($request->only([
                'user_id',
                'amount',
                'card_id',
                'wallet_address',
                'blockchain',
                'txn_hash',
                'transaction_id',
                'notes',
                'status',
            ]));

            $withdrawal->save();

            return response()->success($withdrawal, 'Withdrawal updated successfully.');
        } catch (\Exception $e) {
            return response()->error('Something went wrong. Please try again.', null, 500);
        }
    }
}
