@extends('emails.layout')

@section('content')
    @php
        $heading = 'New User Registration';
        $body = "
            <p>Dear Admin,</p>

            <p>A new user has successfully registered on the platform. Below are the user details:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Name:</strong> {$user->name}</li>
                <li><strong>Email:</strong> {$user->email}</li>
                <li><strong>Registered At:</strong> ". \Carbon\Carbon::parse($user->created_at)->format('F j, Y, g:i A') ."</li>
            </ul>

            <p>You can review the user profile in the admin dashboard if necessary.</p>

            <p>Best regards,<br><strong> Team " . config('app.name') . " </strong></p>
        ";
    @endphp
@endsection
