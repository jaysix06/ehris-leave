<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Models\UpcomingEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $userId = $user ? (int) $user->getKey() : null;

        $events = [];
        if ($userId) {
            $events = UpcomingEvent::query()
                ->where('user_id', $userId)
                ->where('start_at', '>=', now()->subYears(2))
                ->where('start_at', '<=', now()->addYears(10))
                ->orderBy('start_at')
                ->get()
                ->map(fn (UpcomingEvent $e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                    'start_at' => $e->start_at->toIso8601String(),
                    'end_at' => $e->end_at?->toIso8601String(),
                    'description' => $e->description,
                    'color' => $e->color ?? 'blue',
                    'indicator' => $e->indicator ?? 'highlight',
                ]);
        }

        return Inertia::render('SelfService/Calendar', [
            'initialEvents' => $events,
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user ? (int) $user->getKey() : null;
        if (! $userId) {
            return response()->json(['events' => []]);
        }

        $start = $request->input('start', now()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end', now()->endOfMonth()->format('Y-m-d'));

        $events = UpcomingEvent::query()
            ->where('user_id', $userId)
            ->where('start_at', '>=', Carbon::parse($start)->startOfDay())
            ->where('start_at', '<=', Carbon::parse($end)->endOfDay())
            ->orderBy('start_at')
            ->get()
            ->map(fn (UpcomingEvent $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'start_at' => $e->start_at->toIso8601String(),
                'end_at' => $e->end_at?->toIso8601String(),
                'description' => $e->description,
                'color' => $e->color ?? 'blue',
                'indicator' => $e->indicator ?? 'highlight',
            ]);

        return response()->json(['events' => $events]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'description' => ['nullable', 'string', 'max:2000'],
            'color' => ['nullable', 'string', 'in:blue,emerald,amber,violet,rose,cyan'],
            'indicator' => ['nullable', 'string', 'in:highlight,dot'],
        ]);

        $user = $request->user();
        $userId = $user ? (int) $user->getKey() : null;
        if (! $userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $event = UpcomingEvent::create([
            'user_id' => $userId,
            'title' => $validated['title'],
            'start_at' => $validated['start_at'],
            'end_at' => $validated['end_at'] ?? null,
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'] ?? 'blue',
            'indicator' => $validated['indicator'] ?? 'highlight',
        ]);

        return response()->json([
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'start_at' => $event->start_at->toIso8601String(),
                'end_at' => $event->end_at?->toIso8601String(),
                'description' => $event->description,
                'color' => $event->color ?? 'blue',
                'indicator' => $event->indicator ?? 'highlight',
            ],
        ], 201);
    }

    public function destroy(Request $request, UpcomingEvent $event): JsonResponse
    {
        $user = $request->user();
        $userId = $user ? (int) $user->getKey() : null;
        if (! $userId || (int) $event->user_id !== $userId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
