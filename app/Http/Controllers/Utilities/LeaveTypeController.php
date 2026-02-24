<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function index(): Response
    {
        $leaveTypes = LeaveType::query()
            ->select(['id', 'leave', 'leave_type'])
            ->orderBy('id')
            ->get();

        return Inertia::render('Utilities/LeaveTypes', [
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'leave' => ['nullable', 'string', 'max:255'],
            'leave_type' => ['required', 'string', 'max:255'],
        ]);

        LeaveType::create($data);

        return back();
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $data = $request->validate([
            'leave' => ['nullable', 'string', 'max:255'],
            'leave_type' => ['required', 'string', 'max:255'],
        ]);

        $leaveType->update($data);

        return back();
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $leaveType->delete();

        return back();
    }
}
