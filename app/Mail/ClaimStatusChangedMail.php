<?php

namespace App\Mail;

use App\Models\Claim;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClaimStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Claim $claim;
    public string $previousStatus;
    public string $newStatus;

    /**
     * Create a new message instance.
     */
    public function __construct(Claim $claim, string $previousStatus, string $newStatus)
    {
        $this->claim          = $claim;
        $this->previousStatus = $previousStatus;
        $this->newStatus      = $newStatus;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Claim Status Has Been Updated — ' . $this->claim->claim_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.claim-status-changed',
        );
    }
}
