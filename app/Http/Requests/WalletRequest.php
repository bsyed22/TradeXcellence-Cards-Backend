<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletRequest extends FormRequest
{
    public function authorize()
    {
        // Optionally, you can check if the user has the right permission to create a wallet
        return true; // Set to true for now
    }

    public function rules()
    {
        return [
            'balance' => 'nullable|numeric|min:0', // balance is optional but if provided, it should be numeric and >= 0
            'on_hold' => 'nullable|numeric|min:0', // on_hold is optional but if provided, it should be numeric and >= 0
            'type' => 'nullable|in:fiat,crypto', // type is optional but if provided, it must be either 'fiat' or 'crypto'
            'currency_id' => 'nullable|exists:currencies,id', // currency_id is optional but if provided, it must exist in the currencies table
        ];
    }

    public function messages()
    {
        return [
            'balance.required' => 'Balance is required.',
            'balance.numeric' => 'Balance must be a numeric value.',
            'on_hold.required' => 'On-hold balance is required.',
            'on_hold.numeric' => 'On-hold balance must be numeric.',
            'type.required' => 'Wallet type is required.',
            'type.in' => 'Wallet type must be either "fiat" or "crypto".',
            'currency_id.required' => 'Currency ID is required.',
            'currency_id.exists' => 'The selected currency does not exist.',
        ];
    }
}
