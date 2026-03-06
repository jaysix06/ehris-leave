<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\LeaveWorkflowNotification;
use Illuminate\Database\Eloquent\Collection;

class LeaveWorkflowNotificationService
{
    public function notifyLeaveTypeUpdated(?string $leaveType, string $action = 'updated'): void
    {
        $actionLabel = strtolower(trim($action)) === 'deleted' ? 'removed' : 'updated';
        $typeLabel = trim((string) $leaveType) !== '' ? trim((string) $leaveType) : 'Leave types';

        $recipients = User::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(COALESCE(role, "")) like ?', ['%hr%'])
                    ->orWhereRaw('LOWER(COALESCE(role, "")) like ?', ['%admin%'])
                    ->orWhereRaw('LOWER(COALESCE(role, "")) like ?', ['%reporting manager%'])
                    ->orWhereRaw('LOWER(COALESCE(role, "")) like ?', ['%sds%']);
            })
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new LeaveWorkflowNotification(
                title: "Leave type {$actionLabel}",
                description: "{$typeLabel} was {$actionLabel}.",
                href: '/utilities/leave-types',
                meta: ['workflow' => 'leave_types', 'action' => $actionLabel],
            ));
        }
    }

    public function notifyMyDetailsUpdated(int $hrid): void
    {
        $recipient = $this->userByHrid($hrid);
        if ($recipient === null) {
            return;
        }

        $latest = $recipient->notifications()->latest()->first();
        if ($latest !== null) {
            $data = is_array($latest->data) ? $latest->data : [];
            $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
            $sameWorkflow = ($meta['workflow'] ?? null) === 'my_details';
            $recentEnough = $latest->created_at !== null && $latest->created_at->gt(now()->subMinutes(2));
            if ($sameWorkflow && $recentEnough) {
                return;
            }
        }

        $recipient->notify(new LeaveWorkflowNotification(
            title: 'My Details updated',
            description: 'Your personal records were updated in the system.',
            href: '/my-details',
            meta: ['workflow' => 'my_details', 'hrid' => $hrid],
        ));
    }

    public function notifySubmitted(?int $leaveApplicationId, ?int $employeeHrid, ?int $rmAssigneeHrid, ?string $employeeName): void
    {
        $recipient = $this->userByHrid($rmAssigneeHrid);
        if ($recipient === null) {
            return;
        }

        $employeeLabel = $this->displayEmployeeName($employeeName);
        $leaveLabel = $this->leaveLabel($leaveApplicationId);

        $recipient->notify(new LeaveWorkflowNotification(
            title: "{$employeeLabel} filed a leave request",
            description: "Leave {$leaveLabel} is waiting for your review.",
            href: '/employee-management/leave-requests',
            meta: [
                'leave_application_id' => $leaveApplicationId,
                'employee_hrid' => $employeeHrid,
                'stage' => 'submitted',
            ],
        ));
    }

    public function notifyDecision(
        ?int $leaveApplicationId,
        ?int $employeeHrid,
        ?int $rmAssigneeHrid,
        string $decision,
        ?string $workflowStatus,
        ?string $employeeName,
        ?string $actorRole,
        ?int $actorHrid,
    ): void {
        $decisionNorm = strtolower(trim($decision));
        $workflowNorm = strtolower(trim((string) $workflowStatus));
        $actorRoleNorm = strtolower(trim((string) $actorRole));
        $employeeLabel = $this->displayEmployeeName($employeeName);
        $leaveLabel = $this->leaveLabel($leaveApplicationId);

        if ($decisionNorm === 'approve') {
            $this->notifyEmployeeOnApproval($employeeHrid, $workflowNorm, $leaveLabel);
            $this->notifyNextApproversOnApproval($employeeLabel, $leaveLabel, $workflowNorm, $actorHrid, $actorRoleNorm);
            return;
        }

        if ($decisionNorm === 'disapprove') {
            $this->notifyEmployeeOnDisapproval($employeeHrid, $leaveLabel, $actorRoleNorm);
        }
    }

    public function notifyCancelled(?int $leaveApplicationId, ?int $rmAssigneeHrid, ?string $employeeName): void
    {
        $recipient = $this->userByHrid($rmAssigneeHrid);
        if ($recipient === null) {
            return;
        }

        $employeeLabel = $this->displayEmployeeName($employeeName);
        $leaveLabel = $this->leaveLabel($leaveApplicationId);

        $recipient->notify(new LeaveWorkflowNotification(
            title: "{$employeeLabel} cancelled {$leaveLabel}",
            description: 'The leave request assigned to you was cancelled by the employee.',
            href: '/employee-management/leave-requests',
            meta: [
                'leave_application_id' => $leaveApplicationId,
                'stage' => 'cancelled',
            ],
        ));
    }

    private function notifyEmployeeOnApproval(?int $employeeHrid, string $workflowStatus, string $leaveLabel): void
    {
        $employee = $this->userByHrid($employeeHrid);
        if ($employee === null) {
            return;
        }

        if ($workflowStatus === 'pending_hr') {
            $employee->notify(new LeaveWorkflowNotification(
                title: "Your {$leaveLabel} was approved by RM",
                description: 'Your request was forwarded to HR for the next approval step.',
                href: '/request-status/my-leave',
                meta: ['stage' => 'approved_by_rm'],
            ));
            return;
        }

        if ($workflowStatus === 'pending_sds') {
            $employee->notify(new LeaveWorkflowNotification(
                title: "Your {$leaveLabel} was approved by HR",
                description: 'Your request was forwarded to SDS for final approval.',
                href: '/request-status/my-leave',
                meta: ['stage' => 'approved_by_hr'],
            ));
            return;
        }

        if ($workflowStatus === 'approved') {
            $employee->notify(new LeaveWorkflowNotification(
                title: "Your {$leaveLabel} is approved",
                description: 'Your leave request completed all approval stages.',
                href: '/request-status/my-leave',
                meta: ['stage' => 'approved_final'],
            ));
        }
    }

    private function notifyNextApproversOnApproval(
        string $employeeLabel,
        string $leaveLabel,
        string $workflowStatus,
        ?int $actorHrid,
        string $actorRole,
    ): void {
        if ($workflowStatus === 'pending_hr' && $actorRole === 'rm') {
            $recipients = $this->usersByRoleKeyword('hr')
                ->filter(fn (User $user) => (int) ($user->hrId ?? 0) !== (int) ($actorHrid ?? 0));

            foreach ($recipients as $recipient) {
                $recipient->notify(new LeaveWorkflowNotification(
                    title: "{$employeeLabel} {$leaveLabel} is ready for HR approval",
                    description: 'A new leave request has reached the HR approval stage.',
                    href: '/employee-management/leave-requests',
                    meta: ['stage' => 'pending_hr'],
                ));
            }
        }

        if ($workflowStatus === 'pending_sds' && $actorRole === 'hr') {
            $recipients = $this->usersByRoleKeyword('sds')
                ->filter(fn (User $user) => (int) ($user->hrId ?? 0) !== (int) ($actorHrid ?? 0));

            foreach ($recipients as $recipient) {
                $recipient->notify(new LeaveWorkflowNotification(
                    title: "{$employeeLabel} {$leaveLabel} is ready for SDS approval",
                    description: 'A new leave request has reached the SDS approval stage.',
                    href: '/employee-management/leave-requests',
                    meta: ['stage' => 'pending_sds'],
                ));
            }
        }
    }

    private function notifyEmployeeOnDisapproval(?int $employeeHrid, string $leaveLabel, string $actorRole): void
    {
        $employee = $this->userByHrid($employeeHrid);
        if ($employee === null) {
            return;
        }

        $by = $actorRole === 'rm' ? 'RM' : ($actorRole === 'hr' ? 'HR' : ($actorRole === 'sds' ? 'SDS' : 'approver'));

        $employee->notify(new LeaveWorkflowNotification(
            title: "Your {$leaveLabel} was disapproved",
            description: "Your request was disapproved by {$by}.",
            href: '/request-status/my-leave',
            meta: ['stage' => 'disapproved', 'by' => $by],
        ));
    }

    private function usersByRoleKeyword(string $keyword): Collection
    {
        return User::query()
            ->whereRaw('LOWER(COALESCE(role, "")) like ?', ['%'.strtolower($keyword).'%'])
            ->get();
    }

    private function userByHrid(?int $hrid): ?User
    {
        if ($hrid === null || $hrid <= 0) {
            return null;
        }

        return User::query()->where('hrId', $hrid)->first();
    }

    private function displayEmployeeName(?string $employeeName): string
    {
        $name = trim((string) $employeeName);

        return $name !== '' ? $name : 'An employee';
    }

    private function leaveLabel(?int $leaveApplicationId): string
    {
        return $leaveApplicationId !== null && $leaveApplicationId > 0
            ? "leave #{$leaveApplicationId}"
            : 'leave request';
    }
}
