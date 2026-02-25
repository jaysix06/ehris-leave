<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    use Queueable;

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
        return (new MailMessage)
            ->subject('Password reset OTP')
            ->greeting('Hello!')
            ->line('Use this one-time password (OTP) to reset your password:')
            ->line("**{$this->otp}**")
            ->line('This OTP will expire in 10 minutes.')
            ->line('If you did not request this, no further action is required.');
    }
}
