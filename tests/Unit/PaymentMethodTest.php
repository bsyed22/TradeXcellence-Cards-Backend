<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_all_payment_methods()
    {
        PaymentMethod::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success', 'message', 'data' => [['id', 'name', 'image', 'is_active']]
            ]);
    }

    /** @test */
    public function it_can_create_a_payment_method()
    {
        $data = PaymentMethod::factory()->make()->toArray();

        // Ensure JSON fields are arrays
        $data['currency_lists'] = $data['currency_lists'] ?? [];
        $data['supported_currency'] = $data['supported_currency'] ?? [];
        $data['supported_country'] = $data['supported_country'] ?? [];
        $data['convert_rate'] = $data['convert_rate'] ?? [];
        $data['settings'] = $data['settings'] ?? [];

        $response = $this->postJson('/api/admin/payment-methods', $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Payment method created successfully.']);

        $this->assertDatabaseHas('payment_methods', ['name' => $data['name']]);
    }


    /** @test */
    public function it_can_show_a_payment_method()
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->getJson("/api/admin/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Payment method retrieved successfully.']);
    }

    /** @test */
    public function it_can_update_a_payment_method()
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $updateData = [
            'name' => 'Updated Payment Method',
            'image' => $paymentMethod->image,
            'minimum_amount' => $paymentMethod->minimum_amount,
            'maximum_amount' => $paymentMethod->maximum_amount,
            'deposit_fee_payer' => $paymentMethod->deposit_fee_payer,
            'withdraw_fee_payer' => $paymentMethod->withdraw_fee_payer,
            'withdraw_charge_type' => $paymentMethod->withdraw_charge_type,
            'deposit_charge_type' => $paymentMethod->deposit_charge_type,
            'deposit_fixed_charge' => $paymentMethod->deposit_fixed_charge,
            'deposit_percent_charge' => $paymentMethod->deposit_percent_charge,
            'withdraw_fixed_charge' => $paymentMethod->withdraw_fixed_charge,
            'withdraw_percent_charge' => $paymentMethod->withdraw_percent_charge,
            'duration' => $paymentMethod->duration,
            'currency_lists' => json_decode($paymentMethod->currency_lists, true) ?? [], // Convert to array
            'supported_currency' => json_decode($paymentMethod->supported_currency, true) ?? [],
            'supported_country' => json_decode($paymentMethod->supported_country, true) ?? [],
            'convert_rate' => json_decode($paymentMethod->convert_rate, true) ?? [],
            'is_automatic' => $paymentMethod->is_automatic,
            'settings' => json_decode($paymentMethod->settings, true) ?? [],
            'is_active' => $paymentMethod->is_active,
        ];

        $response = $this->putJson("/api/admin/payment-methods/{$paymentMethod->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Payment method updated successfully.']);

        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'name' => 'Updated Payment Method'
        ]);
    }



    /** @test */
    public function it_can_delete_a_payment_method()
    {
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->deleteJson("/api/admin/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Payment method deleted successfully.']);

        $this->assertDatabaseMissing('payment_methods', ['id' => $paymentMethod->id]);
    }
}
