<?php

namespace App\Http\Controllers\SelfService;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelfService\StoreLocatorSlipRequest;
use App\Models\EmployeeRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class LocatorSlipController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('SelfService/LocatorSlip');
    }

    public function store(StoreLocatorSlipRequest $request): RedirectResponse
    {
        if (! Schema::hasTable('tbl_requests')) {
            return back()->withErrors([
                'purpose' => 'Locator slip requests are unavailable because the request table is missing.',
            ]);
        }

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')?->store('locator-slip-attachments')
            : null;

        EmployeeRequest::query()->create([
            'hrid' => $this->resolveHrid($request->user()),
            'purpose' => trim((string) $request->input('purpose')),
            'attachment' => $attachmentPath,
            'status' => 'On Process',
            'type_of_request' => 'Locator Slip',
            'reason' => trim((string) $request->input('reason')),
            'running_year' => now()->format('Y'),
            'remarks' => null,
        ]);

        return redirect()
            ->route('request-status.my-requests')
            ->with('status', 'Locator slip request submitted successfully.');
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
}
