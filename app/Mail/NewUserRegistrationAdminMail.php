<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserRegistrationAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name:string,email:string,hrid:int|null,district_id:string|int|null,district_name:string|null,station_id:string|int|null,station_name:string|null,requested_at:string}  $data
     */
    public function __construct(public array $data)
    {
    }

    public function envelope(): Envelope
    {
        $appName = (string) config('app.name', 'EHRIS');

        return new Envelope(
            subject: "New {$appName} user registration",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-user-registration-admin',
            text: 'emails.new-user-registration-admin-text',
            with: [
                'appName' => (string) config('app.name', 'EHRIS'),
                'data' => $this->data,
                'userListUrl' => url('/utilities/user-list'),
            ],
        );
    }
}

