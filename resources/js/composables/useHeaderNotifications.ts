import { router, usePage } from '@inertiajs/vue3';
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

const optimisticReadIds = ref<string[]>([]);
let listenerRefCount = 0;
let listenerAttached = false;

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

const csrfToken = () => {
    if (typeof document === 'undefined') return '';
    const token = document.querySelector('meta[name="csrf-token"]');
    return token?.getAttribute('content') ?? '';
};

const refreshHeaderNotifications = () => {
    router.reload({
        only: ['headerNotifications'],
        preserveScroll: true,
        preserveState: true,
    });
};

export const useHeaderNotifications = () => {
    const page = usePage();
    const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED !== 'false';
    const authHrid = computed(() => {
        const auth = (page.props as Record<string, unknown>).auth as Record<string, unknown> | undefined;
        const user = auth?.user as Record<string, unknown> | undefined;
        const parsed = Number(user?.hrId ?? 0);
        return Number.isFinite(parsed) ? parsed : 0;
    });

    const baseNotifications = computed<HeaderNotification[]>(() => {
        const props = page.props as Record<string, unknown>;
        const raw = props.headerNotifications ?? props.notifications;
        if (!Array.isArray(raw)) return [];

        return raw
            .map((item, index) => normalizeNotification(item, index))
            .filter((item): item is HeaderNotification => item !== null)
            .slice(0, 20);
    });

    const notifications = computed<HeaderNotification[]>(() =>
        baseNotifications.value.map((item) => ({
            ...item,
            read: item.read || optimisticReadIds.value.includes(item.id),
        })),
    );

    const unreadNotificationCount = computed(() =>
        notifications.value.filter((item) => !item.read).length,
    );

    const markNotificationAsRead = async (id: string) => {
        const normalizedId = String(id ?? '').trim();
        if (normalizedId === '' || optimisticReadIds.value.includes(normalizedId)) return;

        optimisticReadIds.value = [normalizedId, ...optimisticReadIds.value].slice(0, 500);

        try {
            const response = await fetch(`/notifications/${encodeURIComponent(normalizedId)}/read`, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: '{}',
            });

            if (!response.ok) {
                throw new Error(`Failed to mark notification as read (${response.status})`);
            }

            refreshHeaderNotifications();
        } catch {
            // Keep optimistic state to avoid badge jitter; server sync will happen on next navigation.
        }
    };

    onMounted(() => {
        if (!reverbEnabled) return;
        listenerRefCount += 1;
        if (listenerAttached) return;

        try {
            echo().channel('leave-requests').listen('.LeaveRequestUpdated', refreshHeaderNotifications);
            echo().channel('leave-types').listen('.LeaveTypeUpdated', refreshHeaderNotifications);
            echo().channel('my-details').listen('.MyDetailsUpdated', (payload: { hrid?: number | string }) => {
                const payloadHrid = Number(payload?.hrid ?? 0);
                if (authHrid.value > 0 && payloadHrid === authHrid.value) {
                    refreshHeaderNotifications();
                }
            });
            listenerAttached = true;
        } catch {
            // Reverb not connected; real-time updates disabled
        }
    });

    onBeforeUnmount(() => {
        if (!reverbEnabled) return;
        listenerRefCount = Math.max(0, listenerRefCount - 1);
        if (listenerRefCount > 0 || !listenerAttached) return;

        try {
            echo().channel('leave-requests').stopListening('LeaveRequestUpdated');
            echo().channel('leave-types').stopListening('LeaveTypeUpdated');
            echo().channel('my-details').stopListening('MyDetailsUpdated');
            listenerAttached = false;
        } catch {
            // ignore
        }
    });

    return {
        notifications,
        unreadNotificationCount,
        markNotificationAsRead,
    };
};
