<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Transfer;
use App\Models\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransferControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test successful internal transfer.
     *
     * @return void
     */
    public function testSuccessfulInternalTransfer()
    {
        // Create test user
        $user = User::factory()->create();
        $currency = Currency::factory()->create(['code' => 'USD']);

        // Create wallets
        $fromWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 1000, 'currency_id' => $currency->id]);
        $toWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0, 'currency_id' => $currency->id]);

        // Authenticate user for the test
        $this->actingAs($user);

        // Set amount to transfer
        $amount = 100;

        // Convert currency using the real conversion function
        $convertedAmount = $this->convertCurrency($amount, $fromWallet->currency_id, 'USD');

        // Ensure conversion worked (avoid test failing due to API issues)
        $this->assertNotFalse($convertedAmount, 'Currency conversion failed');

        $data = [
            'from_account' => $fromWallet->id,
            'from_account_type' => 'internal',
            'from_currency' => 'USD',
            'conversion_rate' => $convertedAmount / $amount, // Store conversion rate dynamically
            'to_account' => $toWallet->id,
            'to_account_type' => 'internal',
            'to_currency' => 'USD',
            'amount' => $amount,
            'type' => 'transfer',
        ];

        $response = $this->postJson(route('transfers.store'), $data);
        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'message', 'data']);

        // Refresh wallet balances
        $fromWallet->refresh();
        $toWallet->refresh();

        // Assert that the balance changes correctly
        $this->assertEquals(1000 - $convertedAmount, $fromWallet->balance);
        $this->assertEquals($convertedAmount, $toWallet->balance);

        // Assert transfer record creation
        $this->assertDatabaseHas('transfers', [
            'from_account' => $fromWallet->id,
            'to_account' => $toWallet->id,
            'amount' => $convertedAmount, // Ensure stored amount matches converted value
        ]);
    }


    /**
     * Fetches conversion rate for a given currency pair (mock or implement actual logic)
     */
    private function convertCurrency($amount, $fromCurrencyId, $toCurrencyCode)
    {
        $fromCurrencyCode = Currency::where("id", $fromCurrencyId)->value("code");
        if ($fromCurrencyCode === null) {
            return false; // Return false instead of response()->error()
        }

        $apiKey = env('EXCHANGE_RATE_API_KEY');
        if (empty($apiKey)) {
            return false; // Return false instead of response()->error()
        }

        $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$fromCurrencyCode}/{$toCurrencyCode}";
        try {
            $response = Http::withOptions([
                'verify'=> false
            ])->get($url);
            if (!$response->successful()) {
                return false; // Return false instead of response()->error()
            }

            $data = $response->json();

            if ($data['result'] !== 'success') {
                return false; // Return false instead of response()->error()
            }
            return $amount * $data['conversion_rate'];
        } catch (\Exception $e) {
            Log::error('Exchange rate API exception: ' . $e->getMessage());
            return false; // Return false instead of response()->error()
        }
    }

    /**
     * Test insufficient balance error.
     *
     * @return void
     */
    public function testInsufficientBalanceError()
    {
        // Create test user (can use a factory)
        $user = User::factory()->create();

        // Create wallets (can use factories)
        $fromWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 50, 'currency_id' => 1]);
        $toWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0, 'currency_id' => 1]);

        // Authenticate user for the test
        $this->actingAs($user);

        $data = [
            'from_account' => $fromWallet->id,
            'from_account_type' => 'internal',
            'from_currency' => 'USD',
            'conversion_rate' => 1.0,
            'to_account' => $toWallet->id,
            'to_account_type' => 'internal',
            'to_currency' => 'USD',
            'amount' => 100,
            'type' => 'transfer',
        ];

        $response = $this->postJson(route('transfers.store'), $data);

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Insufficient balance in from wallet.']);

        // Assert that the balance did not change
        $this->assertEquals(50, $fromWallet->fresh()->balance);
        $this->assertEquals(0, $toWallet->fresh()->balance);

        // Assert that a transfer record is not created
        $this->assertDatabaseMissing('transfers', [
            'from_account' => $fromWallet->id,
            'to_account' => $toWallet->id,
            'amount' => 100,
        ]);
    }

    /**
     * Test invalid from_account_type error.
     *
     * @return void
     */
    public function testInvalidFromAccountTypeError()
    {
        // Create test user (can use a factory)
        $user = User::factory()->create();

        // Create wallets (can use factories)
        $fromWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 1000, 'currency_id' => 1]);
        $toWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0, 'currency_id' => 1]);

        // Authenticate user for the test
        $this->actingAs($user);

        $data = [
            'from_account' => $fromWallet->id,
            'from_account_type' => 'invalid', //Invalid data
            'from_currency' => 'USD',
            'conversion_rate' => 1.0,
            'to_account' => $toWallet->id,
            'to_account_type' => 'internal',
            'to_currency' => 'USD',
            'amount' => 100,
            'type' => 'transfer',
        ];

        $response = $this->postJson(route('transfers.store'), $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_account_type']);
    }

    /**
     * Test MT5 transfer (for now).
     *
     * @return void
     */
    public function testMT5Transfer()
    {
        // Create test user (can use a factory)
        $user = User::factory()->create();

        // Authenticate user for the test
        $this->actingAs($user);

        $data = [
            'from_account' => 123,  // Dummy value
            'from_account_type' => 'mt5',
            'from_currency' => 'USD',
            'conversion_rate' => 1.0,
            'to_account' => 456, // Dummy Value
            'to_account_type' => 'internal',
            'to_currency' => 'USD',
            'amount' => 100,
            'type' => 'transfer',
        ];

        $response = $this->postJson(route('transfers.store'), $data);

        $response->assertStatus(200) // Assuming the message on successfull
        ->assertJson(['success' => true, 'message' => 'MT5 transfer logic will be implemented later.']);
    }

    /**
     * Test Invalid wallet error
     * @return void
     */
    public function testInvalidWalletError() {
        // Create test user (can use a factory)
        $user = User::factory()->create();

        // Authenticate user for the test
        $this->actingAs($user);

        $data = [
            'from_account' => 999, // Invalid Wallet
            'from_account_type' => 'internal',
            'from_currency' => 'USD',
            'conversion_rate' => 1.0,
            'to_account' => 456, // Dummy Value
            'to_account_type' => 'internal',
            'to_currency' => 'USD',
            'amount' => 100,
            'type' => 'transfer',
        ];

        $response = $this->postJson(route('transfers.store'), $data);

        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'From wallet not found.']);
    }
    /**
     * Test currencyConversion if fail
     * @return void
     */
    public function testCurrencyConversionFails() {
        // Create test user (can use a factory)
        $user = User::factory()->create();
        // Create wallets (can use factories)
        $fromWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 1000, 'currency_id' => 1]);
        $toWallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0, 'currency_id' => 1]);


        //Set a wrong api Key so it fails
        putenv('EXCHANGE_RATE_API_KEY=WRONG_API_KEY');

        // Authenticate user for the test
        $this->actingAs($user);

        $data = [
            'from_account' => $fromWallet->id,
            'from_account_type' => 'internal',
            'from_currency' => 'USD',
            'conversion_rate' => 1.0,
            'to_account' => $toWallet->id,
            'to_account_type' => 'internal',
            'to_currency' => 'EUR',
            'amount' => 100,
            'type' => 'transfer',
        ];

        $response = $this->postJson(route('transfers.store'), $data);

        $response->assertStatus(500);

        //Make sure that it restores it
        putenv('EXCHANGE_RATE_API_KEY=' . config('services.exchangerateapi.key'));
    }
}
