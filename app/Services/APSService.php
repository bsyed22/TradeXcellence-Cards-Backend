<?php

namespace App\Services;

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class APSService
{
    protected $baseUrl;
    protected $depositAppKey;
    protected $depositMerchant;
    protected $depositAppSecret;
    protected $withdrawMerchant;
    protected $withdrawAppKey;
    protected $withdrawAppSecret;
    protected $deposit_guid;
    protected $withdraw_guid;
    protected $credentialsLoaded = false;

    public function __construct()
    {
        // Initialize properties to null.  Do NOT load credentials here!
        $this->baseUrl = null;
        $this->depositAppKey = null;
        $this->depositMerchant = null;
        $this->depositAppSecret = null;
        $this->withdrawMerchant = null;
        $this->withdrawAppKey = null;
        $this->withdrawAppSecret = null;
        $this->deposit_guid = null;
        $this->withdraw_guid = null;
        $this->credentialsLoaded = false;
    }

    protected function loadCredentials()
    {
        if ($this->credentialsLoaded) {
            return;
        }

        $paymentMethod = PaymentMethod::where('name', 'APS')->where('is_active', true)->first();

        if (!$paymentMethod) {
            $this->baseUrl = config('aps.sandbox_url', '');
            $this->depositMerchant = null;
            $this->withdrawMerchant = null;
            $this->deposit_guid = null;
            $this->withdraw_guid = null;
            $this->depositAppKey = null;
            $this->depositAppSecret = null;
            $this->withdrawAppKey = null;
            $this->withdrawAppSecret = null;
            $this->credentialsLoaded = true;
            return;
        }

        $config = $paymentMethod->settings;

        $this->baseUrl = ($config['mode'] ?? 'sandbox') === 'sandbox'
            ? $config['sandbox_url']
            : $config['live_url'];
        $this->depositMerchant = $config['deposit_merchant_guid'];
        $this->withdrawMerchant = $config['withdraw_merchant_guid'];
        $this->deposit_guid = $config['deposit_guid'];
        $this->withdraw_guid = $config['withdraw_guid'];
        $this->depositAppKey = $config['deposit_app_token'];
        $this->depositAppSecret = $config['deposit_app_secret'];
        $this->withdrawAppKey = $config['withdraw_app_token'];
        $this->withdrawAppSecret = $config['withdraw_app_secret'];
        $this->credentialsLoaded = true;
    }

    /*
     *Initiate Payment
     */
    public function initiatePayment(float $amount, array $depositData)
    {
        $this->loadCredentials();
        try {
            $url = "{$this->baseUrl}/{$this->depositMerchant}/transactions";

            if (!$this->baseUrl || !$this->depositMerchant) {
                return response()->error('APS credentials not configured');
            }

            $payload = [
                "amount" => $amount,
                "fields" => [
                    "transaction" => [
                        "deposit_method" => $this->deposit_guid,
                        "deposit" => $depositData
                    ]
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-App-Token: ' . $this->depositAppKey,
                'X-App-Secret: ' . $this->depositAppSecret,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decodedResponse = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return response()->success($decodedResponse, 'Payment initiated successfully');
            }

            return response()->error('Failed to initiate payment', $decodedResponse, $httpCode);
        } catch (\Exception $e) {
            return response()->error('Exception occurred', $e->getMessage());
        }
    }

    /*
     * Initiate Withdrawl
     */

    public function initiateWithdrawal(float $amount, array $withdrawalData)
    {
        $this->loadCredentials();
        try {
            $url = "{$this->baseUrl}/{$this->withdrawMerchant}/transactions";
            if (!$this->baseUrl || !$this->withdrawMerchant) {
                return [
                    'success' => false,
                    'error' => 'APS credentials not configured',
                ];
            }
            $payload = [
                "amount" => $amount,
                "fields" => [
                    "transaction" => [
                        "remit_method" => $this->withdraw_guid, // Assuming this is the withdrawal method ID
                        "remit" => $withdrawalData
                    ]
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-App-Token: ' . $this->withdrawAppKey,
                'X-App-Secret: ' . $this->withdrawAppSecret,
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decodedResponse = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'data' => $decodedResponse
                ];
            }

            return [
                'success' => false,
                'error' => $decodedResponse
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    /*
     *
     */
    public function getTransactionDetails(string $transactionId)
    {
        $this->loadCredentials();
        try {
            $url = "{$this->baseUrl}/{$this->withdrawMerchant}/{$transactionId}";
            if (!$this->baseUrl || !$this->withdrawMerchant) {
                return response()->error('APS credentials not configured');
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-App-Token: ' . $this->withdrawAppKey,
                'X-App-Secret: ' . $this->withdrawAppSecret,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decodedResponse = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return response()->success($decodedResponse, 'Transaction details fetched successfully');
            }

            return response()->error('Failed to fetch transaction details', $decodedResponse, $httpCode);
        } catch (\Exception $e) {
            return response()->error('Exception occurred', $e->getMessage());
        }
    }

    /*
     * Get Merchant Account Info
     */
    public function getInfo(string $merchatGUID)
    {
        $this->loadCredentials();
        try {
            $url = "{$this->baseUrl}/{$merchatGUID}/info";

            if (!$this->baseUrl || !$merchatGUID) {
                return response()->error('APS credentials not configured');
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-App-Token: ' . $this->withdrawAppKey,
                'X-App-Secret: ' . $this->withdrawAppSecret,
//                'X-App-Token: ' . $this->depositAppKey,
//                'X-App-Secret: ' . $this->depositAppSecret,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decodedResponse = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                return response()->success($decodedResponse, 'Merchant details fetched successfully');
            }

            return response()->error('Failed to fetch merchant details', $decodedResponse, $httpCode);
        } catch (\Exception $e) {
            return response()->error('Exception occurred', $e->getMessage());
        }
    }
}
