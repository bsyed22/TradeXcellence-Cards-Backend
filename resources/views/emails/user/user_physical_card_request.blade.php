@extends('emails.layout')

@section('content')
    @php
        $heading = 'Your Card Request Has Been Received';
        $body = "
            <p>Dear {$card->card_holder_name},</p>

            <p>We have received your request for a physical card with the alias <strong>{$card->alias}</strong>. Our team will review and process your request shortly.</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Card Holder ID:</strong> {$card->card_holder_id}</li>
                <li><strong>Email:</strong> {$card->email}</li>
                <li><strong>Request Time:</strong> " . \Carbon\Carbon::parse($card->created_at)->format('F j, Y, g:i A') . "</li>
            </ul>

            <p>You will receive a notification once your request is approved and the card is issued.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
