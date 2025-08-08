@extends('emails.layout')

@section('content')
    @php
        $heading = 'Your Card Has Been Activated';
        $body = "
            <p>Dear {$card->card_holder_name},</p>

            <p>We’re happy to inform you that your physical card has been <strong>successfully activated</strong>.</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Card Type:</strong> " . ucfirst($card->type) . "</li>
                <li><strong>Card Alias:</strong> {$card->alias}</li>
                <li><strong>Card ID:</strong> {$card->card_id}</li>
                <li><strong>Activated At:</strong> " . \Carbon\Carbon::parse($card->activated_at)->format('F j, Y, g:i A') . "</li>
            </ul>

            <p>You can now use your card as per the applicable terms and limits.</p>

            <p>If you did not perform this action or have any concerns, please contact our support team immediately.</p>

            <p>Best regards,<br><strong> Team " . .config('app.name') . "</strong></p>
        ";
    @endphp
@endsection
