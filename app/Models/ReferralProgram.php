<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralProgram extends Model
{
    protected $fillable = [
        'program_name',
        'program_type',
        'bonus_type',
        'bonus_amount',
        'bonus_validity_type',
        'expires_at',
        'max_claims',
    ];

    public function claims()
    {
        return $this->hasMany(ReferralClaim::class);
    }
}
