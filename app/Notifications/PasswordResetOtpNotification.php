<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    public function __construct(
        private readonly string $otp
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = (string) config('app.name', 'EHRIS');

        return (new MailMessage)
            ->subject("{$appName} password reset OTP")
            ->view('emails.password-reset-otp', [
                'appName' => $appName,
                'data' => [
                    'otp' => $this->otp,
                    'expires_in' => '10 minutes',
                    'sign_in_url' => url('/login'),
                ],
            ])
            ->text('emails.password-reset-otp-text', [
                'appName' => $appName,
                'data' => [
                    'otp' => $this->otp,
                    'expires_in' => '10 minutes',
                    'sign_in_url' => url('/login'),
                ],
            ]);
    }
}
