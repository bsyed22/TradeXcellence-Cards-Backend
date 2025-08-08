<?php

namespace App\Mail;

use App\Models\CardHolderLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserCardCreated extends Mailable
{ public $card;

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
            subject: 'New Card Created',
        );
    }

    public function content(): Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.user.user_card_created',
            with: ['card' => $this->card]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
