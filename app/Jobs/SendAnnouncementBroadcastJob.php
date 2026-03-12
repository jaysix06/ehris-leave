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
        $announcement = Announcement::query()->find($this->announcementId);
        if (! $announcement) {
            return;
        }

        $toAddress = config('mail.from.address');
        $toName = (string) config('mail.from.name', 'eHRIS');
        if (! is_string($toAddress) || trim($toAddress) === '') {
            $toAddress = 'noreply@ehris.local';
        }

        Mail::to(trim($toAddress), $toName)
            ->bcc($this->bccEmails)
            ->send(new AnnouncementBroadcastMail($announcement));
    }
}

