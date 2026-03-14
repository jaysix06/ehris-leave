<?php

namespace App\Http\Controllers\RequestStatus;

use App\Http\Controllers\Controller;
use App\Models\EmployeeRequest;
use App\Models\LocatorSlip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class MyRequestsController extends Controller
{
    public function index(): Response
    {
        $requests = collect();
        $authUser = request()->user();
        $hrid = $this->resolveHrid($authUser);

        if ($hrid !== null && Schema::hasTable('tbl_requests')) {
            $requests = $requests->concat(EmployeeRequest::query()
                ->where('hrid', $hrid)
                ->orderByDesc('id')
                ->get()
                ->map(function (EmployeeRequest $request): array {
                    return [
                        'id' => (int) $request->id,
                        'type' => (string) ($request->type_of_request ?? ''),
                        'purpose' => (string) ($request->purpose ?? ''),
                        'reason' => (string) ($request->reason ?? ''),
                        'status' => (string) ($request->status ?? 'Pending'),
                        'remarks' => (string) ($request->remarks ?? ''),
                        'attachment' => $request->attachment,
                        'running_year' => (string) ($request->running_year ?? ''),
                        'submitted_at' => $this->formatSubmittedDate($request),
                        'sort_at' => (string) ($request->getAttribute('created_at') ?? $request->getAttribute('updated_at') ?? ''),
                    ];
                }));
        }

        if ($hrid !== null && Schema::hasTable('tbl_locator_slips')) {
            $requests = $requests->concat(LocatorSlip::query()
                ->where('hrid', $hrid)
                ->orderByDesc('id')
                ->get()
                ->map(function (LocatorSlip $slip): array {
                    return [
                        'id' => (int) $slip->id,
                        'type' => 'Locator Slip',
                        'purpose' => (string) ($slip->purpose_of_travel ?? ''),
                        'reason' => $this->locatorSlipReason($slip),
                        'status' => (string) ($slip->status ?? 'Pending'),
                        'remarks' => (string) ($slip->remarks ?? ''),
                        'attachment' => null,
                        'running_year' => $slip->date_of_filing?->format('Y') ?? '',
                        'submitted_at' => $slip->date_of_filing?->format('d M Y'),
                        'sort_at' => $slip->created_at?->toISOString() ?? $slip->date_of_filing?->toDateString() ?? '',
                    ];
                }));
        }

        return Inertia::render('RequestStatus/MyRequests', [
            'requests' => $requests
                ->sortByDesc(fn (array $request): string => (string) ($request['sort_at'] ?? ''))
                ->values()
                ->all(),
            'statusMessage' => session('status'),
        ]);
    }

    private function resolveHrid(mixed $authUser): ?int
    {
        if ($authUser === null) {
            return null;
        }

        if (! empty($authUser->hrId)) {
            return (int) $authUser->hrId;
        }

        if (! empty($authUser->email) && Schema::hasTable('tbl_user')) {
            $hrid = User::query()
                ->where('email', (string) $authUser->email)
                ->value('hrId');

            return $hrid !== null ? (int) $hrid : null;
        }

        return null;
    }

    private function formatSubmittedDate(EmployeeRequest $request): ?string
    {
        $createdAt = $request->getAttribute('created_at');
        $updatedAt = $request->getAttribute('updated_at');

        foreach ([$createdAt, $updatedAt] as $candidate) {
            if ($candidate === null || trim((string) $candidate) === '') {
                continue;
            }

            try {
                return Carbon::parse((string) $candidate)->format('d M Y, h:i A');
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function locatorSlipReason(LocatorSlip $slip): string
    {
        $travelType = $slip->travel_type === 'official_time' ? 'Official Time' : 'Official Business';
        $travelDate = $slip->travel_date?->format('d M Y');
        $timeOut = filled($slip->time_out) ? Carbon::parse((string) $slip->time_out)->format('h:i A') : null;
        $timeIn = filled($slip->time_in) ? Carbon::parse((string) $slip->time_in)->format('h:i A') : null;

        return trim(implode(' | ', array_filter([
            $travelType,
            $travelDate,
            $timeOut !== null ? 'Time Out: '.$timeOut : null,
            $timeIn !== null ? 'Time In: '.$timeIn : null,
            filled($slip->destination) ? 'Destination: '.$slip->destination : null,
        ])));
    }
}
