<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use \Exception;

class SettingsService
{
    private array $settings;
    private string $appEnvironment;

    // Card API Settings - now nullable where appropriate
    private ?string $cardApiUrl;
    private ?string $apiAuthUsername;
    private ?string $apiAuthPassword;
    private ?string $accountId;
    private ?string $minLoadAmount;
    private ?string $maxLoadAmount;
    private ?string $maxVirtualCards;
    private ?string $maxPhysicalCards;
    private ?string $vortexApiUrl;

    // Virtual Card Specific
    private ?string $virtualWalletId;
    private ?string $virtualServiceId;
    private ?string $virtualCardFee;

    // Physical Card Specific
    private ?string $physicalWalletId;
    private ?string $physicalServiceId;
    private ?string $physicalCardFee;


    public function __construct()
    {
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $this->settings = Setting::pluck('value', 'key')->all();

        $this->appEnvironment = $this->get('APP_ENVIRONMENT', 'SANDBOX');

        // Determine prefix based on environment: 'LIVE_' or 'SANDBOX_'
        $prefix = strtoupper($this->appEnvironment) . '_';

        // Load common settings
        $this->cardApiUrl = $this->get($prefix . 'STRADACARTE_API_URL');
        $this->apiAuthUsername = $this->get($prefix . 'API_AUTH_USERNAME');
        $this->apiAuthPassword = $this->get($prefix . 'API_AUTH_PASSWORD');
        $this->accountId = $this->get($prefix . 'ACCOUNT_ID');
        $this->minLoadAmount = $this->get($prefix . 'MIN_LOAD_AMOUNT');
        $this->maxLoadAmount = $this->get($prefix . 'MAX_LOAD_AMOUNT');
        $this->maxVirtualCards = $this->get($prefix . 'MAX_VIRTUAL_CARDS');
        $this->maxPhysicalCards = $this->get($prefix . 'MAX_PHYSICAL_CARDS');
        $this->vortexApiUrl = $this->get($prefix . 'VORTEX_API_URL');

        // Load virtual card settings
        $this->virtualWalletId = $this->get($prefix . 'VIRTUAL_WALLET_ID');
        $this->virtualServiceId = $this->get($prefix . 'VIRTUAL_SERVICE_ID');
        $this->virtualCardFee = $this->get($prefix . 'VIRTUAL_CARD_FEE');

        // Load physical card settings
        $this->physicalWalletId = $this->get($prefix . 'PHYSICAL_WALLET_ID');
        $this->physicalServiceId = $this->get($prefix . 'PHYSICAL_SERVICE_ID');
        $this->physicalCardFee = $this->get($prefix . 'PHYSICAL_CARD_FEE');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    // --- Accessor Methods (with updated nullable return types) ---
    public function getCardApiUrl(): ?string
    {
        return $this->cardApiUrl;
    }

    public function getApiAuthUsername(): ?string
    {
        return $this->apiAuthUsername;
    }

    public function getApiAuthPassword(): ?string
    {
        return $this->apiAuthPassword;
    }

    public function getAccountId()
    {
        return $this->accountId;
    }

    public function getVortexApiUrl(): ?string
    {
        return $this->vortexApiUrl;
    }

    public function getVirtualWalletId()
    {
        return $this->virtualWalletId;
    }

    public function getVirtualServiceId()
    {
        return $this->virtualServiceId;
    }

    public function getVirtualCardFee()
    {
        return $this->virtualCardFee;
    }

    public function getPhysicalWalletId()
    {
        return $this->physicalWalletId;
    }

    public function getPhysicalServiceId()
    {
        return $this->physicalServiceId;
    }

    public function getPhysicalCardFee()
    {
        return $this->physicalCardFee;
    }

    private function guardRequiredSettings(string $cardType): void
    {
        $walletId = ($cardType === 'virtual') ? $this->getVirtualWalletId() : $this->getPhysicalWalletId();
        $serviceId = ($cardType === 'virtual') ? $this->getVirtualServiceId() : $this->getPhysicalServiceId();

        if (!$this->getCardApiUrl() || !$this->getApiAuthUsername() || !$this->getApiAuthPassword() || !$this->getAccountId() || !$walletId || !$serviceId) {
            Log::error('Card API settings are incomplete.', ['env' => $this->appEnvironment]);
            throw new Exception("Card provider settings are incomplete. Please check the configuration.");
        }
    }

    public function createCard(string $email, string $alias, int $cardHolderId, string $cardType)
    {
        try {
            // 1. Ensure all required settings are available before making the call.
            $this->guardRequiredSettings($cardType);

            // 2. Determine Wallet and Service ID based on the requested card type.
            $walletId = ($cardType === 'virtual') ? $this->getVirtualWalletId() : $this->getPhysicalWalletId();
            $serviceId = ($cardType === 'virtual') ? $this->getVirtualServiceId() : $this->getPhysicalServiceId();

            // 3. Construct the full API endpoint URL.
            $url = "{$this->getCardApiUrl()}/api/ServiceProvider_43/CreateNewCard/{$this->getAccountId()}/{$walletId}/{$serviceId}/{$cardHolderId}";

            // 4. Prepare the request payload.
            $payload = [
                'email' => $email,
                'alias' => $alias,
            ];

            // 5. Make the API call with authentication headers.
            $response = Http::withHeaders([
                'accept' => 'text/plain',
                'ApiAuthUsername' => $this->getApiAuthUsername(),
                'ApiAuthPassword' => $this->getApiAuthPassword(),
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->post($url, $payload);


            // 7. Handle the response based on its status.
            if ($response->successful()) {
                return response()->success(['raw' => $response->json()], 'Card created successfully');
            }

            // Return a standardized error JSON response.
            return response()->error('Card creation failed', [
                'status' => $response->status(),
                'body' => $response->json()
            ], $response->status());

        } catch (Exception $e) {
            return response()->error('An exception occurred during card creation: ' . $e->getMessage(), null, 500);
        }
    }

    public function getCardDetails(string $cardType,int $cardId)
    {
        $walletId=null;
        $accountID = $this->getAccountId();

        if($cardType=="virtual")
        {
            $walletId = $this->getVirtualWalletId();
        }else{
            $walletId = $this->getPhysicalWalletId();
        }

        try {
            $url = "{$this->getCardApiUrl()}/api/ServiceProvider_43/ViewCardDetails/{$accountID}/{$walletId}/{$cardId}";


            $response = Http::withHeaders([
                'accept' => 'text/plain',
                'ApiAuthUsername' => $this->getApiAuthUsername(),
                'ApiAuthPassword' => $this->getApiAuthPassword(),
            ])->withoutVerifying()->get($url); // <-- CHANGE THIS TO GET

            if ($response->successful()) {
                return response()->success($response->json(), "Card details fetched successfully", 200);
            }

            return response()->error('Failed to fetch card details', [
                'status' => $response->status(),
                'body' => $response->json(),
            ], $response->status());

        } catch (Exception $e) {
            return response()->error('Exception while fetching card details: ' . $e->getMessage(), null, 500);
        }
    }

    public function loadCard(float $amount, int $cardHolderId, int $cardId, string $cardType)
    {
        try {


            $this->guardRequiredSettings($cardType);

            // 2. Determine Wallet and Service ID based on the requested card type.
            $walletId = ($cardType === 'virtual') ? $this->getVirtualWalletId() : $this->getPhysicalWalletId();
            $serviceId = ($cardType === 'virtual') ? $this->getVirtualServiceId() : $this->getPhysicalServiceId();
            $accountId= $this->getAccountId();

            $url = "{$this->getCardApiUrl()}/api/ServiceProvider_43/LoadCard/{$accountId}/{$walletId}/{$cardHolderId}/{$cardId}";

            $payload = [
                'amount' => $amount*100,
                'description' => "Card payment",
            ];

            $response = Http::withHeaders([
                'accept' => 'text/plain',
                'ApiAuthUsername' => $this->getApiAuthUsername(),
                'ApiAuthPassword' => $this->getApiAuthPassword(),
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->post($url, $payload);


            if ($response->successful()) {

                return response()->success($response->json(), "Card deposit successful", 200);
            }


            return response()->error('Card deposit unsuccessful', [
                'status' => $response->status(),
                'body' => $response->json(),
            ], $response->status());


        } catch (Exception $e) {

            return response()->error('An exception occurred during card deposit: ' . $e->getMessage(),$e->getMessage(),$e->getCode());
        }
    }


    public function getCardTransactions(int $cardHolderId, int $cardId, string $startDate, string $endDate)
    {
        try {

            $url = "{$this->getCardApiUrl()}/api/ServiceProvider_43/ViewCardTransactions/{$this->getAccountId()}/{$cardHolderId}/{$cardId}?StartDate={$startDate}&EndDate={$endDate}";

            $response = Http::withHeaders([
                'accept' => 'text/plain',
                'ApiAuthUsername' => $this->getApiAuthUsername(),
                'ApiAuthPassword' => $this->getApiAuthPassword(),
            ])->withoutVerifying()->get($url);

            if ($response->successful()) {
                return response()->success($response->json(), "Card transactions fetched successfully", 200);
            }

            return response()->error('Card transactions fetch 102100failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ], $response->status());

        } catch (Exception $e) {
            return response()->error('Exception during transaction fetch: ' . $e->getMessage(), $e->getMessage(), $e->getCode());
        }
    }

}
