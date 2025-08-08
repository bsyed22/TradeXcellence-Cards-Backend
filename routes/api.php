<?php

use App\Http\Controllers\Api\{AdminController,
    AuthController,
    CardHolderLinkController,
    DepositController,
    NotificationController,
    ReferralLinkController,
    ReferralProgramController,
    ReferralRequestController,
    RoleController,
    SettingsController,
    UserController,
    WithdrawalController};
use App\Http\Controllers\Api\admin\PaymentMethodController;
use App\Http\Controllers\Api\ALT5Controller;
use App\Http\Controllers\Api\BrandingSettingController;
use App\Http\Controllers\Api\PaymentMethods\{APSController, CoinsBuyController};
use Illuminate\Support\Facades\Route;

//alt5 payment method callback
Route::match(['get', 'post'], '/alt5-webhook-callback', [ALT5Controller::class, 'handleCallback']);

//Register with referral
Route::post('register/referral/{referral_code}', [AuthController::class, 'registerWithReferral']);

//Track Referral Link
Route::post('/track-referral/{referral_code}', [ReferralLinkController::class, 'trackClick']);

//Register Route
Route::post('register', [AuthController::class, 'register']);

//Referral Program CRUD
Route::apiResource('referral-programs', ReferralProgramController::class);

//referral requests
Route::prefix('referral-requests')->group(function () {
    // Submit a referral request (User)
    Route::post('/', [ReferralRequestController::class, 'submit']);

    // List all referral requests (Admin)
    Route::get('/', [ReferralRequestController::class, 'list']);

    // Approve a referral request (Admin)
    Route::post('{id}/approve', [ReferralRequestController::class, 'approve']);

    // Reject a referral request (Admin)
    Route::post('{id}/reject', [ReferralRequestController::class, 'reject']);
});



//send verification email
Route::post('/send-verification-email', [AuthController::class, 'sendEmailVerificationCode']);

//Login Route
Route::post('login', [AuthController::class, 'login']);

//Forgot Password
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

//Reset Password
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

//Email confirmation route
Route::post('/send-verification-email', [AuthController::class, 'sendEmailVerificationCode']);

//Route::middleware('auth:sanctum')->group(function () {
Route::middleware([])->group(function () {
    //Get User Profile by Token
    Route::get('/user/profile', [UserController::class, 'profile']);

//Get User Profile by Token
    Route::get('/user/profile_by_id/{id}', [UserController::class, 'profileById']);

//Get User Profile by Token
    Route::put('/user/update/{id}', [UserController::class, 'update']);

//Get User Profile by Token
    Route::post('create/deposit', [DepositController::class, 'hostedDeposit']);

//Get User Profile by Token
    Route::get('users', [UserController::class, 'index']);

    Route::post('/send-kyc-link', [UserController::class, 'sendKycLink']);

    Route::post('/verify-email', [UserController::class, 'verifyEmailCode']);

    Route::post('/verify-email-code', [UserController::class, 'verifyEmail']);

    Route::delete('/delete-user/{id}', [UserController::class, 'deleteUser']);

    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions']);

// Deposit Routes
    Route::post('/deposits', [DepositController::class, 'store']);
    Route::post('/deposits/{id}/approve', [DepositController::class, 'approve']);
    Route::post('/deposits/coinsbuy/{id}/approve', [CoinsBuyController::class, 'reviewCoinsbuyWithdrawal']);
    Route::post('/deposits/{id}/reject', [DepositController::class, 'reject']);

// Withdrawal Routes
    Route::post('/withdrawals', [WithdrawalController::class, 'store']);
    Route::post('/withdrawals/{id}/approve', [WithdrawalController::class, 'approve']);
    Route::post('/withdrawals/{id}/reject', [WithdrawalController::class, 'reject']);

    Route::apiResource('card-holder-links', CardHolderLinkController::class);

    Route::post("/request-physical-card", [CardHolderLinkController::class, 'requestPhysicalCard']);
    Route::get("/physical-card-requests", [CardHolderLinkController::class, 'physicalCardRequests']);
    Route::get("/request-physical-card-requests/{physical_card_holder_id}", [CardHolderLinkController::class, 'userPhysicalCardRequests']);
    Route::put('/activate-physical-card/{id}', [CardHolderLinkController::class, 'activateCard']);

    Route::get('cards-by-card-holder-link-id/{id}', [CardHolderLinkController::class, "cardsByCardHolderLinkId"]);

    Route::get("/transactions/{id?}", [AdminController::class, 'transactions']);


    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/user/{id}', [NotificationController::class, 'getNotificationsByUserId']);
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);

    //Settings by mode
    Route::get('/settings/{mode}',[SettingsController::class,'settingsByMode']);

    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index']); // Get all settings
        Route::get('/{key}', [SettingsController::class, 'show']); // Get setting by key
        Route::post('/', [SettingsController::class, 'storeOrUpdate']); // Create or update setting
        Route::delete('/{key}', [SettingsController::class, 'destroy']); // Delete setting by key
    });

    Route::put("/update/withdrawals/{id}", [WithdrawalController::class, 'update']);
    Route::put("/update/deposit/{id}", [DepositController::class, 'update']);

    Route::post('/admin/verify-user-email', [AdminController::class, 'verifyEmailByAdmin']);


    Route::prefix('admin')->group(function () {
        //Payment Methods CRUD
        Route::apiResource('payment-methods', PaymentMethodController::class);

        Route::get('dashboard-summary', [AdminController::class, 'dashboardSummary']);
    });

    Route::get("/user/dashboardSummary",[UserController::class, 'dashboardSummary']);

// Deposit Routes
    Route::prefix('deposit')->group(function () {

        //Coins buy
        Route::prefix('coinsbuy')->controller(CoinsBuyController::class)->group(function () {
            Route::get('/test', 'test');
            Route::post('/deposit', 'deposit');
            Route::match(['get', 'post'], '/capture', 'handleCallBack');

        });

        // APS
        Route::prefix('aps')->controller(APSController::class)->group(function () {
            Route::post('/deposit', 'deposit');
            Route::match(['get', 'post'], '/capture', 'depositCallBack');
        });

        //Now Payments
        Route::prefix('nowpayments')->controller(NowPaymentsController::class)->group(function () {

            Route::post('/deposit', 'deposit');

            Route::get('/currencies', 'currencies');

            Route::match(['get', 'post'], '/capture', 'handleCallBack');

        });

    });

// Withdraw Routes
    Route::prefix('withdraw')->group(function () {

        //Coins buy
        Route::prefix('coinsbuy')->controller(CoinsBuyController::class)->group(function () {
            Route::post('/withdraw', 'requestCoinsbuyWithdrawal');
            Route::post('/payout-charges', 'payoutCharges');
            Route::match(['get', 'post'], '/capture', 'handleCallBack');

        });

        // APS
        Route::prefix('aps')->controller(APSController::class)->group(function () {
            Route::post('/withdraw', 'requestWithdrawal');
            Route::get('/transaction/{transactionId}', 'getTransaction');
            Route::get('/info/{merchantGUID}', 'getInfo');
            Route::match(['get', 'post'], '/capture', 'payoutCallBack');
        });

    });
});

Route::prefix('branding')->group(function () {
    Route::get('/', [BrandingSettingController::class, 'index']);         // GET /api/branding
    Route::post('/', [BrandingSettingController::class, 'storeOrUpdate']); // POST /api/branding
});





