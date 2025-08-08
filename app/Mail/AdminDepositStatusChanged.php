<?php

namespace App\Mail;

use App\Models\Deposit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminDepositStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public $deposit;

    /**
     * Create a new message instance.
     */
    public function __construct(Deposit $deposit)
    {
        $this->deposit = $deposit;
    }

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'Deposit Status Updated'
        );
    }

    public function content(): Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.admin.admin_deposit_status_changed',
            with: ['withdrawal' => $this->deposit]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
