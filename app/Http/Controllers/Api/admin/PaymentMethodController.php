<?php

namespace App\Http\Controllers\Api\admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class PaymentMethodController extends Controller
{
    /**
     * Get all payment methods.
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::all();
        return response()->success($paymentMethods, 'Payment methods retrieved successfully.', 200);
    }

    /**
     * Store a new payment method.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:payment_methods',
            'minimum_amount' => 'required|numeric|min:0',
            'maximum_amount' => 'required|numeric|min:0',
            'deposit_fee_payer' => 'required|string',
            'withdraw_fee_payer' => 'required|string',
            'withdraw_charge_type' => 'required|string',
            'deposit_charge_type' => 'required|string',
            'deposit_fixed_charge' => 'required|numeric|min:0',
            'deposit_percent_charge' => 'required|numeric|min:0',
            'withdraw_fixed_charge' => 'required|numeric|min:0',
            'withdraw_percent_charge' => 'required|numeric|min:0',
            'duration' => 'required|string',
            'is_automatic' => 'required|boolean',
            'settings' => 'required|array',
            'is_active' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->error('Validation failed.', $validator->errors(), 422);
        }


        $validated = $validator->validated();

        // Apply conditional validation based on 'is_automatic'
        if ($validated['is_automatic'] == 1) {
            $validated += $request->validate([
                'amount_greater_than_equal' => 'required|numeric|min:0',
                'amount_less_than_equal' => 'required|numeric|min:0',
            ]);
        }


        $paymentMethod = PaymentMethod::create($validated);

        return response()->success($paymentMethod, 'Payment method created successfully.', 201);
    }


    /**
     * Get a specific payment method.
     */
    public function show($id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->error('Payment method not found.', null, 404);
        }

        return response()->success($paymentMethod, 'Payment method retrieved successfully.');
    }

    /**
     * Update an existing payment method.
     */
    public function update(Request $request, $id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->error('Payment method not found.', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            //'image' => 'required|string|nullable',
            'minimum_amount' => 'required|numeric|min:0',
            'maximum_amount' => 'required|numeric|min:0',
            'deposit_fee_payer' => 'required|string',
            'withdraw_fee_payer' => 'required|string',
            'withdraw_charge_type' => 'required|string',
            'deposit_charge_type' => 'required|string',
            'deposit_fixed_charge' => 'required|numeric|min:0',
            'deposit_percent_charge' => 'required|numeric|min:0',
            'withdraw_fixed_charge' => 'required|numeric|min:0',
            'withdraw_percent_charge' => 'required|numeric|min:0',
            'duration' => 'required|string',
            //'currency_lists' => 'required|array',
            'supported_currency' => 'required|array',
            'supported_country' => 'required|array',
            'convert_rate' => 'required|array',
            'is_automatic' => 'required|boolean',
            'settings' => 'required|array',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        // Apply conditional validation based on 'is_automatic'
        if ($validatedData['is_automatic']) { //Simplified boolean check
            $conditionalValidator = Validator::make($request->all(), [
                'amount_greater_than_equal' => 'required|numeric|min:0',
                'amount_less_than_equal' => 'required|numeric|min:0',
            ]);

            if ($conditionalValidator->fails()) {
                return response()->json(['errors' => $conditionalValidator->errors()], 422);
            }
            $validatedData = array_merge($validatedData, $conditionalValidator->validated());
        }

        try {
            $paymentMethod->update($validatedData);
            return response()->success($paymentMethod, 'Payment method updated successfully.');
        } catch (\Exception $e) {
            return response()->error('Failed to update payment method.', $e->getMessage(), 500);
        }
    }

    /**
     * Delete a payment method.
     */
    public function destroy($id)
    {
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->error('Payment method not found.', null, 404);
        }

        try {
            $paymentMethod->delete();
            return response()->success(null, 'Payment method deleted successfully.');
        } catch (\Exception $e) {
            return response()->error('Failed to delete payment method.', $e->getMessage(), 500);
        }
    }
}
