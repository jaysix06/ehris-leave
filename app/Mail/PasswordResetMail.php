<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name:string,login_email:string,temporary_password:string,sign_in_url:string}  $data
     */
    public function __construct(public array $data)
    {
    }

    public function envelope(): Envelope
    {
        $appName = (string) config('app.name', 'EHRIS');

        return new Envelope(
            subject: "{$appName} password reset (admin request)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
            text: 'emails.password-reset-text',
            with: [
                'appName' => (string) config('app.name', 'EHRIS'),
                'data' => $this->data,
            ],
        );
    }
}
