@extends('emails.layout')

@section('content')
    @php
        $heading = 'Withdrawal Status Updated';
        $body = "
            <p>Dear {$withdrawal->user->name},</p>

            <p>We would like to inform you that the status of your withdrawal request has been updated. Below are the details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Withdrawal ID:</strong> {$withdrawal->id}</li>
                <li><strong>Amount:</strong> \${$withdrawal->amount}</li>
                <li><strong>Status:</strong> <strong>{$withdrawal->status}</strong></li>

<li><strong>Requested At:</strong> " . \Carbon\Carbon::parse($withdrawal->created_at)->format('F j, Y, g:i A') . "</li>

            </ul>

            <p>If you have any questions or need further assistance, please contact our support team.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
