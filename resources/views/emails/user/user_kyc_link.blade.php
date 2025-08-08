@extends('emails.layout')

@section('content')
    @php
        $heading = 'KYC Verification';
        $body = "
            <p>Dear {$user->name},</p>

            <p>A KYC verification link has been generated with the following details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>KYC Verification Link:</strong> <a href='{$user->kyc_link}' target='_blank'>Click here to verify</a></li>
            </ul>

            <p>Please complete the verification process by clicking the link above. If you did not request this, contact our support team immediately.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
