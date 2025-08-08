<?php

namespace App\Mail;

use App\Models\CardHolderLink;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserKYCLinkEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'Complete your KYC',
        );
    }

    public function content(): Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.user.user_kyc_link',
            with: ['user' => $this->user]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
