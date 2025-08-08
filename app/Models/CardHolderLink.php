<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardHolderLink extends Model
{
    protected $fillable = [
        'card_id',
        'card_holder_id',
        'card_number',
        'card_holder_name',
        'type',
        'fee_paid',
        'balance',
        'alias',
        'email',
        'status',
        'activated_at'
    ];

    protected $casts = [
        'fee_paid' => 'boolean',
        'activated_at' => 'datetime',
    ];
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class, 'card_holder_id', 'card_holder_id');
    }
}
