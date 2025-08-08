<?php

// app/Models/ReferralRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReferralRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'admin_comment',
        'reviewed_at',
        'referral_program_id',
        'reviewed_by',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function reviewer() {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function referralProgram()
    {
        return $this->belongsTo(ReferralProgram::class);
    }
}
