<?php

namespace App\Services;

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Str;

class CoinsBuy
{
    protected $baseUrl;
    protected $appKey;
    protected $appSecret;
    protected $depositCallBack;
    protected $withdrawCallBack;
    protected $paymentPageRedirectUrl;
    protected $paymentPageButtonText;
    protected $accessToken;
    protected $credentialsLoaded = false;

    public function __construct()
    {
        // Initialize all properties to null
        $this->baseUrl = null;
        $this->appKey = null;
        $this->appSecret = null;
        $this->depositCallBack = null;
        $this->withdrawCallBack = null;
        $this->paymentPageRedirectUrl = null;
        $this->paymentPageButtonText = null;
        $this->accessToken = null;
        $this->credentialsLoaded = false;
    }

    protected function loadCredentials()
    {
        if ($this->credentialsLoaded) {
            return;
        }
        $paymentMethod = PaymentMethod::where('name', 'Coinsbuy')->where('is_active', true)->first();
        if (!$paymentMethod) {
            $this->baseUrl = config('coinsbuy.sandbox_url', '');
            $this->appKey = null;
            $this->appSecret = null;
            $this->depositCallBack = null;
            $this->withdrawCallBack = null;
            $this->paymentPageRedirectUrl = null;
            $this->paymentPageButtonText = null;
            $this->accessToken = null;
            $this->credentialsLoaded = true;
            return;
        }
        $config = $paymentMethod->settings;
        $this->baseUrl = ($config['mode'] ?? 'sandbox') === 'sandbox'
            ? $config['sandbox_url']
            : $config['live_url'];

        $this->appKey = $config['app_key'];
        $this->appSecret = $config['app_secret'];
        $this->depositCallBack = $config['deposit_callback_url'];
        $this->withdrawCallBack = $config['withdraw_callback_url'];
        $this->paymentPageRedirectUrl = $config['payment_page_redirect_url'];
        $this->paymentPageButtonText = $config['payment_page_button_text'];
        $this->credentialsLoaded = true;

    }

    protected function generateAccessToken()
    {
        $this->loadCredentials();

        if (!$this->baseUrl || !$this->appKey || !$this->appSecret) {
            return response()->error('Coinsbuy credentials not configured');
        }

        $url = $this->baseUrl . '/token/';
        $response = Http::withHeaders([
            'Content-Type' => 'application/vnd.api+json',
            'Accept' => 'application/vnd.api+json',
        ])->post($url, [
            'data' => [
                'type' => 'auth-token',
                'attributes' => [
                    'login' => $this->appKey,
                    'password' => $this->appSecret,
                ],
            ],
        ]);

        // Handle the token generation
        if ($response->successful()) {
            $data = $response->json();
            $data = $data["data"]["attributes"]["access"];
            $this->accessToken = $data;
            return response()->success($data, 'Token retrieved successfully');
        } else {
            return response()->error('Failed to retrieve token', $response->json(), $response->status());
        }

    }

    /*
     * Initiate Deposit
     */
    public function deposit($postData)
    {
        $accessTokenResponse = $this->generateAccessToken();
        if ($accessTokenResponse->getStatusCode() !== 200) {  // Or whatever success code you're using
            return $accessTokenResponse; // Forward the error response
        }

        $url = $this->baseUrl . '/deposit/';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/vnd.api+json',
        ])->post($url, $postData);

        // Handle the response
        if ($response->successful()) {
            return response()->success($response->json(), 'Deposit initiated successfully');
        } else {
            return response()->error('Failed to create deposit', $response->json(), $response->status());
        }
    }

    public function withdraw($postData)
    {
        $accessTokenResponse = $this->generateAccessToken();
        if ($accessTokenResponse->getStatusCode() !== 200) {  // Or whatever success code you're using
            return $accessTokenResponse; // Forward the error response
        }

        $url = $this->baseUrl . '/payout/';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/vnd.api+json',
            'idempotency-key' => Str::uuid()->toString(),
        ])->post($url, $postData);

        // Handle the response
        if ($response->successful()) {
            return response()->success($response->json(), 'Deposit initiated successfully');
        } else {
            return response()->error('Failed to create deposit', $response->json(), $response->status());
        }
    }

    public function payoutCharges($postData)
    {
        $accessTokenResponse = $this->generateAccessToken();
        if ($accessTokenResponse->getStatusCode() !== 200) {  // Or whatever success code you're using
            return $accessTokenResponse; // Forward the error response
        }
        $url = $this->baseUrl . '/payout/calculate/';
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/vnd.api+json',
        ])->post($url, $postData);

        // Handle the response
        if ($response->successful()) {
            return response()->success($response->json(), 'Payout Charges');
        } else {
            return response()->error('Failed to get payout charges', $response->json(), $response->status());
        }
    }
}
