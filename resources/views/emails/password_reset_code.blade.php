@extends('emails.layout')

@section('content')
    @php
        $heading = 'Password Reset Request';
        $body = "<p><strong>Dear Client,</strong></p>
                 <p>You have requested to reset your password.</p>
                 <p>Your reset code is: <strong>{$code}</strong></p>
                 <p>Please enter this code in the password reset form to proceed.</p>
                 <p>If you did not request a password reset, please ignore this email.</p>
                 <p>Thank you!</p>
                 <p>Sincerely,<br>VortexFX</p>";
    @endphp
@endsection
