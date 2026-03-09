<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarPlus, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Calendar } from 'v-calendar';
import { toast } from 'vue3-toastify';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CalendarEvent = {
    id: number;
    title: string;
    start_at: string;
    end_at: string | null;
    description: string | null;
    color?: string | null;
    indicator?: 'highlight' | 'dot' | null;
};

const EVENT_COLORS = [
    { value: 'blue', label: 'Blue', hex: '#3b82f6', borderClass: 'border-l-blue-500', barClass: 'bg-blue-500' },
    { value: 'emerald', label: 'Emerald', hex: '#10b981', borderClass: 'border-l-emerald-500', barClass: 'bg-emerald-500' },
    { value: 'amber', label: 'Amber', hex: '#f59e0b', borderClass: 'border-l-amber-500', barClass: 'bg-amber-500' },
    { value: 'violet', label: 'Violet', hex: '#8b5cf6', borderClass: 'border-l-violet-500', barClass: 'bg-violet-500' },
    { value: 'rose', label: 'Rose', hex: '#f43f5e', borderClass: 'border-l-rose-500', barClass: 'bg-rose-500' },
    { value: 'cyan', label: 'Cyan', hex: '#06b6d4', borderClass: 'border-l-cyan-500', barClass: 'bg-cyan-500' },
] as const;

function getEventColorHex(color: string | null | undefined): string {
    const found = EVENT_COLORS.find((c) => c.value === (color ?? 'blue'));
    return found?.hex ?? EVENT_COLORS[0].hex;
}

function getEventBorderClass(color: string | null | undefined): string {
    const found = EVENT_COLORS.find((c) => c.value === (color ?? 'blue'));
    return found?.borderClass ?? 'border-l-blue-500';
}

function getEventBarClass(color: string | null | undefined): string {
    const found = EVENT_COLORS.find((c) => c.value === (color ?? 'blue'));
    return found?.barClass ?? 'bg-blue-500';
}

/** v-calendar theme color names for highlight/dot (solid circle + white date number) */
const VC_THEME_COLORS: Record<string, string> = {
    blue: 'blue',
    emerald: 'green',
    amber: 'orange',
    violet: 'purple',
    rose: 'pink',
    cyan: 'teal',
};

function getVcThemeColor(color: string | null | undefined): string {
    const key = color ?? 'blue';
    return VC_THEME_COLORS[key] ?? 'blue';
}

function randomEventColor(): string {
    return EVENT_COLORS[Math.floor(Math.random() * EVENT_COLORS.length)].value;
}

type Props = {
    initialEvents?: CalendarEvent[];
};

const props = withDefaults(defineProps<Props>(), {
    initialEvents: () => [],
});

const pageTitle = 'Upcoming Events & schedule';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Self-Service', href: selfServiceRoutes.timezone().url },
    { title: 'Timezone', href: selfServiceRoutes.timezone().url },
    { title: pageTitle },
];

const events = ref<CalendarEvent[]>([...props.initialEvents]);
const calendarDate = ref(new Date());
const showAddModal = ref(false);
const selectedDate = ref<Date | null>(null);
const formTitle = ref('');
const formStartAt = ref('');
const formEndAt = ref('');
const formDescription = ref('');
const submitLoading = ref(false);

watch(
    () => props.initialEvents,
    (next) => {
        events.value = [...next];
    },
    { deep: true },
);

/** Refetch events from server so calendar stays in sync (fully dynamic) */
function refetchEvents() {
    router.reload({ only: ['initialEvents'] });
}

function onVisibilityChange() {
    if (document.visibilityState === 'visible') {
        refetchEvents();
    }
}

const POLL_INTERVAL_MS = 60_000;
let pollTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    document.addEventListener('visibilitychange', onVisibilityChange);
    pollTimer = setInterval(refetchEvents, POLL_INTERVAL_MS);
});

onUnmounted(() => {
    document.removeEventListener('visibilitychange', onVisibilityChange);
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
});

/** Normalize to local calendar day so "set 9" shows on 9 (avoids timezone off-by-one) */
function toLocalDayStart(d: Date): Date {
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
}

/** Send date at noon UTC so the calendar day is preserved in all timezones */
function toNoonUTC(datetimeLocal: string): string {
    const d = new Date(datetimeLocal);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}T12:00:00.000Z`;
}

/** Normalize event to v-calendar dates: single day or range (start/end) for multi-day highlight */
function eventDates(e: CalendarEvent): Array<Date | { start: Date; end: Date }> {
    const start = new Date(e.start_at);
    const startDay = toLocalDayStart(start);
    if (!e.end_at) {
        return [startDay];
    }
    const end = new Date(e.end_at);
    if (end <= start) {
        return [startDay];
    }
    const endDay = new Date(end.getFullYear(), end.getMonth(), end.getDate());
    endDay.setHours(23, 59, 59, 999);
    return [{ start: startDay, end: endDay }];
}

// v-calendar attributes: line (bar) under the date instead of circle
const attributes = computed(() =>
    events.value.map((e) => ({
        key: e.id,
        dates: eventDates(e),
        bar: { color: getVcThemeColor(e.color) },
        popover: { label: e.title },
    })),
);

/** Events that include the clicked day (for detail modal) */
const eventsForSelectedDay = computed(() => {
    const d = selectedDate.value;
    if (!d) return [];
    const dayStart = toLocalDayStart(d);
    return events.value.filter((e) => {
        const start = toLocalDayStart(new Date(e.start_at));
        if (start.getTime() > dayStart.getTime()) return false;
        const end = e.end_at ? toLocalDayStart(new Date(e.end_at)) : start;
        return end.getTime() >= dayStart.getTime();
    });
});

const selectedDateLabel = computed(() => {
    if (!selectedDate.value) return '';
    return selectedDate.value.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
});

function onDayClick(day: { startDate: Date }) {
    selectedDate.value = new Date(day.startDate.getFullYear(), day.startDate.getMonth(), day.startDate.getDate());
}

function closeDetailModal() {
    selectedDate.value = null;
}

// Show all events that fall within the currently viewed calendar month
const upcomingList = computed(() => {
    const d = calendarDate.value;
    const monthStart = new Date(d.getFullYear(), d.getMonth(), 1);
    const monthEnd = new Date(d.getFullYear(), d.getMonth() + 1, 0, 23, 59, 59, 999);
    return [...events.value]
        .filter((e) => {
            const start = new Date(e.start_at);
            const end = e.end_at ? new Date(e.end_at) : start;
            return start <= monthEnd && end >= monthStart;
        })
        .sort((a, b) => new Date(a.start_at).getTime() - new Date(b.start_at).getTime());
});

const formatEventDate = (iso: string) => {
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

function openAddModal() {
    const now = new Date();
    now.setMinutes(0, 0, 0);
    formTitle.value = '';
    formStartAt.value = now.toISOString().slice(0, 16);
    formEndAt.value = '';
    formDescription.value = '';
    showAddModal.value = true;
}

function closeAddModal() {
    showAddModal.value = false;
}

async function submitEvent() {
    if (!formTitle.value.trim()) {
        toast.error('Please enter a title.');
        return;
    }
    submitLoading.value = true;
    try {
        const res = await fetch('/api/self-service/calendar/events', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                title: formTitle.value.trim(),
                start_at: formStartAt.value ? toNoonUTC(formStartAt.value) : null,
                end_at: formEndAt.value ? toNoonUTC(formEndAt.value) : null,
                description: formDescription.value.trim() || null,
                color: randomEventColor(),
                indicator: 'highlight',
            }),
        });
        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.message || 'Failed to create event.');
        }
        const data = await res.json();
        events.value = [...events.value, data.event];
        toast.success('Event added.');
        closeAddModal();
        refetchEvents();
    } catch (err) {
        toast.error(err instanceof Error ? err.message : 'Failed to add event.');
    } finally {
        submitLoading.value = false;
    }
}

</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page flex flex-col gap-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-bold text-foreground">Calendar &amp; upcoming events</h1>
                <div class="flex items-center gap-2">
                    <Link :href="selfServiceRoutes.timezone().url" class="text-sm font-medium text-primary hover:underline">
                        ← Back to Timezone
                    </Link>
                    <Button size="sm" @click="openAddModal">
                        <CalendarPlus class="mr-2 size-4" />
                        Add event
                    </Button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Calendar (transparent) -->
                <div class="lg:col-span-2 rounded-lg bg-transparent">
                    <Calendar
                        v-model="calendarDate"
                        :attributes="attributes"
                        expanded
                        :masks="{ weekdays: 'WWW' }"
                        class="calendar-events"
                        @dayclick="onDayClick"
                    />
                </div>

                <!-- Upcoming list (all future events, scrollable) -->
                <div class="ehris-card flex flex-col">
                    <h2 class="mb-4 text-lg font-semibold text-foreground">Upcoming events</h2>
                    <ul
                        v-if="upcomingList.length"
                        class="max-h-[min(70vh,600px)] space-y-3 overflow-y-auto pr-1"
                    >
                        <li
                            v-for="ev in upcomingList"
                            :key="ev.id"
                            class="flex min-h-0 rounded-lg border border-border/60 bg-muted/20 text-sm shadow-sm"
                        >
                            <span
                                aria-hidden="true"
                                :class="['shrink-0 w-1 rounded-l-lg', getEventBarClass(ev.color)]"
                            />
                            <div class="min-w-0 flex-1 p-3">
                                <p class="font-medium text-foreground">{{ ev.title }}</p>
                                <p class="mt-1 text-muted-foreground">{{ formatEventDate(ev.start_at) }}</p>
                                <p v-if="ev.description" class="mt-1 line-clamp-2 text-muted-foreground">
                                    {{ ev.description }}
                                </p>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">No events this month. Add one above.</p>
                </div>
            </div>
        </div>

        <!-- Day detail modal (events for clicked date with description) -->
        <Teleport to="body">
            <div
                v-if="selectedDate !== null"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeDetailModal"
            >
                <div
                    class="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-lg max-h-[85vh] flex flex-col"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="day-detail-title"
                >
                    <div class="mb-4 flex items-center justify-between shrink-0">
                        <h2 id="day-detail-title" class="text-lg font-semibold text-foreground">
                            {{ selectedDateLabel }}
                        </h2>
                        <button
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-muted"
                            aria-label="Close"
                            @click="closeDetailModal"
                        >
                            <X class="size-5" />
                        </button>
                    </div>
                    <div class="min-h-0 overflow-y-auto space-y-4">
                        <template v-if="eventsForSelectedDay.length">
                            <div
                                v-for="ev in eventsForSelectedDay"
                                :key="ev.id"
                                class="rounded-lg border border-border/60 bg-muted/20 p-4 text-sm"
                            >
                                <span
                                    aria-hidden="true"
                                    :class="['inline-block h-2 w-2 shrink-0 rounded-full align-middle mr-2', getEventBarClass(ev.color)]"
                                />
                                <span class="font-medium text-foreground">{{ ev.title }}</span>
                                <p class="mt-1 text-muted-foreground">{{ formatEventDate(ev.start_at) }}</p>
                                <p v-if="ev.description" class="mt-2 text-foreground/90 whitespace-pre-wrap">
                                    {{ ev.description }}
                                </p>
                                <p v-else class="mt-2 text-muted-foreground italic">No description.</p>
                            </div>
                        </template>
                        <p v-else class="text-sm text-muted-foreground">No events on this day.</p>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Add event modal -->
        <Teleport to="body">
            <div
                v-if="showAddModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeAddModal"
            >
                <div
                    class="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-lg"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="add-event-title"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h2 id="add-event-title" class="text-lg font-semibold text-foreground">Add upcoming event</h2>
                        <button
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-muted"
                            aria-label="Close"
                            @click="closeAddModal"
                        >
                            <X class="size-5" />
                        </button>
                    </div>
                    <form class="space-y-4" @submit.prevent="submitEvent">
                        <div>
                            <Label for="event-title">Title</Label>
                            <Input
                                id="event-title"
                                v-model="formTitle"
                                type="text"
                                class="mt-1"
                                placeholder="Event title"
                                required
                                maxlength="255"
                            />
                        </div>
                        <div>
                            <Label for="event-start">Start</Label>
                            <Input
                                id="event-start"
                                v-model="formStartAt"
                                type="datetime-local"
                                class="mt-1"
                                required
                            />
                        </div>
                        <div>
                            <Label for="event-end">End (optional)</Label>
                            <Input
                                id="event-end"
                                v-model="formEndAt"
                                type="datetime-local"
                                class="mt-1"
                            />
                        </div>
                        <div>
                            <Label for="event-desc">Description (optional)</Label>
                            <textarea
                                id="event-desc"
                                v-model="formDescription"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                rows="3"
                                placeholder="Description"
                                maxlength="2000"
                            />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="closeAddModal">
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="submitLoading">
                                {{ submitLoading ? 'Adding…' : 'Add event' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.calendar-events :deep(.vc-container) {
    border: 0;
    background: transparent;
}
.calendar-events :deep(.vc-weeks),
.calendar-events :deep(.vc-week),
.calendar-events :deep(.vc-day),
.calendar-events :deep(.vc-day-content) {
    background: transparent;
    border-color: transparent;
}
.calendar-events :deep(.vc-day-content) {
    font-size: 0.875rem;
}
/* Line (bar) under the date */
.calendar-events :deep(.vc-bars) {
    width: 100%;
}
.calendar-events :deep(.vc-bar) {
    height: 4px;
    border-radius: 2px;
}

/* Day popover: keep color indicator visible and never covered */
.calendar-events :deep(.vc-day-popover-row) {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 1.75rem;
}
.calendar-events :deep(.vc-day-popover-row-indicator) {
    flex-shrink: 0;
    width: 12px;
    height: 12px;
    min-width: 12px;
    min-height: 12px;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.calendar-events :deep(.vc-day-popover-row-indicator span) {
    width: 100%;
    height: 100%;
    border-radius: 9999px;
    flex-shrink: 0;
    /* Transparent (outline) instead of solid circle so indicator is visible */
    background: transparent !important;
    border: 2px solid var(--vc-accent-500);
    box-sizing: border-box;
}
.calendar-events :deep(.vc-day-popover-row-label) {
    flex: 1;
    min-width: 0;
}
</style>
