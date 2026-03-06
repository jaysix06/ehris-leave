<?php

namespace App\Http\Middleware;

use App\Models\SurveySet;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $headerNotifications = [];
        $surveyCategoriesWithSurveys = [];
        if ($request->user()) {
            $headerNotifications = $request->user()
                ->notifications()
                ->latest()
                ->limit(20)
                ->get()
                ->map(function ($notification) {
                    $data = is_array($notification->data) ? $notification->data : [];

                    return [
                        'id' => (string) $notification->id,
                        'title' => (string) ($data['title'] ?? 'Notification'),
                        'description' => (string) ($data['description'] ?? ''),
                        'kind' => (string) ($data['kind'] ?? 'general'),
                        'href' => isset($data['href']) && is_string($data['href']) && trim($data['href']) !== '' ? $data['href'] : null,
                        'read' => $notification->read_at !== null,
                    ];
                })
                ->values()
                ->all();

            $surveyCategoriesWithSurveys = SurveySet::query()
                ->whereIn('category', ['GAD', 'PRAISE', 'PASS'])
                ->distinct()
                ->pluck('category')
                ->filter()
                ->values()
                ->toArray();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'headerNotifications' => $headerNotifications,
            'surveyCategoriesWithSurveys' => $surveyCategoriesWithSurveys,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'errors' => fn () => $request->session()->get('errors') ? $request->session()->get('errors')->getBag('default')->getMessages() : [],
            ],
        ];
    }
}
