import { usePage } from '@inertiajs/vue3';
import { echo } from '@laravel/echo-vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

export type NotificationKind = 'leave' | 'birthday' | 'general';

export type HeaderNotification = {
    id: string;
    title: string;
    description: string;
    kind: NotificationKind;
    href: string | null;
    read: boolean;
};

type LeaveRequestRealtimePayload = {
    leaveApplicationId?: number | string | null;
    employeeHrid?: number | string | null;
    rmAssigneeHrid?: number | string | null;
    action?: string | null;
    workflowStatus?: string | null;
    employeeName?: string | null;
    actorRole?: string | null;
    actorHrid?: number | string | null;
};

const normalizeKind = (value: unknown): NotificationKind => {
    if (value === 'leave' || value === 'birthday' || value === 'general') return value;
    return 'general';
};

const normalizeNotification = (value: unknown, index: number): HeaderNotification | null => {
    if (!value || typeof value !== 'object') return null;

    const record = value as Record<string, unknown>;
    const titleCandidate = record.title ?? record.message ?? record.text;
    if (typeof titleCandidate !== 'string' || titleCandidate.trim() === '') return null;

    const descriptionCandidate = record.description ?? record.body ?? record.details ?? '';
    const idCandidate = record.id ?? index;
    const hrefCandidate = record.href ?? record.link ?? null;

    return {
        id: String(idCandidate),
        title: titleCandidate.trim(),
        description: typeof descriptionCandidate === 'string' ? descriptionCandidate : '',
        kind: normalizeKind(record.kind ?? record.type ?? record.category),
        href: typeof hrefCandidate === 'string' && hrefCandidate.trim() !== '' ? hrefCandidate : null,
        read: Boolean(record.read),
    };
};

export const useHeaderNotifications = () => {
    const page = usePage();
    const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED !== 'false';
    const liveNotifications = ref<HeaderNotification[]>([]);

    const authHrid = computed(() => {
        const auth = (page.props as Record<string, unknown>).auth as Record<string, unknown> | undefined;
        const user = auth?.user as Record<string, unknown> | undefined;
        const parsed = Number(user?.hrId ?? 0);
        return Number.isFinite(parsed) ? parsed : 0;
    });
    const authRole = computed(() => {
        const auth = (page.props as Record<string, unknown>).auth as Record<string, unknown> | undefined;
        const user = auth?.user as Record<string, unknown> | undefined;
        return String(user?.role ?? '').trim().toLowerCase();
    });

    const baseNotifications = computed<HeaderNotification[]>(() => {
        const props = page.props as Record<string, unknown>;
        const raw = props.headerNotifications ?? props.notifications;
        if (!Array.isArray(raw)) return [];

        return raw
            .map((item, index) => normalizeNotification(item, index))
            .filter((item): item is HeaderNotification => item !== null);
    });

    const notifications = computed<HeaderNotification[]>(() =>
        [...liveNotifications.value, ...baseNotifications.value].slice(0, 8),
    );

    const unreadNotificationCount = computed(() =>
        notifications.value.filter((item) => !item.read).length,
    );

    const pushRealtimeNotification = (
        title: string,
        description: string,
        href = '/employee-management/leave-requests',
    ) => {
        liveNotifications.value = [
            {
                id: `rt-${Date.now()}-${Math.random().toString(36).slice(2)}`,
                title,
                description,
                kind: 'leave',
                href,
                read: false,
            },
            ...liveNotifications.value,
        ].slice(0, 8);
    };

    const handleLeaveRequestUpdated = (payload: LeaveRequestRealtimePayload) => {
        const action = String(payload?.action ?? '').toLowerCase();
        const workflowStatus = String(payload?.workflowStatus ?? '').toLowerCase();
        const rmAssigneeHrid = Number(payload?.rmAssigneeHrid ?? 0);
        const employeeHrid = Number(payload?.employeeHrid ?? 0);
        const actorHrid = Number(payload?.actorHrid ?? 0);
        const actorRole = String(payload?.actorRole ?? '').trim().toLowerCase();
        const leaveId = Number(payload?.leaveApplicationId ?? 0);
        const leaveLabel = leaveId > 0 ? `#${leaveId}` : 'request';
        const employeeNameRaw = String(payload?.employeeName ?? '').trim();
        const employeeName = employeeNameRaw !== '' ? employeeNameRaw : 'An employee';
        const isActor = authHrid.value > 0 && actorHrid > 0 && authHrid.value === actorHrid;
        const isHrRole = authRole.value.includes('hr');
        const isSdsRole = authRole.value.includes('sds');

        if (action === 'submitted') {
            if (authHrid.value > 0 && rmAssigneeHrid === authHrid.value && !isActor) {
                pushRealtimeNotification(
                    `${employeeName} filed a leave request`,
                    `Leave ${leaveLabel} is waiting for your review.`,
                );
            }
            return;
        }

        if (action === 'approve') {
            if (authHrid.value > 0 && employeeHrid === authHrid.value && !isActor) {
                if (workflowStatus === 'pending_hr') {
                    pushRealtimeNotification(
                        `Your leave ${leaveLabel} was approved by RM`,
                        'Your request was forwarded to HR for the next approval step.',
                        '/request-status/my-leave',
                    );
                    return;
                }
                if (workflowStatus === 'pending_sds') {
                    pushRealtimeNotification(
                        `Your leave ${leaveLabel} was approved by HR`,
                        'Your request was forwarded to SDS for final approval.',
                        '/request-status/my-leave',
                    );
                    return;
                }
                if (workflowStatus === 'approved') {
                    pushRealtimeNotification(
                        `Your leave ${leaveLabel} is approved`,
                        'Your leave request completed all approval stages.',
                        '/request-status/my-leave',
                    );
                    return;
                }
            }

            if (workflowStatus === 'pending_hr' && isHrRole && actorRole === 'rm' && !isActor) {
                pushRealtimeNotification(
                    `${employeeName} leave ${leaveLabel} is ready for HR approval`,
                    'A new leave request has reached the HR approval stage.',
                );
                return;
            }

            if (workflowStatus === 'pending_sds' && isSdsRole && actorRole === 'hr' && !isActor) {
                pushRealtimeNotification(
                    `${employeeName} leave ${leaveLabel} is ready for SDS approval`,
                    'A new leave request has reached the SDS approval stage.',
                );
            }
            return;
        }

        if (action === 'disapprove') {
            if (authHrid.value > 0 && employeeHrid === authHrid.value && !isActor) {
                const by = actorRole === 'rm' ? 'RM' : actorRole === 'hr' ? 'HR' : actorRole === 'sds' ? 'SDS' : 'approver';
                pushRealtimeNotification(
                    `Your leave ${leaveLabel} was disapproved`,
                    `Your request was disapproved by ${by}.`,
                    '/request-status/my-leave',
                );
            }
            return;
        }

        if (action === 'cancelled') {
            if (authHrid.value > 0 && rmAssigneeHrid === authHrid.value && !isActor) {
                pushRealtimeNotification(
                    `${employeeName} cancelled leave ${leaveLabel}`,
                    'The leave request assigned to you was cancelled by the employee.',
                );
            }
        }
    };

    onMounted(() => {
        if (!reverbEnabled) return;
        try {
            echo().channel('leave-requests').listen('.LeaveRequestUpdated', handleLeaveRequestUpdated);
        } catch {
            // Reverb not connected; real-time updates disabled
        }
    });

    onBeforeUnmount(() => {
        if (!reverbEnabled) return;
        try {
            echo().channel('leave-requests').stopListening('LeaveRequestUpdated');
        } catch {
            // ignore
        }
    });

    return {
        notifications,
        unreadNotificationCount,
    };
};
