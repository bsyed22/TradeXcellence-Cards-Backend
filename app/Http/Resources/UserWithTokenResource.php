<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWithTokenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email_verification' => $this->email_verification,
            'sms_verification' => $this->sms_verification,
            'country' => $this->country,
            'phone_number' => $this->phone_number,
            'token' => $this->token, // Include token here
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
