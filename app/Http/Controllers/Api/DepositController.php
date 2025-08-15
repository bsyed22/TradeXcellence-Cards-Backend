<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AdminActivatePhysicalCard;
use App\Mail\AdminDepositStatusChanged;
use App\Mail\AdminDepositSubmitted;
use App\Mail\AdminUserKYCLinkEmail;
use App\Mail\AdminUserRegistered;
use App\Mail\UserDepositStatusChanged;
use App\Mail\UserDepositSubmitted;
use App\Models\CardHolderLink;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DepositController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'coupon_id' => 'nullable|exists:coupons,id',
                'amount' => 'required|numeric|min:0.0001',
                'card_id' => 'nullable|numeric|min:0.0001',
                'txn_hash' => 'nullable|string',
                'transaction_id' => 'nullable|string',
                'callback_id' => 'nullable|string',
                'alias' => 'nullable|string',
                'card_type' => 'nullable|string',
                'notes' => 'nullable|string',
                'fee' => 'nullable',
                'proof_image' => 'required|image|max:5048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $path = $request->file('proof_image')->store('proofs', 'public');

            $deposit = Deposit::create([
                'user_id' => $request->user_id,
                'card_id' => $request->card_id,
                'coupon_id' => $request->coupon_id,
                'fee' => $request->fee,
                'alias' => $request->alias,
                'card_type' => $request->card_type,
                'txn_hash' => $request->txn_hash,
                'transaction_id' => $request->transaction_id,
                'callback_id' => $request->callback_id,
                'amount' => $request->amount,
                'notes' => $request->notes,
                'proof_image' => $path,
            ]);

            $user = User::find($request->user_id);

            // Get all users with 'admin' role
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
                            'title' => 'Deposit Request',
                            'message' => 'New Deposit Request Received from.'.$user->name,
                            'action_url' => '/deposits/123'
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Mail::to($admin->email)->send(new AdminDepositSubmitted($deposit));

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
                    'title' => 'Deposit Request',
                    'message' => 'New Deposit Request.',
                    'action_url' => '/deposits/123'
                ]),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Mail::to($user->email)->send(new UserDepositSubmitted($deposit));

            return response()->json([
                'success' => true,
                'message' => 'Deposit request submitted successfully.',
                'data' => $deposit,
            ]);
        } catch (\Exception $e) {
            Log::error('Deposit store failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the deposit.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function approve($id)
    {
        try {
            $deposit = Deposit::findOrFail($id);
            $deposit->update(['status' => 'approved']);

            $user=User::find($deposit->user_id);

//            $admin = User::where("email","admin@vortexfx.com")->first();

            //Notification Work
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'manual', // optional, can be custom string
                'notifiable_type' => \App\Models\User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Deposit Request Status Updated',
                    'message' => 'Deposit Request Approved.',
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
                            'title' => 'Deposit Request Status Updated',
                            'message' => 'New Deposit Request from.'.$user->name."has been approved",
                            'action_url' => '/deposits/123'
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Mail::to($admin->email)->send(new AdminDepositStatusChanged($deposit));

                } catch (\Exception $e) {

                }
            }

            Mail::to($user->email)->send(new UserDepositStatusChanged($deposit));

            return response()->json([
                'success' => true,
                'message' => 'Deposit approved successfully.',
                'data' => $deposit,
            ]);
        } catch (\Exception $e) {
            Log::error('Deposit approval failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while approving the deposit.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function reject($id)
    {
        try {
            $deposit = Deposit::findOrFail($id);
            $deposit->update(['status' => 'rejected']);
            $user = User::find($deposit->user_id);

            $admin = User::where("email","admin@vortexfx.com")->first();

            //Notification Work
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'manual', // optional, can be custom string
                'notifiable_type' => \App\Models\User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Deposit Request Status Updated',
                    'message' => 'Deposit Request Rejected.',
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
                            'title' => 'Deposit Request Status Updated',
                            'message' => 'New Deposit Request from.'.$user->name."has been rejected",
                            'action_url' => '/deposits/123'
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Mail::to($admin->email)->send(new AdminDepositStatusChanged($deposit));

                } catch (\Exception $e) {

                }
            }

            Mail::to($user->email)->send(new UserDepositStatusChanged($deposit));

            return response()->json([
                'success' => true,
                'message' => 'Deposit rejected successfully.',
                'data' => $deposit,
            ]);
        } catch (\Exception $e) {
            Log::error('Deposit rejection failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rejecting the deposit.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $deposit = Deposit::findOrFail($id);

            // Validate only provided fields
            $validator = Validator::make($request->all(), [
                'user_id' => 'sometimes|exists:users,id',
                'amount' => 'sometimes|numeric|min:0.0001',
                'coupon_id' => 'sometimes|exists:coupons,id',
                'card_id' => 'sometimes|numeric|min:0.0001',
                'txn_hash' => 'sometimes|string|nullable',
                'transaction_id' => 'sometimes|integer|min:1|nullable',
                'fee' => 'sometimes|numeric|nullable',
                'currency' => 'sometimes|nullable',
                'notes' => 'sometimes|string|nullable',
                'status' => 'sometimes|in:pending,approved,rejected',
                'proof_image' => 'sometimes|image|max:2048', // optional image update
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Handle optional image update
            if ($request->hasFile('proof_image')) {
                $path = $request->file('proof_image')->store('proofs', 'public');
                $deposit->proof_image = $path;
            }

            // Fill only provided attributes
            $deposit->fill($request->only([
                'user_id',
                'amount',
                'card_id',
                'callback_id',
                'fee',
                'currency',
                'txn_hash',
                'transaction_id',
                'fee',
                'notes',
                'status',
            ]));

            $deposit->save();

            return response()->success($deposit,"Deposit updated successfully.",200);
        } catch (\Exception $e) {

            return response()->error('Something went wrong. Please try again.', $e->getMessage(), 500);
        }
    }
}
