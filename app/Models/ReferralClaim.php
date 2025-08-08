<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralClaim extends Model
{
    protected $fillable = [
        'user_id',
        'referral_program_id',
        'bonus_amount',
        'deposit_id',
    ];

    public function program()
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }
}
