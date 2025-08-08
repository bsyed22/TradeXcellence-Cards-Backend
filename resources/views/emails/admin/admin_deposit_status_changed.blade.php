@extends('emails.layout')

@section('content')
    @php
        $heading = 'Deposit Status Updated';
        $body = "
            <p>Dear Admin,</p>

            <p>This is to inform you that the status of a deposit request has been updated. The details are as follows:</p>

            <ul style='margin: 16px 0; padding-left: 20px;'>
                <li><strong>Deposit ID:</strong> {$deposit->id}</li>
                <li><strong>User:</strong> {$deposit->user->name} ({$deposit->user->email})</li>
                <li><strong>Amount:</strong> \$" . number_format($deposit->amount, 2) . "</li>
                <li><strong>Card ID:</strong> {$deposit->card_id}</li>
                <li><strong>Status:</strong> <strong>" . ucfirst($deposit->status) . "</strong></li>
                <li><strong>Submitted At:</strong> " . \Carbon\Carbon::parse($deposit->created_at)->format('F j, Y, g:i A') . "</li>
            </ul>

            <p>Please log in to the admin panel to view more details or take necessary action.</p>

            <p>Regards,<br><strong> Team " . config('app.name') . "</strong></p>
        ";
    @endphp
@endsection
