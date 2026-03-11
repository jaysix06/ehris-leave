<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Utilities\StoreAnnouncementRequest;
use App\Http\Requests\Utilities\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
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
