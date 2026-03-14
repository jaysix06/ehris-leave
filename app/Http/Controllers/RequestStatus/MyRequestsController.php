<?php

namespace App\Http\Controllers\RequestStatus;

use App\Http\Controllers\Controller;
use App\Models\EmployeeRequest;
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
            $requests = EmployeeRequest::query()
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
                    ];
                });
        }

        return Inertia::render('RequestStatus/MyRequests', [
            'requests' => $requests->values()->all(),
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
}
