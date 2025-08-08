@extends('emails.layout')

@section('content')
    @php
        $heading = 'User Withdrawal Status Updated';
        $body = "
            <p>Dear Admin,</p>

            <p>The withdrawal request has been updated with the following details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>User:</strong> {$withdrawal->user->name} ({$withdrawal->user->email})</li>
                <li><strong>Amount:</strong> \${$withdrawal->amount}</li>
                <li><strong>Status:</strong> <strong>{$withdrawal->status}</strong></li>
<li><strong>Requested At:</strong> " . \Carbon\Carbon::parse($withdrawal->created_at)->format('F j, Y, g:i A') . "</li>

            </ul>

            <p>Please log in to the admin dashboard to review or take further action if necessary.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
