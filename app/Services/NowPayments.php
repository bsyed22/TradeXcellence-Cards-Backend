<?php

namespace App\Services;

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NowPayments
{
    protected $userEmail;
    protected $userPassword;
    protected $baseUrl;
    protected $apiKey;
    protected $publicKey;
    protected $callBackURL;

    protected $jwtToken;
    protected $credentialsLoaded = false;


    public function __construct()
    {
        // Initialize all properties to null
        $this->userEmail = null;
        $this->userPassword = null;
        $this->baseUrl = null;
        $this->apiKey = null;
        $this->publicKey = null;
        $this->callBackURL = null;
        $this->jwtToken = null;
        $this->credentialsLoaded = false;

    }

    protected function loadCredentials()
    {
        if ($this->credentialsLoaded) {
            return;
        }

        $paymentMethod = PaymentMethod::where('name', 'NowPayments')->where('is_active', true)->first();

        if (!$paymentMethod) {
            Log::warning('NowPayments PaymentMethod not found or is inactive.');
            $this->userEmail = null;
            $this->userPassword = null;
            $this->baseUrl = null;
            $this->apiKey = null;
            $this->publicKey = null;
            $this->callBackURL = null;
            $this->jwtToken = null;
            $this->credentialsLoaded = true;
            return;
        }

        $config = $paymentMethod->settings;

        $this->userEmail = $config['user_email'] ?? null;
        $this->userPassword = $config['user_password'] ?? null;
        $this->baseUrl = $config['api_url'] ?? null;
        $this->apiKey = $config['api_key'] ?? null;
        $this->publicKey = $config['public_key'] ?? null;
        $this->callBackURL = $config['callback_url'] ?? null;

        $this->credentialsLoaded = true;
    }


    public function generateJWTToken()
    {
        $this->loadCredentials();
        if (!$this->baseUrl) {
            Log::error('NowPayments base URL not configured.');
            return response()->error('NowPayments base URL not configured.');
        }

        $url = $this->baseUrl . '/auth';
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                "email" => $this->userEmail,
                "password" => $this->userPassword,
                //Hardcoded credentials will be removed after testing
                //"email" => "bsyed22@gmail.com",
                //"password" => 'Xc3$$Pr002@16'

            ]);

            // Handle the token generation
            if ($response->successful()) {
                $data = $response->json(); // Convert JSON response to an array
                $this->jwtToken = $data['token'] ?? null; // Ensure token is correctly extracted
                if ($this->jwtToken) {
                    return $this->jwtToken; // Return the JWT token as a string
                } else {
                    Log::error('Token not found in NowPayments response: ' . json_encode($data));
                    return response()->error('Token not found in response', $data);
                }
            } else {
                Log::error('Failed to retrieve token from NowPayments API: ' . $response->body());
                return response()->error('Failed to retrieve token', $response->json(), $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Exception during NowPayments token generation: ' . $e->getMessage());
            return response()->error('API request failed', ['exception' => $e->getMessage()]);
        }
    }


    /*
     * Initiate Deposit
     */
    public function deposit(array $postData)
    {
        $this->loadCredentials();
        if (!$this->apiKey) {
            Log::error('NowPayments API key not configured.');
            return response()->error('NowPayments API key not configured.');
        }


        $url = $this->baseUrl . '/invoice';
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($url, $postData);

            // Handle the response
            if ($response->successful()) {
                return response()->success($response->json(), 'Deposit initiated successfully');
            } else {
                Log::error('Failed to create NowPayments deposit: ' . $response->body());
                return response()->error('Failed to create deposit', $response->json(), $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Exception during NowPayments deposit creation: ' . $e->getMessage());
            return response()->error('API request failed', ['exception' => $e->getMessage()]);
        }
    }

    public function withdraw(array $postData)
    {
        $this->loadCredentials();

        try {
            // Fetch the JWT token by calling the generateJWTToken method
            $jwtToken = $this->generateJWTToken();

            if (!$jwtToken) {
                return response()->error('Unable to retrieve JWT token');
            }

            if (!$this->apiKey) {
                Log::error('NowPayments API key not configured.');
                return response()->error('NowPayments API key not configured.');
            }

            $url = $this->baseUrl . '/payout'; // Ensure correct API endpoint
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwtToken,
                'Content-Type' => 'application/json',
                'x-api-key' => $this->apiKey,
            ])->post($url, $postData);


            // Return JSON response, not a Laravel response object
            if ($response->successful()) {
                return response()->json(); // Return the successful response as JSON
            } else {
                Log::error('Failed to create NowPayments withdrawal: ' . $response->body());
                return response()->error('Failed to create withdrawal', $response->json(), $response->status());
            }
        } catch (\Exception $e) {
            Log::error('API request failed during NowPayments withdrawal: ' . $e->getMessage());
            return response()->error('API request failed', ['exception' => $e->getMessage()]);
        }
    }


    public function currencies()
    {
        $this->loadCredentials();
        if (!$this->apiKey) {
            Log::error('NowPayments API key not configured.');
            return response()->error('NowPayments API key not configured.');
        }

        $url = $this->baseUrl . '/merchant/coins';
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($url);


            // Handle the response
            if ($response->successful()) {
                return response()->success($response->json(), 'Currencies retrieved successfully');
            } else {
                Log::error('Failed to retrieve currencies from NowPayments API: ' . $response->body());
                return response()->error('Failed to retrieve currencies', $response->json(), $response->status());
            }
        } catch (\Exception $e) {
            Log::error('API request failed during NowPayments currency retrieval: ' . $e->getMessage());
            return response()->error('API request failed', ['exception' => $e->getMessage()]);
        }
    }

    public function payoutCharges(array $postData)
    {
        $this->loadCredentials();
        $jwtToken = $this->generateJWTToken();

        if (!$jwtToken) {
            return response()->error('Unable to retrieve JWT token');
        }

        $url = $this->baseUrl . '/payout/calculate/';
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $jwtToken,
                'Content-Type' => 'application/vnd.api+json',
            ])->post($url, $postData);

            // Handle the response
            if ($response->successful()) {
                return response()->success($response->json(), 'Payout Charges');
            } else {
                Log::error('Failed to get NowPayments payout charges: ' . $response->body());
                return response()->error('Failed to get payout charges', $response->json(), $response->status());
            }
        } catch (\Exception $e) {
            Log::error('API request failed during NowPayments payout charge calculation: ' . $e->getMessage());
            return response()->error('API request failed', ['exception' => $e->getMessage()]);
        }
    }

}
