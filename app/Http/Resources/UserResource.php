<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sumsub_id' => $this->sumsub_id,
            'sumsub_applicant_id' => $this->sumsub_applicant_id,
            'name' => $this->name,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'country' => $this->country,
            'phone_code' => $this->phone_code,
            'phone_number' => $this->phone_number,
            'token' => $this->token,
            'email_verification_code' => $this->email_verification_code,
            'email_verification_code_expires_at' => $this->email_verification_code_expires_at,
            'sms_verification_code' => $this->sms_verification_code,
            'sms_verification_code_expires_at' => $this->sms_verification_code_expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
