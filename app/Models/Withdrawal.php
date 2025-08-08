<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'auto_approve',
        'card_id',
        'wallet_address',
        'blockchain',
        'txn_hash',
        'transaction_id',
        'notes',
        'status',
        'approved_by',
        'payout_batch_id',
        'transaction_fee_payer',
        'currency',
        'charge_type',
        'transaction_fee',
        'transaction_details',
        'merchant',
        'request_data',
        'comments',
    ];


    protected $casts = [
        'transaction_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function card()
    {
        return $this->belongsTo(CardHolderLink::class, 'card_id', 'card_id');
    }
}
