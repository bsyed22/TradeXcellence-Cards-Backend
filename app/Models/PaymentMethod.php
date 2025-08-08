<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $guarded = [];

    protected $casts = [
        'convert_rate' => 'array',
        'settings' => 'array',
        'is_automatic' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d h:i A'); // US format
    }
    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d h:i A'); // US format
    }
}
