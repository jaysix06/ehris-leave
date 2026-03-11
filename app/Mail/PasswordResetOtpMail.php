<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $otp,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = (string) config('app.name', 'EHRIS');

        return new Envelope(
            subject: "{$appName} password reset OTP",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $appName = (string) config('app.name', 'EHRIS');

        return new Content(
            view: 'emails.password-reset-otp',
            with: [
                'appName' => $appName,
                'data' => [
                    'otp' => $this->otp,
                    'expires_in' => '10 minutes',
                    'sign_in_url' => url('/login'),
                ],
            ],
        );
    }
}
