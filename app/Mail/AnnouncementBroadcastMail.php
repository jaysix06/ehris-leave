<?php

namespace App\Mail;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Announcement $announcement,
    ) {
    }

    public function envelope(): Envelope
    {
        $fromAddress = config('ehris.admin_email', config('mail.from.address'));
        $fromName = (string) config('ehris.admin_name', config('mail.from.name', 'eHRIS'));

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: '[eHRIS] '.$this->announcement->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.announcement-broadcast',
            with: [
                'announcement' => $this->announcement,
            ],
        );
    }
}

