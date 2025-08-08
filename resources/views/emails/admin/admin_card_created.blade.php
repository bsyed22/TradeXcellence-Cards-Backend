@extends('emails.layout')

@section('content')
    @php
        $heading = 'New Card Created';
        $body = "
            <p>Dear Admin,</p>

            <p>A new card has been successfully created with the following details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Card Holder:</strong> {$card->card_holder_name}</li>
                <li><strong>Card Type:</strong> " . ucfirst($card->type) . "</li>
                <li><strong>Card ID:</strong> <strong>{$card->card_id}</strong></li>
                <li><strong>Card Last Four Digits:</strong> <strong>" . substr($card->card_number, -4) . "</strong></li>
                <li><strong>Created At:</strong> " . \Carbon\Carbon::parse($card->created_at)->format('F j, Y, g:i A'). "</li>

            </ul>

            <p>You may log in to the admin panel to review or manage this card as needed.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
