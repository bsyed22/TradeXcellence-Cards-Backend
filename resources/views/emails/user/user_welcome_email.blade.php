@extends('emails.layout')

@section('content')
    @php
        $heading = 'Welcome Aboard!';
        $body = "
            <p><strong>Dear {$user->name},</strong></p>

            <p>Welcome to BlackDuck! We're excited to have you with us.</p>

            <p>We are committed to providing you with the best experience possible. If you have any questions or need assistance getting started, feel free to reach out to our support team.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
