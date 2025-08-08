<?php

namespace App\Mail;

use App\Models\CardHolderLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminPhysicalCardRequest extends Mailable
{
    use Queueable, SerializesModels;

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
            subject: 'New Physical Card Request',
        );
    }

    public function content(): Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            view: 'emails.admin.admin_physical_card_request',
            with: ['card' => $this->card]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
