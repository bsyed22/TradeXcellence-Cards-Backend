@extends('emails.layout')

@section('content')
    @php
        $heading = 'New Withdrawal Request';
        $body = "
            <p>Dear Admin,</p>

            <p>A new withdrawal request has been submitted with the following details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>User:</strong> {$withdrawal->user->name} ({$withdrawal->user->email})</li>
                <li><strong>Amount:</strong> \${$withdrawal->amount}</li>
                <li><strong>Wallet Address:</strong> {$withdrawal->wallet_address}</li>
                <li><strong>Blockchain:</strong> {$withdrawal->blockchain}</li>

<li><strong>Requested At:</strong> " . \Carbon\Carbon::parse($withdrawal->created_at)->format('F j, Y, g:i A') . "</li>

            </ul>

            <p>Please log in to the admin panel to review and process the request.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
