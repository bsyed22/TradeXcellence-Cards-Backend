@extends('emails.layout')

@section('content')
    @php
        $heading = 'New Physical Card Request';
        $body = "
            <p>Dear Admin,</p>

            <p>A user has requested a new physical card. Here are the request details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Card Holder Name:</strong> {$card->card_holder_name}</li>
                <li><strong>Card Holder ID:</strong> {$card->card_holder_id}</li>
                <li><strong>Email:</strong> {$card->email}</li>
                <li><strong>Alias:</strong> {$card->alias}</li>
                <li><strong>Request Time:</strong> " . \Carbon\Carbon::parse($card->created_at)->format('F j, Y, g:i A') . "</li>
            </ul>

            <p>You may log in to the admin panel to review and process this request.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
