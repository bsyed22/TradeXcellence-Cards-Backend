<?php

namespace App\Models;

use App\Models\CardHolderLink;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'alias',
        'fee',
        'card_id',
        'proof_image',
        'card_type',
        'txn_hash',
        'transaction_id',
        'callback_id',
        'notes',
        'status',
        'order_id',
        'transaction_fee_payer',
        'currency',
        'charge_type',
        'transaction_fee',
        'transaction_details',
        'merchant',
        'comments',
    ];

    protected $appends = ['proof_image_url'];

//    protected $casts = [
//        'transaction_id' => 'integer',
//    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function card()
    {
        return $this->belongsTo(CardHolderLink::class, 'card_id', 'card_id');
    }

    // Accessor for full image URL
    public function getProofImageUrlAttribute()
    {
        return $this->proof_image
            ? asset('storage/' . $this->proof_image)
            : null;
    }

}
