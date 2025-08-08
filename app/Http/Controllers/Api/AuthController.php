<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserWithTokenResource;
use App\Mail\AdminDepositSubmitted;
use App\Mail\AdminUserRegistered;
use App\Mail\SendPasswordResetCode;
use App\Mail\SendVerificationCode;
use App\Mail\UserWelcomeEmail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RegisterUserRequest;
use App\Http\Requests\LoginUserRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;


class AuthController extends Controller
{
    //Register
    public function register(RegisterUserRequest $request)
    {
        $verificationCode = random_int(100000, 999999);

        DB::beginTransaction();

        try {
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verification_code' => $verificationCode,
                'email_verification_code_expires_at' => now()->addMinutes(15),
            ]);

            if($request->has('role_id'))
            {
                $role = Role::findOrFail($request->role_id);
                $user->assignRole($role);
            }
//            else{
//                $role = Role::where("name", "user")->first();
//                $user->assignRole($role);
//            }


            //Notification Work
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'manual', // optional, can be custom string
                'notifiable_type' => \App\Models\User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Welcome to BlackDuck',
                    'message' => 'Please update your profile to continue',
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
                        'type' => 'manual',
                        'notifiable_type' => \App\Models\User::class,
                        'notifiable_id' => $admin->id,
                        'data' => json_encode([
                            'title' => 'New User Registration',
                            'message' => "New user registered: {$user->name} ({$user->email})",
                            'action_url' => '/deposits/123'
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Mail::to($admin->email)->send(new AdminUserRegistered($user));

                } catch (\Exception $e) {

                }
            }

            // Send verification email
            Mail::to($user->email)->send(new SendVerificationCode($verificationCode));
//            Mail::to($user->email)->send(new UserWelcomeEmail($user));

            // Generate token
            $token = $user->createToken($user->email . '-Temporary-Token')->plainTextToken;
            $user->token = $token;
            DB::commit();
            return response()->success($user, 'User registered successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error('Registration error: ' . $e->getMessage(), null, 500);
        }
    }

    //Register with Referral
    public function registerWithReferral(RegisterUserRequest $request)
    {
        $verificationCode = random_int(100000, 999999);

        DB::beginTransaction();

        try {
            // Create the user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verification_code' => $verificationCode,
                'email_verification_code_expires_at' => now()->addMinutes(15),
            ]);

            if($request->has('role_id'))
            {
                $role = Role::findOrFail($request->role_id);
                $user->assignRole($role);
            }


            //Notification Work
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'manual', // optional, can be custom string
                'notifiable_type' => \App\Models\User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => 'Welcome to BlackDuckCard',
                    'message' => 'Please update your profile to continue',
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
                        'type' => 'manual',
                        'notifiable_type' => \App\Models\User::class,
                        'notifiable_id' => $admin->id,
                        'data' => json_encode([
                            'title' => 'User Registration',
                            'message' => 'New User Registration.',
                            'action_url' => '/deposits/123'
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Mail::to($admin->email)->send(new AdminUserRegistered($user));

                } catch (\Exception $e) {

                }
            }

            // Send verification email
            Mail::to($user->email)->send(new SendVerificationCode($verificationCode));
//            Mail::to($user->email)->send(new UserWelcomeEmail($user));

            // Generate token
            $token = $user->createToken($user->email . '-Temporary-Token')->plainTextToken;
            $user->token = $token;
            DB::commit();
            return response()->success($user, 'User registered successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->error('Registration error: ' . $e->getMessage(), null, 500);
        }
    }

    //login
    public function login(LoginUserRequest $request)
    {
        // Validate request
        $credentials = $request->validated();

        // Attempt authentication with the provided credentials
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken($user->email . '-API-Token')->plainTextToken;

            // Check if email verification is 1
            if ($user->email_verification != 1) {
                $user->token = $token;
                $user->role = $user->getRoleNames()->first();
                return response()->success($user, "Login success. Email", 200);
            }

            // Revoke all previous tokens
            //$user->tokens()->delete();

            // Generate a new token
            $expiry = Carbon::now()->addHours(2);
            $tokenId = explode('|', $token)[0];

            // Update token expiration in the database
            DB::table('personal_access_tokens')
                ->where('id', $tokenId)
                ->update(['expires_at' => $expiry]);

            // Attach the token to the user response
            $user->token = $token;
            $user->role = $user->getRoleNames()->first();
            // Return success response with user and token if both verifications passed
            return response()->success($user, 'Login successful', 200);
        } else {
            return response()->error('Incorrect Email / Password', null, 401);
        }
    }

    //Forgot Password
    public function forgotPassword(Request $request)
    {
        try {
            // Validate request manually to capture and return validation errors
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
            ]);

            if ($validator->fails()) {

                $firstError = $validator->errors()->first();
                return response()->error($firstError, $validator->errors(), 422);

            }

            $user = User::where('email', $request->email)->first();

            $resetCode = random_int(100000, 999999); // 6-digit code

            // Store token in password_resets table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                ['token' => Hash::make($resetCode), 'created_at' => now()]
            );

            // Send the password reset email
            Mail::to($user->email)->send(new SendPasswordResetCode($resetCode));

            return response()->success([], 'Password reset code sent successfully.', 200);
        } catch (\Exception $e) {

            return response()->error('Something went wrong. Please try again.',null, 500);
        }
    }

    //reset password
    public function resetPassword(Request $request)
    {
        try {
            // Manual validator to customize error response
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'token' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {

                return response()->error('Validation failed.', $validator->errors(), 422);

            }

            $resetRequest = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRequest || !Hash::check($request->token, $resetRequest->token)) {

                return response()->error('Invalid or expired reset code.',null,400);

            }

            $user = User::where('email', $request->email)->first();
            $user->update(['password' => Hash::make($request->password)]);

            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->success([], 'Password reset successfully.', 200);

        } catch (\Exception $e) {
            return response()->error('Something went wrong. Please try again.',null,500);
        }
    }

    public function sendEmailVerificationCode(Request $request)
    {
        try {
            $bearerToken = $request->bearerToken();
            $user = User::findByToken($bearerToken);
            if (!$user) {
                return response()->error('Unauthorized or user not found.', null, 401);
            }

            $verificationCode = random_int(100000, 999999);

            $user->update([
                'email_verification_code' => $verificationCode,
                'email_verification_code_expires_at' => now()->addMinutes(15),
            ]);

            Mail::to($user->email)->send(new SendVerificationCode($verificationCode));

            return response()->success(null, 'Verification email sent successfully.');
        } catch (\Exception $e) {
            return response()->error('Failed to send verification email: ' . $e->getMessage(), null, 500);
        }
    }
}
