<?php

namespace App\Providers;


use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Response;
use Twilio\Rest\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
//        Gate::define('viewPulse', fn ($user) => $user->is_admin);

//        Pulse::auth(fn ($request) => Gate::authorize('viewPulse'));


        Schema::defaultStringLength(191);

        // Success response format macro
        Response::macro('success', function ($data = [], $message = 'Operation successful', $status = 200) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data
            ], $status);
        });

        // Error response format macro
        Response::macro('error', function ($message = 'An error occurred', $data = null, $status = 400) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => $data
            ], $status);
        });
    }
}
