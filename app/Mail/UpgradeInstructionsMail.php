<?php

namespace App\Mail;

use App\Models\UpgradeRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the requesting user with full payment instructions.
 */
class UpgradeInstructionsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public UpgradeRequest $upgradeRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SchoolShare Upgrade Instructions — ' . $this->upgradeRequest->formattedPlan(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.upgrade-instructions',
        );
    }
}
