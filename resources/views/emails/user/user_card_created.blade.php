@extends('emails.layout')

@section('content')
    @php
        $heading = 'New Card Created';
        $body = "
            <p>Dear {$card->card_holder_name},</p>

            <p>We are pleased to inform you that your new <strong>{$card->type}</strong> card has been successfully created.</p>

            <p><strong>Card ID:</strong> {$card->card_id}</p>

            <p><strong>Card Last Four Digits:</strong> " . substr($card->card_number, -4) . "</p>

            <p>If you did not authorize this action or if you have any concerns, please contact our support team immediately.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
