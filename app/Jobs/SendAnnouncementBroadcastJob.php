<?php

namespace App\Jobs;

use App\Mail\AnnouncementBroadcastMail;
use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAnnouncementBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $bccEmails
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public int $announcementId,
        public array $bccEmails,
        public array $meta = [],
    ) {
        $this->onQueue('mail');
    }

    public function handle(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $announcement = Announcement::query()->find($this->announcementId);
        if (! $announcement) {
            return;
        }

        // Send directly to the recipient list only (no copy to the admin inbox).
        Mail::bcc($this->bccEmails)
            ->send(new AnnouncementBroadcastMail($announcement));
    }
}

