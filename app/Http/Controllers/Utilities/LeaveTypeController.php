<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Services\ActivityLogService;
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
            'leave_type' => ['required', 'string', 'max:255', 'unique:tbl_leave_type,leave_type'],
        ], [
            'leave_type.unique' => 'A leave type with this name already exists.',
        ]);

        LeaveType::create($data);

        $code = $data['leave'] ? ' ('.$data['leave'].')' : '';
        ActivityLogService::logCreate('Leave Type', $data['leave_type'].$code);

        return back();
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $data = $request->validate([
            'leave' => ['nullable', 'string', 'max:255'],
            'leave_type' => ['required', 'string', 'max:255', 'unique:tbl_leave_type,leave_type,'.$leaveType->id.',id'],
        ], [
            'leave_type.unique' => 'A leave type with this name already exists.',
        ]);

        $oldCode = $leaveType->leave ?? '';
        $oldType = $leaveType->leave_type;

        $leaveType->update($data);

        $newCode = $data['leave'] ?? '';
        $newType = $data['leave_type'];

        $oldDisplay = $oldCode !== '' ? $oldType.' ('.$oldCode.')' : $oldType;
        $newDisplay = $newCode !== '' ? $newType.' ('.$newCode.')' : $newType;

        ActivityLogService::logUpdate('Leave Type', "Changed from '{$oldDisplay}' to '{$newDisplay}'");

        return back();
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        $code = $leaveType->leave ?? '';
        $type = $leaveType->leave_type;
        $display = $code !== '' ? $type.' ('.$code.')' : $type;

        $leaveType->delete();

        ActivityLogService::logDelete('Leave Type', $display);

        return back();
    }
}
