<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, CheckCircle2, Download, Eye, FolderOpen, ListPlus, Pause, Pencil, Play, Search, Trash2, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Calendar as VCalendar, DatePicker } from 'v-calendar';
import { toast } from 'vue3-toastify';
import AppLayout from '@/layouts/AppLayout.vue';
import AppModal from '@/components/AppModal.vue';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';

type TaskItem = {
    id: number;
    title: string;
    description: string;
    priority: string;
    due_date: string;
    due_date_end: string | null;
    add_to_calendar: boolean;
    status: string;
    accomplishment_report: string | null;
};

type TaskDueDateRange = { start: Date; end: Date };

type Props = {
    attendance?: {
        isClockedIn: boolean;
        hoursWorkedThisWeek: string;
    };
    openTasks?: TaskItem[];
    completedTasks?: TaskItem[];
    successMessage?: string;
    errorMessage?: string;
};

const props = withDefaults(defineProps<Props>(), {
    attendance: () => ({ isClockedIn: false, hoursWorkedThisWeek: '00:00:00' }),
    openTasks: () => [],
    completedTasks: () => [],
    successMessage: '',
    errorMessage: '',
});

const pageTitle = 'Self-Service - WFH Attendance';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
    },
];

// Live date/time (header style)
const now = ref(new Date());

function getOrdinal(n: number): string {
    const s = ['th', 'st', 'nd', 'rd'];
    const v = n % 100;
    return n + (s[(v - 20) % 10] ?? s[v] ?? s[0]);
}

function formatDateLong(d: Date): string {
    const weekday = d.toLocaleDateString('en-US', { weekday: 'long' });
    const day = d.getDate();
    const month = d.toLocaleDateString('en-US', { month: 'long' });
    const year = d.getFullYear();
    return `${weekday} the ${getOrdinal(day)} of ${month}, ${year}`;
}

function formatTime(d: Date): string {
    return d.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
    });
}

const dateTimeDisplay = ref('');

function updateDisplay(): void {
    now.value = new Date();
    dateTimeDisplay.value = `Today is ${formatDateLong(now.value)} ${formatTime(now.value)}`;
}

let tick: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    updateDisplay();
    tick = setInterval(updateDisplay, 1000);
});

onBeforeUnmount(() => {
    if (tick) clearInterval(tick);
});

// Attendance: from backend, updated after clock in/out (synced from props when Inertia updates)
const isClockedIn = ref(props.attendance?.isClockedIn ?? false);
const hoursWorkedThisWeek = ref(props.attendance?.hoursWorkedThisWeek ?? '00:00:00');
const clockLoading = ref(false);
const clockIconSpinning = ref(false);

// Tasks tab: 'open' | 'completed'
const tasksTab = ref<'open' | 'completed'>('open');

// Search and sort (apply to both Tasks and Completed Tasks)
const taskSearchQuery = ref('');
const taskPrioritySort = ref<'High' | 'Medium' | 'Low'>('High');
const taskStatusSort = ref<'Not Started' | 'In Progress' | 'On Hold'>('Not Started');

const PRIORITY_ORDER: Record<'High' | 'Medium' | 'Low', number[]> = {
    High: [0, 1, 2],   // High first, then Medium, then Low
    Medium: [1, 0, 2], // Medium first, then High, then Low
    Low: [2, 1, 0],    // Low first, then Medium, then High
};
const PRIORITY_RANK: Record<string, number> = { High: 0, Medium: 1, Low: 2 };

const STATUS_ORDER: Record<'Not Started' | 'In Progress' | 'On Hold', number[]> = {
    'Not Started': [0, 1, 2],   // Not Started, In Progress, On Hold
    'In Progress': [1, 0, 2],   // In Progress first, then Not Started, then On Hold
    'On Hold': [2, 0, 1],       // On Hold first, then Not Started, then In Progress
};
const STATUS_RANK: Record<string, number> = {
    'Not Started': 0,
    'In Progress': 1,
    'On Hold': 2,
    'Complete': 3,
};

function filterAndSortTasks(tasks: TaskItem[]): TaskItem[] {
    const q = taskSearchQuery.value.trim().toLowerCase();
    const priorityKey = taskPrioritySort.value;
    const statusKey = taskStatusSort.value;
    const priorityOrder = PRIORITY_ORDER[priorityKey];
    const statusOrder = STATUS_ORDER[statusKey];
    let list = tasks;
    if (q) {
        list = list.filter(
            (t) =>
                t.title.toLowerCase().includes(q) ||
                (t.description && t.description.toLowerCase().includes(q)),
        );
    }
    return [...list].sort((a, b) => {
        const statusA = normalizedStatus(a);
        const statusB = normalizedStatus(b);
        const statusRankA = statusOrder[STATUS_RANK[statusA] ?? 0] ?? 99;
        const statusRankB = statusOrder[STATUS_RANK[statusB] ?? 0] ?? 99;
        if (statusRankA !== statusRankB) return statusRankA - statusRankB;
        const rankA = priorityOrder[PRIORITY_RANK[a.priority] ?? 1];
        const rankB = priorityOrder[PRIORITY_RANK[b.priority] ?? 1];
        return rankA - rankB;
    });
}

const sortedFilteredOpenTasks = computed(() => filterAndSortTasks(props.openTasks ?? []));
const sortedFilteredCompletedTasks = computed(() => filterAndSortTasks(props.completedTasks ?? []));

// View task modal
const viewTask = ref<TaskItem | null>(null);
const taskActionLoading = ref(false);

function openViewModal(t: TaskItem): void {
    viewTask.value = t;
}

function closeViewModal(): void {
    viewTask.value = null;
    isEditingTask.value = false;
}

// Edit task (inside view modal)
const isEditingTask = ref(false);
const editTitle = ref('');
const editDescription = ref('');
const editPriority = ref<'Low' | 'Medium' | 'High'>('Low');
const editDueDateRange = ref<{ start: Date; end: Date } | null>(null);
const editCalendarKey = ref(0);
const editSubmitLoading = ref(false);

const editDueDateRangeForPicker = computed({
    get: () => editDueDateRange.value ?? undefined,
    set: (v: { start: Date; end: Date } | undefined) => { editDueDateRange.value = v ?? null; },
});

function startEditing(): void {
    if (!viewTask.value) return;
    const t = viewTask.value;
    editTitle.value = t.title;
    editDescription.value = t.description;
    editPriority.value = (t.priority as 'Low' | 'Medium' | 'High') || 'Low';
    const start = parseTaskDate(t.due_date);
    const end = t.due_date_end && t.due_date_end !== t.due_date ? parseTaskDate(t.due_date_end) : start;
    editDueDateRange.value = { start, end };
    editCalendarKey.value += 1;
    isEditingTask.value = true;
}

function cancelEditing(): void {
    isEditingTask.value = false;
}

function submitEditTask(): void {
    if (!viewTask.value) return;
    const title = editTitle.value.trim();
    if (!title) { toast.error('Task title is required.'); return; }
    if (title.length > 50) { toast.error('Task title must be at most 50 characters.'); return; }
    const description = editDescription.value.trim();
    if (!description) { toast.error('Task target is required.'); return; }
    const range = editDueDateRange.value;
    if (!range?.start) { toast.error('Task due date is required.'); return; }
    const end = range.end && range.end >= range.start ? range.end : range.start;

    editSubmitLoading.value = true;
    router.patch(`/self-service/wfh-time-in-out/tasks/${viewTask.value.id}`, {
        title,
        description,
        priority: editPriority.value,
        due_date: formatTaskDueDate(range.start),
        due_date_end: formatTaskDueDate(end) !== formatTaskDueDate(range.start) ? formatTaskDueDate(end) : null,
    }, {
        preserveScroll: true,
        onFinish: () => { editSubmitLoading.value = false; },
        onSuccess: () => {
            isEditingTask.value = false;
            closeViewModal();
        },
    });
}

function normalizedStatus(t: TaskItem): string {
    return t.status === 'open' ? 'Not Started' : t.status;
}

function updateTaskStatus(taskId: number, status: string, fromStatus?: string): void {
    taskActionLoading.value = true;
    router.put(`/self-service/wfh-time-in-out/tasks/${taskId}`, { status }, {
        preserveScroll: true,
        onFinish: () => { taskActionLoading.value = false; },
        onSuccess: () => {
            closeViewModal();
            if (status === 'In Progress' && fromStatus === 'On Hold') {
                toast.success('Task resumed!');
            } else if (status === 'In Progress') {
                toast.success('Task started!');
            } else if (status === 'On Hold') {
                toast.info('Task put on hold.');
            }
        },
    });
}

const showDeleteModal = ref(false);
const pendingDeleteTaskId = ref<number | null>(null);

function requestDeleteTask(taskId: number): void {
    pendingDeleteTaskId.value = taskId;
    showDeleteModal.value = true;
}

function cancelDelete(): void {
    showDeleteModal.value = false;
    pendingDeleteTaskId.value = null;
}

function confirmDelete(): void {
    if (pendingDeleteTaskId.value === null) return;
    taskActionLoading.value = true;
    const taskId = pendingDeleteTaskId.value;
    showDeleteModal.value = false;
    pendingDeleteTaskId.value = null;
    router.delete(`/self-service/wfh-time-in-out/tasks/${taskId}`, {
        preserveScroll: true,
        onFinish: () => { taskActionLoading.value = false; },
        onSuccess: () => {
            closeViewModal();
            toast.success('Task deleted.');
        },
    });
}

// Complete Task (accomplishment report) modal
const showCompleteModal = ref(false);
const pendingCompleteTaskId = ref<number | null>(null);
const accomplishmentReport = ref('');
const completeSubmitLoading = ref(false);

function requestCompleteTask(taskId: number): void {
    pendingCompleteTaskId.value = taskId;
    accomplishmentReport.value = '';
    showCompleteModal.value = true;
}

function cancelComplete(): void {
    showCompleteModal.value = false;
    pendingCompleteTaskId.value = null;
    accomplishmentReport.value = '';
}

function confirmComplete(): void {
    if (pendingCompleteTaskId.value === null) return;
    const report = accomplishmentReport.value.trim();
    if (!report) {
        toast.error('Please write your accomplishment report.');
        return;
    }
    completeSubmitLoading.value = true;
    const taskId = pendingCompleteTaskId.value;
    showCompleteModal.value = false;
    pendingCompleteTaskId.value = null;
    router.put(`/self-service/wfh-time-in-out/tasks/${taskId}`, {
        status: 'Complete',
        accomplishment_report: report,
    }, {
        preserveScroll: true,
        onFinish: () => {
            completeSubmitLoading.value = false;
            accomplishmentReport.value = '';
        },
        onSuccess: () => {
            closeViewModal();
            toast.success('Task completed!');
        },
    });
}

// Export: single button opens modal to pick date range; exports all tasks (open + completed) in that range.
const exportPdfLoading = ref(false);
const showExportModal = ref(false);
const exportDateRange = ref<{ start: Date; end: Date } | null>(null);
const exportDateRangeKey = ref(0);

const exportDateRangeForPicker = computed({
    get: () => exportDateRange.value ?? undefined,
    set: (v: { start: Date; end: Date } | undefined) => { exportDateRange.value = v ?? null; },
});

function openExportModal(): void {
    if (!exportDateRange.value) {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        exportDateRange.value = { start, end: new Date(now) };
    }
    exportDateRangeKey.value += 1;
    showExportModal.value = true;
}

function closeExportModal(): void {
    showExportModal.value = false;
}

function formatExportDate(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function doExportWithRange(): void {
    const range = exportDateRange.value;
    if (!range?.start || !range?.end) {
        toast.error('Please select a date range.');
        return;
    }
    const dateFrom = formatExportDate(range.start);
    const dateTo = formatExportDate(range.end);
    if (dateFrom > dateTo) {
        toast.error('Start date must be before or equal to end date.');
        return;
    }
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const url = `${origin}/self-service/wfh-time-in-out/export/pdf?date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`;
    exportPdfLoading.value = true;
    fetch(url, { method: 'GET', credentials: 'same-origin', headers: { Accept: 'application/pdf' } })
        .then(async (res) => {
            if (!res.ok) {
                const text = await res.text();
                throw new Error(text.length < 200 ? text : `Export failed (${res.status}).`);
            }
            const disposition = res.headers.get('Content-Disposition');
            const blob = await res.blob();
            return { blob, disposition };
        })
        .then((result) => {
            let filename = `tasklist_report_${dateFrom}_${dateTo}.pdf`;
            if (result.disposition) {
                const m = result.disposition.match(/filename="?([^";\n]+)"?/);
                if (m) filename = m[1].trim();
            }
            const u = URL.createObjectURL(result.blob);
            const a = document.createElement('a');
            a.href = u;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(u);
            closeExportModal();
            toast.success('Export downloaded.');
        })
        .catch((err: Error) => toast.error(err?.message ?? 'Export failed.'))
        .finally(() => { exportPdfLoading.value = false; });
}

// User's Manuals modal (wide)
const showUserManualModal = ref(false);

// Create Task modal
const showCreateTaskModal = ref(false);
const taskTitle = ref('');
const taskDescription = ref('');
const taskPriority = ref<'Low' | 'Medium' | 'High'>('Low');
const taskDueDateRange = ref<{ start: Date; end: Date } | null>(null);
const taskCalendarKey = ref(0);
const taskSubmitLoading = ref(false);

// Task view calendar (dashboard): show open tasks on their due dates
const taskViewCalendarDate = ref(new Date());

function parseTaskDate(ymd: string): Date {
    const [y, m, d] = ymd.split('-').map(Number);
    return new Date(y, (m ?? 1) - 1, d ?? 1);
}

/** Build calendar attributes: one attribute per task per day so popover shows all tasks when hovering a date */
function getDaysInRange(startYmd: string, endYmd: string | null): Date[] {
    const start = parseTaskDate(startYmd);
    if (!endYmd || endYmd === startYmd) return [start];
    const end = parseTaskDate(endYmd);
    const days: Date[] = [];
    const d = new Date(start);
    while (d <= end) {
        days.push(new Date(d));
        d.setDate(d.getDate() + 1);
    }
    return days;
}

const HIGHLIGHT_COLORS: Record<string, { fillMode: 'light'; color: string }> = {
    High: { fillMode: 'light', color: 'red' },
    Medium: { fillMode: 'light', color: 'orange' },
    Low: { fillMode: 'light', color: 'green' },
} as const;

const taskViewAttributes = computed(() => {
    type Attr = {
        key: string;
        dates: Array<{ start: Date; end: Date }> | Date[];
        order: number;
        highlight?: { start: { fillMode: 'light'; color: string }; base: { fillMode: 'light'; color: string }; end: { fillMode: 'light'; color: string } };
        dot?: { color: string; class: string };
    };
    const out: Attr[] = [];

    (props.openTasks ?? []).forEach((t, taskIndex) => {
        const start = parseTaskDate(t.due_date);
        const hasRange = t.due_date_end && t.due_date_end !== t.due_date;
        const end = hasRange ? parseTaskDate(t.due_date_end!) : start;
        const style = HIGHLIGHT_COLORS[t.priority] ?? HIGHLIGHT_COLORS.Low;

        out.push({
            key: `task-hl-${t.id}`,
            dates: [{ start, end }],
            order: taskIndex,
            highlight: { start: style, base: style, end: style },
        });

        const days = getDaysInRange(t.due_date, hasRange ? t.due_date_end : null);
        days.forEach((day, dayIndex) => {
            out.push({
                key: `task-dot-${t.id}-${day.getTime()}`,
                dates: [day],
                order: 1000 + taskIndex * 100 + dayIndex,
                dot: { color: style.color, class: 'task-dot' },
            });
        });
    });
    return out;
});

type DayTaskEntry = { title: string; priority: string; color: string };

const tasksByDayKey = computed(() => {
    const map = new Map<string, DayTaskEntry[]>();
    (props.openTasks ?? []).forEach((t) => {
        const hasRange = t.due_date_end && t.due_date_end !== t.due_date;
        const days = getDaysInRange(t.due_date, hasRange ? t.due_date_end : null);
        const color = t.priority === 'High' ? 'red' : t.priority === 'Medium' ? 'orange' : 'green';
        for (const day of days) {
            const key = `${day.getFullYear()}-${day.getMonth()}-${day.getDate()}`;
            if (!map.has(key)) map.set(key, []);
            map.get(key)!.push({ title: t.title, priority: t.priority, color });
        }
    });
    return map;
});

const hoveredDayTasks = ref<DayTaskEntry[]>([]);
const tooltipPos = ref({ x: 0, y: 0 });
const showTooltip = ref(false);

function onDayMouseEnter(day: { date: Date }, event: MouseEvent): void {
    const d = day.date;
    const key = `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
    const tasks = tasksByDayKey.value.get(key);
    if (!tasks || tasks.length === 0) {
        showTooltip.value = false;
        return;
    }
    hoveredDayTasks.value = tasks;
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    tooltipPos.value = { x: rect.left + rect.width / 2, y: rect.top };
    showTooltip.value = true;
}

function onDayMouseLeave(): void {
    showTooltip.value = false;
}

const taskDueDateRangeForPicker = computed({
    get: () => taskDueDateRange.value ?? undefined,
    set: (v: { start: Date; end: Date } | undefined) => { taskDueDateRange.value = v ?? null; },
});

function openCreateTaskModal(): void {
    taskTitle.value = '';
    taskDescription.value = '';
    taskPriority.value = 'Low';
    taskDueDateRange.value = null;
    taskCalendarKey.value += 1;
    showCreateTaskModal.value = true;
}

function closeCreateTaskModal(): void {
    showCreateTaskModal.value = false;
}

function formatTaskDueDate(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

const taskDueDateLabel = computed(() => {
    const range = taskDueDateRange.value;
    if (!range?.start) return null;
    const startStr = range.start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const end = range.end && range.end >= range.start ? range.end : range.start;
    const endStr = end.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    if (formatTaskDueDate(range.start) === formatTaskDueDate(end)) {
        return { text: startStr, days: 1 };
    }
    const days = Math.round((end.getTime() - range.start.getTime()) / (1000 * 60 * 60 * 24)) + 1;
    return { text: `${startStr} – ${endStr}`, days };
});

function submitCreateTask(): void {
    const title = taskTitle.value.trim();
    if (!title) {
        toast.error('Task title is required.');
        return;
    }
    if (title.length > 50) {
        toast.error('Task title must be at most 50 characters.');
        return;
    }
    const description = taskDescription.value.trim();
    if (!description) {
        toast.error('Task target is required.');
        return;
    }
    const range = taskDueDateRange.value;
    if (!range?.start) {
        toast.error('Task due date is required.');
        return;
    }
    const end = range.end && range.end >= range.start ? range.end : range.start;
    taskSubmitLoading.value = true;
    router.post('/self-service/wfh-time-in-out/tasks', {
        title,
        description,
        priority: taskPriority.value,
        due_date: formatTaskDueDate(range.start),
        due_date_end: formatTaskDueDate(end) !== formatTaskDueDate(range.start) ? formatTaskDueDate(end) : null,
    }, {
        preserveScroll: true,
        onFinish: () => { taskSubmitLoading.value = false; },
        onSuccess: () => { closeCreateTaskModal(); },
    });
}

// Sync refs when props change (e.g. after Inertia response from clock in/out)
watch(
    () => props.attendance,
    (att) => {
        if (att) {
            isClockedIn.value = att.isClockedIn;
            hoursWorkedThisWeek.value = att.hoursWorkedThisWeek;
        }
    },
    { deep: true },
);

// Show toasts from server-passed messages (avoids CSRF by using Inertia only)
watch(
    () => props.successMessage,
    (msg) => {
        if (msg) toast.success(msg);
    },
);
watch(
    () => props.errorMessage,
    (msg) => {
        if (msg) toast.error(msg);
    },
);


function clockIn(): void {
    if (clockLoading.value) return;
    clockLoading.value = true;
    router.post('/self-service/wfh-time-in-out/clock-in', {}, {
        preserveScroll: true,
        onFinish: () => { clockLoading.value = false; },
    });
}

function clockOut(): void {
    if (clockLoading.value) return;
    clockLoading.value = true;
    router.post('/self-service/wfh-time-in-out/clock-out', {}, {
        preserveScroll: true,
        onFinish: () => { clockLoading.value = false; },
    });
}

function toggleClock(): void {
    if (clockLoading.value) return;
    clockIconSpinning.value = true;
    setTimeout(() => { clockIconSpinning.value = false; }, 1200);
    if (isClockedIn.value) clockOut();
    else clockIn();
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page timezone-attendance-page">
            <div class="mb-3 flex justify-end">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-white/30 bg-white px-5 py-3 text-base font-semibold text-neutral-800 shadow-md transition hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    @click="showUserManualModal = true"
                >
                    <BookOpen class="size-5" />
                    User's Manuals
                </button>
            </div>
            <!-- Row 1: Calendar card (left) + Activity Logs / Clock-in card (right) -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_auto]">
                <!-- Calendar card (left) -->
                <div class="ehris-card flex min-h-0 flex-col overflow-hidden rounded-2xl p-4">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <h3 class="text-base font-semibold text-foreground">Task calendar</h3>
                        <p class="shrink-0 text-right text-sm font-medium tabular-nums text-muted-foreground sm:text-base">
                            {{ dateTimeDisplay }}
                        </p>
                    </div>
                    <div class="task-calendar-box task-calendar-box--large min-h-0 flex-1 rounded-md border border-border/80 bg-muted/20 p-2">
                        <VCalendar
                            v-model="taskViewCalendarDate"
                            :attributes="taskViewAttributes"
                            expanded
                            :masks="{ weekdays: 'WWW' }"
                            class="task-calendar-inline task-view-calendar"
                            @daymouseenter="onDayMouseEnter"
                            @daymouseleave="onDayMouseLeave"
                        />
                    </div>

                    <!-- Custom task tooltip -->
                    <Teleport to="body">
                        <Transition name="fade">
                            <div
                                v-if="showTooltip && hoveredDayTasks.length > 0"
                                class="task-tooltip"
                                :style="{ left: tooltipPos.x + 'px', top: tooltipPos.y + 'px' }"
                            >
                                <div
                                    v-for="(entry, idx) in hoveredDayTasks"
                                    :key="idx"
                                    class="task-tooltip-row"
                                >
                                    <span class="task-tooltip-dot" :style="{ background: entry.color }" />
                                    <span class="task-tooltip-priority">[{{ entry.priority[0] }}]</span>
                                    <span class="task-tooltip-label">{{ entry.title }}</span>
                                </div>
                            </div>
                        </Transition>
                    </Teleport>
                </div>

                <!-- Activity Logs card (right) – hours worked + Clock In button in empty space -->
                <article
                    class="flex min-h-0 flex-col justify-between gap-3 rounded-2xl p-4 text-white transition-colors duration-500 ease-in-out"
                    :class="isClockedIn ? 'bg-green-700' : 'bg-red-700'"
                >
                    <Link
                        :href="'/self-service/time-logs'"
                        class="flex flex-col gap-3 transition hover:opacity-95"
                    >
                        <span
                            class="inline-flex size-10 shrink-0 items-center justify-center opacity-90"
                            :class="{ 'clock-hands-spin': clockIconSpinning }"
                        >
                            <svg
                                class="size-10"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <g class="clock-hand clock-hand-minute">
                                    <line x1="12" y1="12" x2="12" y2="4" />
                                </g>
                                <g class="clock-hand clock-hand-hour">
                                    <line x1="12" y1="12" x2="18" y2="12" />
                                </g>
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-medium opacity-90">You have worked</p>
                            <p class="text-2xl font-bold tabular-nums">{{ hoursWorkedThisWeek }}</p>
                            <p class="text-sm font-medium opacity-90">this week.</p>
                        </div>
                        <span class="text-xs font-medium opacity-90 underline decoration-white/70 underline-offset-2">
                            View time →
                        </span>
                    </Link>
                    <div class="pt-2 flex flex-col items-stretch sm:items-end">
                        <p class="mb-1 text-xs font-medium opacity-90">You are currently {{ isClockedIn ? 'Clocked In' : 'Clocked Out' }}</p>
                        <button
                            type="button"
                            :disabled="clockLoading"
                            :class="[
                                'inline-flex w-full sm:w-56 items-center justify-center gap-2 whitespace-nowrap rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 disabled:opacity-60 disabled:pointer-events-none',
                                isClockedIn
                                    ? 'bg-red-700 text-white hover:bg-red-800 focus:ring-offset-green-700'
                                    : 'bg-green-700 text-white hover:bg-green-800 focus:ring-offset-red-700',
                            ]"
                            @click="toggleClock"
                        >
                            <template v-if="clockLoading">
                                <span>{{ isClockedIn ? 'Clocking out…' : 'Clocking in…' }}</span>
                            </template>
                            <template v-else-if="isClockedIn">
                                <span>Clock Out</span>
                                <ArrowRight class="size-4" />
                            </template>
                            <template v-else>
                                <span>Clock In</span>
                                <ArrowRight class="size-4" />
                            </template>
                        </button>
                    </div>
                </article>
            </section>

            <!-- Row 2: Tasks card (list only) -->
            <section class="grid grid-cols-1 gap-4">
                <div class="ehris-card p-4">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="ehris-tabs w-full sm:w-auto">
                            <button
                                type="button"
                                :class="['ehris-tab', { 'is-active': tasksTab === 'open' }]"
                                @click="tasksTab = 'open'"
                            >
                                Tasks
                            </button>
                            <button
                                type="button"
                                :class="['ehris-tab', { 'is-active': tasksTab === 'completed' }]"
                                @click="tasksTab = 'completed'"
                            >
                                Completed Tasks
                            </button>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-md border border-border bg-background px-2.5 py-1.5 text-xs font-medium text-foreground shadow-sm transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-60 disabled:pointer-events-none"
                                :disabled="exportPdfLoading"
                                @click="openExportModal"
                            >
                                <Download class="size-4" />
                                {{ exportPdfLoading ? 'Exporting…' : 'Export' }}
                            </button>
                            <button
                                v-if="tasksTab === 'open'"
                                type="button"
                                class="inline-flex shrink-0 items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                                @click="openCreateTaskModal"
                            >
                                <ListPlus class="size-4" />
                                Create Task
                            </button>
                        </div>
                    </div>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <div class="relative w-full max-w-[220px]">
                            <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                v-model="taskSearchQuery"
                                type="search"
                                placeholder="Search Task"
                                class="w-full rounded-md border border-input bg-background py-1.5 pl-8 pr-3 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            />
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex items-center gap-2">
                                <label for="task-status-sort" class="text-xs font-medium text-muted-foreground">Sort by status</label>
                                <select
                                    id="task-status-sort"
                                    v-model="taskStatusSort"
                                    class="rounded-md border border-input bg-background px-2 py-1.5 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option value="Not Started">Not Started</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="On Hold">On Hold</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <label for="task-priority-sort" class="text-xs font-medium text-muted-foreground">Sort by priority</label>
                                <select
                                    id="task-priority-sort"
                                    v-model="taskPrioritySort"
                                    class="rounded-md border border-input bg-background px-2 py-1.5 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                >
                                    <option value="High">High</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Low">Low</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div v-if="tasksTab === 'open'" class="min-h-[120px]">
                        <ul v-if="sortedFilteredOpenTasks.length" class="space-y-2">
                            <li
                                v-for="t in sortedFilteredOpenTasks"
                                :key="t.id"
                                :class="[
                                    'flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3 py-2 text-sm',
                                    normalizedStatus(t) === 'In Progress'
                                        ? 'border-blue-600/25 bg-blue-600/10'
                                        : normalizedStatus(t) === 'On Hold'
                                          ? 'border-amber-500/30 bg-amber-500/15'
                                          : 'border-slate-500/20 bg-slate-500/10',
                                ]"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-foreground">{{ t.title }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        Due {{ t.due_date }}{{ t.due_date_end && t.due_date_end !== t.due_date ? ` – ${t.due_date_end}` : '' }} · {{ t.priority }} · {{ normalizedStatus(t) }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded border border-border/80 bg-background px-2 py-1.5 text-xs font-medium text-foreground hover:bg-muted"
                                        @click="openViewModal(t)"
                                    >
                                        <Eye class="size-3.5" />
                                        View
                                    </button>
                                    <template v-if="normalizedStatus(t) === 'Not Started'">
                                        <button
                                            type="button"
                                            :disabled="taskActionLoading"
                                            class="inline-flex items-center gap-1 rounded bg-primary px-2 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60"
                                            @click="updateTaskStatus(t.id, 'In Progress', 'Not Started')"
                                        >
                                            <Play class="size-3.5" />
                                            Start Task
                                        </button>
                                    </template>
                                    <template v-else-if="normalizedStatus(t) === 'In Progress'">
                                        <button
                                            type="button"
                                            :disabled="taskActionLoading"
                                            class="inline-flex items-center gap-1 rounded border border-amber-600 bg-amber-500/10 px-2 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-500/20 disabled:opacity-60"
                                            @click="updateTaskStatus(t.id, 'On Hold')"
                                        >
                                            <Pause class="size-3.5" />
                                            Hold Task
                                        </button>
                                        <button
                                            type="button"
                                            :disabled="taskActionLoading"
                                            class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
                                            @click="requestCompleteTask(t.id)"
                                        >
                                            <CheckCircle2 class="size-3.5" />
                                            Complete Task
                                        </button>
                                    </template>
                                    <template v-else-if="normalizedStatus(t) === 'On Hold'">
                                        <button
                                            type="button"
                                            :disabled="taskActionLoading"
                                            class="inline-flex items-center gap-1 rounded bg-primary px-2 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60"
                                            @click="updateTaskStatus(t.id, 'In Progress', 'On Hold')"
                                        >
                                            <Play class="size-3.5" />
                                            Resume Task
                                        </button>
                                    </template>
                                    <button
                                        type="button"
                                        :disabled="taskActionLoading"
                                        class="inline-flex items-center gap-1 rounded border border-red-300 bg-red-50 px-2 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 disabled:opacity-60"
                                        @click="requestDeleteTask(t.id)"
                                    >
                                        <Trash2 class="size-3.5" />
                                        Delete Task
                                    </button>
                                </div>
                            </li>
                        </ul>
                        <div v-else class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border/80 bg-muted/30 py-6">
                            <FolderOpen class="size-10 text-muted-foreground/60" />
                            <p class="text-sm text-muted-foreground">{{ taskSearchQuery.trim() ? 'No matching tasks.' : 'No open tasks found' }}</p>
                        </div>
                    </div>
                    <div v-else class="min-h-[120px]">
                        <ul v-if="sortedFilteredCompletedTasks.length" class="space-y-2">
                            <li
                                v-for="t in sortedFilteredCompletedTasks"
                                :key="t.id"
                                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border/80 bg-muted/20 px-3 py-2 text-sm"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-foreground">{{ t.title }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">Due {{ t.due_date }}{{ t.due_date_end && t.due_date_end !== t.due_date ? ` – ${t.due_date_end}` : '' }} · {{ t.priority }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded border border-border/80 bg-background px-2 py-1.5 text-xs font-medium text-foreground hover:bg-muted"
                                        @click="openViewModal(t)"
                                    >
                                        <Eye class="size-3.5" />
                                        View
                                    </button>
                                </div>
                            </li>
                        </ul>
                        <div v-else class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border/80 bg-muted/30 py-6">
                            <CheckCircle2 class="size-10 text-muted-foreground/60" />
                            <p class="text-sm text-muted-foreground">{{ taskSearchQuery.trim() ? 'No matching completed tasks.' : 'No completed tasks found' }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- View / Edit Task modal -->
        <Teleport to="body">
            <div
                v-if="viewTask"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeViewModal"
            >
                <div
                    class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-xl border border-border bg-card p-6 shadow-lg"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="view-task-title"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h2 id="view-task-title" class="text-lg font-semibold text-foreground">
                            {{ isEditingTask ? 'Edit Task' : 'Task details' }}
                        </h2>
                        <div class="flex items-center gap-1">
                            <button
                                v-if="!isEditingTask && viewTask.status !== 'Complete'"
                                type="button"
                                class="rounded p-1 text-muted-foreground hover:bg-muted"
                                aria-label="Edit task"
                                @click="startEditing"
                            >
                                <Pencil class="size-4" />
                            </button>
                            <button
                                type="button"
                                class="rounded p-1 text-muted-foreground hover:bg-muted"
                                aria-label="Close"
                                @click="closeViewModal"
                            >
                                <X class="size-5" />
                            </button>
                        </div>
                    </div>

                    <!-- View mode -->
                    <dl v-if="!isEditingTask" class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-muted-foreground">Title</dt>
                            <dd class="mt-0.5 font-medium text-foreground">{{ viewTask.title }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-muted-foreground">Target</dt>
                            <dd class="mt-0.5 text-foreground">{{ viewTask.description || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-muted-foreground">Priority</dt>
                            <dd class="mt-0.5 text-foreground">{{ viewTask.priority }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-muted-foreground">Due date</dt>
                            <dd class="mt-0.5 text-foreground">
                                {{ viewTask.due_date }}{{ viewTask.due_date_end && viewTask.due_date_end !== viewTask.due_date ? ` – ${viewTask.due_date_end}` : '' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-muted-foreground">Status</dt>
                            <dd class="mt-0.5 text-foreground">{{ viewTask.status === 'open' ? 'Not Started' : viewTask.status }}</dd>
                        </div>
                        <div v-if="viewTask.accomplishment_report">
                            <dt class="font-medium text-muted-foreground">Accomplishment Report</dt>
                            <dd class="mt-0.5 whitespace-pre-wrap text-foreground">{{ viewTask.accomplishment_report }}</dd>
                        </div>
                    </dl>

                    <!-- Edit mode -->
                    <form v-else class="space-y-4" @submit.prevent="submitEditTask">
                        <div>
                            <label for="edit-task-title" class="mb-1 block text-sm font-medium text-foreground">Title</label>
                            <input
                                id="edit-task-title"
                                v-model="editTitle"
                                type="text"
                                maxlength="50"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="Task title"
                            />
                        </div>
                        <div>
                            <label for="edit-task-desc" class="mb-1 block text-sm font-medium text-foreground">Target</label>
                            <textarea
                                id="edit-task-desc"
                                v-model="editDescription"
                                rows="3"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none"
                                placeholder="Task target"
                            />
                        </div>
                        <div>
                            <label for="edit-task-priority" class="mb-1 block text-sm font-medium text-foreground">Priority</label>
                            <select
                                id="edit-task-priority"
                                v-model="editPriority"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-foreground">Due date range</label>
                            <div class="task-calendar-box rounded-md border border-input bg-background p-2">
                                <DatePicker
                                    :key="editCalendarKey"
                                    v-model="editDueDateRangeForPicker"
                                    is-range
                                    is-inline
                                    expanded
                                    :masks="{ weekdays: 'WWW' }"
                                    class="task-calendar-inline"
                                />
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                                @click="cancelEditing"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="editSubmitLoading"
                                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-60"
                            >
                                <CheckCircle2 class="size-4" />
                                {{ editSubmitLoading ? 'Saving…' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Create Task modal -->
        <Teleport to="body">
            <div
                v-if="showCreateTaskModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeCreateTaskModal"
            >
                <div
                    class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-xl border border-border bg-card p-6 shadow-lg"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="create-task-title"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h2 id="create-task-title" class="text-lg font-semibold text-foreground">Create Task</h2>
                        <button
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-muted"
                            aria-label="Close"
                            @click="closeCreateTaskModal"
                        >
                            <X class="size-5" />
                        </button>
                    </div>
                    <form class="space-y-4" @submit.prevent="submitCreateTask">
                        <div>
                            <label for="task-title" class="block text-sm font-medium text-foreground">Task Title *</label>
                            <p class="text-xs text-muted-foreground">Max 50 characters</p>
                            <input
                                id="task-title"
                                v-model="taskTitle"
                                type="text"
                                maxlength="50"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                placeholder="Enter task title"
                                required
                            />
                        </div>
                        <div>
                            <label for="task-priority" class="block text-sm font-medium text-foreground">Priority *</label>
                            <select
                                id="task-priority"
                                v-model="taskPriority"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                required
                            >
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div>
                            <label for="task-description" class="block text-sm font-medium text-foreground">Task Target *</label>
                            <textarea
                                id="task-description"
                                v-model="taskDescription"
                                rows="4"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none"
                                placeholder="Enter task target"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">Task Due Date *</label>
                            <p class="text-xs text-muted-foreground mb-1">Click a start date, then click an end date to highlight a range. The range can span multiple weeks (e.g. from day 3 to day 24). Weekdays only.</p>
                            <div class="task-calendar-box rounded-md border border-input bg-background p-2">
                                <DatePicker
                                    :key="taskCalendarKey"
                                    v-model="taskDueDateRangeForPicker"
                                    is-range
                                    is-inline
                                    expanded
                                    :masks="{ weekdays: 'WWW' }"
                                    class="task-calendar-inline"
                                />
                            </div>
                            <p
                                v-if="taskDueDateLabel"
                                class="mt-2 text-sm font-medium text-foreground"
                            >
                                {{ taskDueDateLabel.text }}
                                <span v-if="taskDueDateLabel.days > 1" class="text-muted-foreground">
                                    ({{ taskDueDateLabel.days }} days)
                                </span>
                            </p>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button
                                type="button"
                                class="rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted"
                                @click="closeCreateTaskModal"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="taskSubmitLoading"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 disabled:opacity-60"
                            >
                                <CheckCircle2 class="size-4" />
                                {{ taskSubmitLoading ? 'Saving…' : 'Save New Task' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Export PDF modal: pick date range, export all tasks (open + completed) in range -->
        <Teleport to="body">
            <div
                v-if="showExportModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeExportModal"
            >
                <div
                    class="w-full max-w-lg rounded-xl border border-border bg-card p-6 shadow-lg"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="export-modal-title"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h2 id="export-modal-title" class="text-lg font-semibold text-foreground">Export tasks to PDF</h2>
                        <button
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-muted"
                            aria-label="Close"
                            @click="closeExportModal"
                        >
                            <X class="size-5" />
                        </button>
                    </div>
                    <p class="mb-4 text-sm text-muted-foreground">
                        Choose a date range. All tasks (open and completed) whose due date falls in this range will be included.
                    </p>
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-foreground">Date range</label>
                        <div class="task-calendar-box rounded-md border border-input bg-background p-2">
                            <DatePicker
                                :key="exportDateRangeKey"
                                v-model="exportDateRangeForPicker"
                                is-range
                                is-inline
                                expanded
                                :masks="{ weekdays: 'WWW' }"
                                class="task-calendar-inline"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                            @click="closeExportModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-60"
                            :disabled="exportPdfLoading || !exportDateRange?.start || !exportDateRange?.end"
                            @click="doExportWithRange"
                        >
                            <Download class="size-4" />
                            {{ exportPdfLoading ? 'Exporting…' : 'Export' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- User's Manuals (wide modal) – in-app guide, not PDF -->
        <Teleport to="body">
            <div
                v-if="showUserManualModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="showUserManualModal = false"
            >
                <div
                    class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-xl border border-border bg-card p-6 shadow-lg"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="user-manual-title"
                >
                    <div class="mb-6 flex items-center justify-between border-b border-border pb-4">
                        <h2 id="user-manual-title" class="flex items-center gap-2 text-xl font-semibold text-foreground">
                            <BookOpen class="size-6 text-primary" />
                            User's Manual – WFH Attendance
                        </h2>
                        <button
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-muted"
                            aria-label="Close"
                            @click="showUserManualModal = false"
                        >
                            <X class="size-5" />
                        </button>
                    </div>

                    <div class="space-y-6 text-sm text-foreground">
                        <section>
                            <h3 class="mb-2 font-semibold text-foreground">How to use the WFH Attendance page</h3>
                            <p class="text-muted-foreground">
                                This page lets you record your work-from-home time and manage your tasks. Follow the steps below.
                            </p>
                        </section>

                        <section class="space-y-2">
                            <h4 class="font-medium text-foreground">1.) Clock In and Clock Out</h4>
                            <p class="text-muted-foreground">
                                The clock card on the right shows how long you have worked this week and whether you are currently clocked in or out.
                            </p>
                            <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                                <li><strong class="text-foreground">Click</strong> the green <strong>Clock In</strong> button to start recording time.</li>
                                <li><strong class="text-foreground">Click</strong> <strong>Clock Out</strong> when you finish.</li>
                                <li><strong class="text-foreground">Click</strong> the <strong>View time</strong> link to open your time logs. When you are done, <strong class="text-foreground">click</strong> <strong>Back to WFH Attendance</strong> to return.</li>
                            </ul>
                        </section>

                        <section class="space-y-2">
                            <h4 class="font-medium text-foreground">2.) Create a task</h4>
                            <p class="text-muted-foreground">
                                Enter the task title, target (description), priority, and due date. You can select a date range on the calendar (weekdays only). The task appears in the list and on the calendar.
                            </p>
                            <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                                <li><strong class="text-foreground">Click</strong> <strong>Create Task</strong> in the Tasks section.</li>
                                <li><strong class="text-foreground">Click</strong> a start date and end date on the calendar to set the due date range.</li>
                            </ul>
                        </section>

                        <section class="space-y-2">
                            <h4 class="font-medium text-foreground">3.) Task calendar</h4>
                            <p class="text-muted-foreground">
                                The Task calendar on the left shows your tasks by due date. Dates with tasks are marked.
                            </p>
                            <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                                <li><strong class="text-foreground">Click</strong> (or hover) over a date to see which tasks are due.</li>
                                <li><strong class="text-foreground">Click</strong> the arrows to move between months.</li>
                            </ul>
                        </section>

                        <section class="space-y-2">
                            <h4 class="font-medium text-foreground">4.) Manage task status</h4>
                            <p class="text-muted-foreground">
                                Each task has a <strong>View</strong> button and action buttons. Completed tasks move to the <strong>Completed Tasks</strong> tab. You can edit or delete from the view modal (delete is not available for completed tasks).
                            </p>
                            <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                                <li><strong class="text-foreground">Click</strong> <strong>View</strong> to see task details.</li>
                                <li><strong class="text-foreground">Click</strong> <strong>Start Task</strong> when the task is Not Started.</li>
                                <li><strong class="text-foreground">Click</strong> <strong>Hold Task</strong> or <strong>Complete Task</strong> when In Progress.</li>
                                <li><strong class="text-foreground">Click</strong> <strong>Resume Task</strong> when the task is On Hold.</li>
                            </ul>
                        </section>

                        <section class="space-y-2">
                            <h4 class="font-medium text-foreground">5.) Search and sort</h4>
                            <p class="text-muted-foreground">
                                Filter by title or target; order the list by status or priority.
                            </p>
                            <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                                <li><strong class="text-foreground">Click</strong> in the <strong>Search Task</strong> field and type to filter.</li>
                                <li><strong class="text-foreground">Click</strong> <strong>Sort by status</strong> or <strong>Sort by priority</strong> to reorder the list.</li>
                            </ul>
                        </section>

                        <section class="space-y-2">
                            <h4 class="font-medium text-foreground">6.) Export report</h4>
                            <p class="text-muted-foreground">
                                Export all tasks (open and completed) in a date range as a PDF. The report uses the official header and footer when available.
                            </p>
                            <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                                <li><strong class="text-foreground">Click</strong> <strong>Export</strong> to open the export dialog.</li>
                                <li><strong class="text-foreground">Click</strong> a start and end date on the calendar to choose the range, then <strong class="text-foreground">click</strong> <strong>Export</strong> to download the PDF.</li>
                            </ul>
                        </section>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Delete Task Confirmation Modal -->
        <AppModal v-model="showDeleteModal" title="Delete Task" tone="disapprove">
            <p class="text-sm text-muted-foreground">Are you sure you want to delete this task? This action cannot be undone.</p>
            <template #actions>
                <button
                    type="button"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                    @click="cancelDelete"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="taskActionLoading"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:opacity-60"
                    @click="confirmDelete"
                >
                    {{ taskActionLoading ? 'Deleting…' : 'Delete' }}
                </button>
            </template>
        </AppModal>

        <!-- Complete Task (Accomplishment Report) Modal -->
        <AppModal v-model="showCompleteModal" title="Complete Task" tone="approve">
            <p class="mb-3 text-sm text-muted-foreground">Write your accomplishment report for this task before marking it as complete.</p>
            <label for="accomplishment-report" class="mb-1 block text-sm font-medium text-foreground">Accomplishment Report *</label>
            <textarea
                id="accomplishment-report"
                v-model="accomplishmentReport"
                rows="5"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none"
                placeholder="Describe what you accomplished for this task…"
            />
            <template #actions>
                <button
                    type="button"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-foreground transition hover:bg-muted"
                    @click="cancelComplete"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :disabled="completeSubmitLoading || !accomplishmentReport.trim()"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60"
                    @click="confirmComplete"
                >
                    <CheckCircle2 class="size-4" />
                    {{ completeSubmitLoading ? 'Completing…' : 'Complete Task' }}
                </button>
            </template>
        </AppModal>
    </AppLayout>
</template>

<style scoped>
.task-calendar-box :deep(.vc-container) {
    border: 0;
    background: transparent;
}
.task-calendar-inline :deep(.vc-weeks),
.task-calendar-inline :deep(.vc-week),
.task-calendar-inline :deep(.vc-day),
.task-calendar-inline :deep(.vc-day-content) {
    background: transparent;
}
.task-calendar-inline :deep(.vc-title),
.task-calendar-inline :deep(.vc-weekday) {
    color: hsl(var(--muted-foreground));
    font-weight: 600;
}
.task-calendar-inline :deep(.vc-day-content:hover) {
    background: color-mix(in srgb, hsl(var(--primary)) 18%, white);
}
.task-calendar-inline :deep(.vc-highlight-bg-solid) {
    background-color: hsl(var(--primary));
}

/* Same as Leave Application: weekdays-only (Mon–Fri), no Sunday or Saturday */
.task-calendar-box :deep(.vc-weekdays),
.task-calendar-box :deep(.vc-week) {
    grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
}

.task-calendar-box :deep(.vc-weekdays .vc-weekday-1),
.task-calendar-box :deep(.vc-weekdays .vc-weekday-7),
.task-calendar-box :deep(.vc-week .weekday-1),
.task-calendar-box :deep(.vc-week .weekday-7),
.task-calendar-box :deep(.vc-weekdays > *:nth-child(1)),
.task-calendar-box :deep(.vc-weekdays > *:nth-child(7)),
.task-calendar-box :deep(.vc-week > *:nth-child(1)),
.task-calendar-box :deep(.vc-week > *:nth-child(7)) {
    display: none !important;
}

/* Task view calendar (dashboard): compact height */
.task-calendar-box--large :deep(.vc-title) {
    font-size: 1rem;
}
.task-calendar-box--large :deep(.vc-weekday),
.task-calendar-box--large :deep(.vc-day-content) {
    font-size: 0.8125rem;
}
.task-calendar-box--large :deep(.vc-day) {
    min-height: 2rem;
}
.task-calendar-box--large :deep(.vc-weeks) {
    min-height: 6rem;
}

/* Task view calendar: highlight range spans */
.task-view-calendar :deep(.vc-highlight) {
    opacity: 0.85;
}

/* Dots under date – rendered side-by-side when multiple tasks share a day */
.task-view-calendar :deep(.vc-dots) {
    display: flex;
    justify-content: center;
    gap: 3px;
    margin-top: 2px;
}
.task-view-calendar :deep(.vc-dot) {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

/* Custom task tooltip (positioned via Teleport to body) */
.task-tooltip {
    position: fixed;
    z-index: 100;
    transform: translate(-50%, -100%) translateY(-8px);
    pointer-events: none;
    min-width: 160px;
    max-width: 280px;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid hsl(var(--border));
    background: #fff;
    box-shadow: 0 4px 16px hsl(0 0% 0% / 0.12);
    font-size: 0.75rem;
    line-height: 1.3;
}

.task-tooltip-row {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0;
}

.task-tooltip-dot {
    flex-shrink: 0;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.task-tooltip-priority {
    flex-shrink: 0;
    font-weight: 700;
    color: hsl(var(--muted-foreground));
}

.task-tooltip-label {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: hsl(var(--foreground));
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.1s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Clock hands animation on clock in/out – only the arms rotate, smooth ease-in-out */
.clock-hand {
    transform-origin: 12px 12px;
}
@keyframes clock-minute-turn {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
@keyframes clock-hour-turn {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.clock-hands-spin .clock-hand-minute {
    animation: clock-minute-turn 0.6s ease-in-out;
}
.clock-hands-spin .clock-hand-hour {
    animation: clock-hour-turn 1.2s ease-in-out;
}
</style>
