@extends('emails.layout')

@section('content')
    @php
        $heading = 'Deposit Status Updated';
        $body = "
            <p>Dear {$deposit->user->name},</p>

            <p>We would like to inform you that your deposit request has been <strong>{$deposit->status}</strong>. Below are the details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Amount:</strong> \${$deposit->amount}</li>
                <li><strong>Card:</strong> {$deposit->card_id}</li>
                <li><strong>Status:</strong> {$deposit->status}</li>
<li><strong>Updated At:</strong> " . \Carbon\Carbon::parse($deposit->updated_at)->format('F j, Y, g:i A') . "</li>
            </ul>

            <p>If you have any questions or concerns, please feel free to contact our support team.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
