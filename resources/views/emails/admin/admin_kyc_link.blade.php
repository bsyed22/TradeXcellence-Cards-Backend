@extends('emails.layout')

@section('content')
    @php
        $heading = 'New KYC Verification Link for User';
        $body = "
            <p>Dear Admin,</p>

            <p>A new KYC verification link has been generated with the following details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Name:</strong> {$user->name}</li>
                <li><strong>Email:</strong> {$user->email}</li>
                <li><strong>KYC Verification Link:</strong> <a href='{$user->kyc_link}' target='_blank'>{$user->kyc_link}</a></li>
            </ul>

            <p>Please log in to the admin panel to review or manage this user's KYC process as necessary.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
