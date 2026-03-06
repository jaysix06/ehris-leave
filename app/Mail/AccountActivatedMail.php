<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name:string,official_email:string,default_password:string,hrid:int|null,activated_at:string,sign_in_url:string}  $data
     */
    public function __construct(public array $data)
    {
    }

    public function envelope(): Envelope
    {
        $appName = (string) config('app.name', 'EHRIS');

        return new Envelope(
            subject: "{$appName} account activated",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-activated',
            text: 'emails.account-activated-text',
            with: [
                'appName' => (string) config('app.name', 'EHRIS'),
                'data' => $this->data,
            ],
        );
    }
}

