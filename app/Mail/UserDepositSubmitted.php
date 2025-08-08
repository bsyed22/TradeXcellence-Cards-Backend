<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Deposit;

class UserDepositSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $deposit;

    public function __construct(Deposit $deposit)
    {
        $this->deposit = $deposit;
    }

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'Your Deposit Request Submitted'
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.user.user_deposit_submitted',
            with: ['deposit' => $this->deposit]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

