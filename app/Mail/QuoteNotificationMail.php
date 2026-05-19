<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Quote $quote;
    public string $eventType; // 'created' or 'approved'

    /**
     * Create a new message instance.
     */
    public function __construct(Quote $quote, string $eventType)
    {
        $this->quote     = $quote;
        $this->eventType = $eventType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->eventType === 'approved'
            ? 'Great News! Your Quote Has Been Approved — ' . $this->quote->quote_number
            : 'Your Insurance Quote Has Been Created — ' . $this->quote->quote_number;

        return new Envelope(subject: $subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-notification',
        );
    }
}
