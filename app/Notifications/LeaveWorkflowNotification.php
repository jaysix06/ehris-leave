<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaveWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly string $kind = 'leave',
        private readonly ?string $href = null,
        private readonly array $meta = [],
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'kind' => $this->kind,
            'href' => $this->href,
            'meta' => $this->meta,
        ];
    }
}

