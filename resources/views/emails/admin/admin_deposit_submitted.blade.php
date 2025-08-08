@extends('emails.layout')

@section('content')
    @php
        $heading = 'New Deposit Request';
        $body = "
            <p>Dear Admin,</p>

            <p>A new deposit request has been submitted with the following details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Amount:</strong> \${$deposit->amount}</li>
                <li><strong>Card ID:</strong> {$deposit->card_id}</li>
                <li><strong>Submitted By:</strong> {$deposit->user->name}</li>
                <li><strong>Transaction Hash:</strong> {$deposit->txn_hash}</li>
<li><strong>Submitted At:</strong> " . \Carbon\Carbon::parse($deposit->created_at)->format('F j, Y, g:i A') . "</li>

            </ul>

            <p>Please review the deposit in the admin panel and take the necessary action.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
