<?php

namespace App\Mail;

use App\Models\CardHolderLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserActivatePhysicalCard extends Mailable
{
    public $card;

    /**
     * Create a new message instance.
     */
    public function __construct(CardHolderLink $card)
    {
        $this->card = $card;
    }

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: 'Your Card Has Been Activated',
        );
    }

    public function content(): Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.user.user_activate_physical_card',
            with: ['card' => $this->card]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
