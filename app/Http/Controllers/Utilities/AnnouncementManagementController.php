<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilities\SendAnnouncementEmailRequest;
use App\Http\Requests\Utilities\StoreAnnouncementRequest;
use App\Http\Requests\Utilities\UpdateAnnouncementRequest;
use App\Jobs\SendAnnouncementBroadcastJob;
use App\Models\Announcement;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementManagementController extends Controller
{
    public function index(): Response
    {
        $announcements = Announcement::query()
            ->select(['id', 'title', 'content', 'links', 'status', 'created_at'])
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Utilities/AnnouncementManagement', [
            'announcements' => $announcements,
            'roles' => Role::roleNames(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $links = $this->sanitizeLinks($validated['links'] ?? []);

        $announcement = Announcement::create([
            'title' => trim((string) $validated['title']),
            'content' => $this->normalizeString($validated['content'] ?? null),
            'links' => $links === [] ? null : $links,
            'status' => $validated['status'],
        ]);

        ActivityLogService::logCreate('Announcement', "Title: {$announcement->title}");

        return back();
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validated();
        $links = $this->sanitizeLinks($validated['links'] ?? []);
        $oldTitle = $announcement->title;

        $announcement->update([
            'title' => trim((string) $validated['title']),
            'content' => $this->normalizeString($validated['content'] ?? null),
            'links' => $links === [] ? null : $links,
            'status' => $validated['status'],
        ]);

        ActivityLogService::logUpdate('Announcement', "Title: {$oldTitle} -> {$announcement->title}");

        return back();
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $title = $announcement->title;
        $announcement->delete();

        ActivityLogService::logDelete('Announcement', "Title: {$title}");

        return back();
    }

    public function sendEmail(SendAnnouncementEmailRequest $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validated();
        $recipientScope = (string) $validated['recipient_scope'];
        $onlyActive = (bool) ($validated['only_active'] ?? true);
        $roles = is_array($validated['roles'] ?? null) ? $validated['roles'] : [];

        // Prefer personal_email (registration email) and fallback to official email.
        $query = User::query()
            ->selectRaw("
                userId,
                fullname,
                role,
                active,
                COALESCE(NULLIF(TRIM(personal_email), ''), NULLIF(TRIM(email), '')) as send_email
            ");

        if ($onlyActive) {
            $query->where('active', true);
        }

        if ($recipientScope === 'role') {
            $query->whereIn('role', $roles);
        }

        /** @var Collection<int, string> $emails */
        $emails = $query
            ->pluck('send_email')
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => $email !== '')
            ->values();

        // Chunk to keep BCC lists reasonable.
        $queuedCount = 0;
        foreach ($emails->chunk(50) as $chunk) {
            SendAnnouncementBroadcastJob::dispatch(
                $announcement->id,
                $chunk->values()->all(),
                [
                    'scope' => $recipientScope,
                    'only_active' => $onlyActive,
                    'roles' => $roles,
                ],
            );
            $queuedCount += $chunk->count();
        }

        ActivityLogService::logCreate('Announcement Email', "Announcement: {$announcement->title} | Recipients queued: {$queuedCount}");

        return back()->with('success', "Queued announcement email to {$queuedCount} recipient(s).");
    }

    /**
     * @param  array<int, array{label?: mixed, url?: mixed}>  $links
     * @return array<int, array{label: string, url: string}>
     */
    private function sanitizeLinks(array $links): array
    {
        return collect($links)
            ->map(function (array $link): array {
                return [
                    'label' => trim((string) ($link['label'] ?? '')),
                    'url' => trim((string) ($link['url'] ?? '')),
                ];
            })
            ->filter(function (array $link): bool {
                return $link['label'] !== '' || $link['url'] !== '';
            })
            ->values()
            ->all();
    }

    private function normalizeString(mixed $value): ?string
    {
        $string = is_string($value) ? trim($value) : '';

        return $string === '' ? null : $string;
    }
}
