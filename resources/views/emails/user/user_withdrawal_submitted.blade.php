@extends('emails.layout')

@section('content')
    @php
        $heading = 'Withdrawal Request Submitted';
        $body = "
            <p>Dear {$withdrawal->user->name},</p>

            <p>We have successfully received your withdrawal request. Below are the details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Amount:</strong> \${$withdrawal->amount}</li>
                <li><strong>Wallet Address:</strong> {$withdrawal->wallet_address}</li>
                <li><strong>Blockchain:</strong> {$withdrawal->blockchain}</li>


<li><strong>Requested At:</strong> " . \Carbon\Carbon::parse($withdrawal->created_at)->format('F j, Y, g:i A') . "</li>

            </ul>

            <p>Your request is currently being processed. You will receive your funds shortly. If you have any questions, please contact our support team.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
