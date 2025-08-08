@extends('emails.layout')

@section('content')
    @php
        $heading = 'New Deposit Request Submitted';
        $body = "
            <p>Dear {$deposit->user->name},,</p>

            <p>A new deposit request has been submitted with the following details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>User:</strong> {$deposit->user->name} ({$deposit->user->email})</li>
                <li><strong>Amount:</strong> \${$deposit->amount}</li>
                <li><strong>Card:</strong> {$deposit->card_id}</li>
                <li><strong>Status:</strong> {$deposit->status}</li>
                <li><strong>Submitted At:</strong> " . \Carbon\Carbon::parse($deposit->created_at)->format('F j, Y, g:i A') . "</li>

            </ul>
            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
