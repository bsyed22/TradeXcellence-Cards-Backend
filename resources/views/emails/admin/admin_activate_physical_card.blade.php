@extends('emails.layout')

@section('content')
    @php
        $heading = 'Card Activated by User';
        $body = "
            <p>Dear Admin,</p>

            <p>The following card has been successfully <strong>activated</strong> by the user:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Card Holder:</strong> {$card->card_holder_name}</li>
                <li><strong>Card Type:</strong> " . ucfirst($card->type) . "</li>
                <li><strong>Card Alias:</strong> {$card->alias}</li>
                <li><strong>Card ID:</strong> {$card->card_id}</li>
                <li><strong>Activated At:</strong> " . \Carbon\Carbon::parse($card->activated_at)->format('F j, Y, g:i A') . "</li>
            </ul>

            <p>You can log in to the admin panel to view or manage this card if needed.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
