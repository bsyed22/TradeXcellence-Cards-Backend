<?php
//
//namespace App\Mail;
//
//use Illuminate\Bus\Queueable;
//use Illuminate\Mail\Mailable;
//use Illuminate\Queue\SerializesModels;
//
//class SendPasswordResetCode extends Mailable
//{
//    use Queueable, SerializesModels;
//
//    public $resetCode;
//
//    public function __construct($resetCode)
//    {
//        $this->resetCode = $resetCode;
//    }
//
//    public function build()
//    {
//        return $this->subject('Your Password Reset Code')
//            ->view('emails.password_reset_code')
//            ->with(['code' => $this->resetCode]);
//    }
//}


namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class SendPasswordResetCode extends Mailable
{
    use Queueable, SerializesModels;

    public $resetCode;

    /**
     * Create a new message instance.
     *
     * @param string $resetCode
     * @param User|null $user
     * @return void
     */
    public function __construct(string $resetCode)
    {
        $this->resetCode = $resetCode;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset Request',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.password_reset_code',
            with: [
                'code' => $this->resetCode,
            ],
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
