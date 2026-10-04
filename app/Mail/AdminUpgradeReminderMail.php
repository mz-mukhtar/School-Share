<?php

namespace App\Mail;

use App\Models\UpgradeRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the admin (mahizeki037@gmail.com) as a reminder when a new upgrade is requested.
 */
class AdminUpgradeReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UpgradeRequest $upgradeRequest,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🔔 [SchoolShare Admin] New Upgrade Request — ' . $this->upgradeRequest->user->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-upgrade-reminder',
        );
    }
}
