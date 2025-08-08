<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasRoles, HasFactory, Notifiable, HasApiTokens;

    protected $appends = ['full_phone'];

    protected $fillable = [
        'name',
        'email',
        'card_holder_id',
        'physical_card_holder_id',
        'kyc_link',
        'role',
        'password',
        'first_name',
        'referral_code',
        'referral_program',
        'referred_by',
        'last_name',
        'status',
        'kyc_verified',
        'email_verification',
        'email_verified_at',
        'email_verification_code',
        'email_verification_code_expires_at'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //Static Function to return User By Token
    public static function findByToken($token)
    {
        if (!$token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        return $accessToken ? $accessToken->tokenable : null;
    }

    public function referralRequest() {
        return $this->hasOne(ReferralRequest::class);
    }

    public function isIB(): bool {
        return !is_null($this->referral_code);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d h:i A'); // US format
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d h:i A'); // US format
    }


    public function getFullPhoneAttribute()
    {
        return $this->phone_code.$this->phone_number;
    }
}
