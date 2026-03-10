<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, Clock, Eye, FolderOpen, ListPlus, Pause, Play, Search, Trash2, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Calendar as VCalendar, DatePicker } from 'v-calendar';
import { toast } from 'vue3-toastify';
import AppLayout from '@/layouts/AppLayout.vue';
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

const pageTitle = 'Self-Service - Timezone';

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

const PRIORITY_ORDER: Record<'High' | 'Medium' | 'Low', number[]> = {
    High: [0, 1, 2],   // High first, then Medium, then Low
    Medium: [1, 0, 2], // Medium first, then High, then Low
    Low: [2, 1, 0],    // Low first, then Medium, then High
};
const PRIORITY_RANK: Record<string, number> = { High: 0, Medium: 1, Low: 2 };

function filterAndSortTasks(tasks: TaskItem[]): TaskItem[] {
    const q = taskSearchQuery.value.trim().toLowerCase();
    const sortKey = taskPrioritySort.value;
    const order = PRIORITY_ORDER[sortKey];
    let list = tasks;
    if (q) {
        list = list.filter(
            (t) =>
                t.title.toLowerCase().includes(q) ||
                (t.description && t.description.toLowerCase().includes(q)),
        );
    }
    return [...list].sort((a, b) => {
        const rankA = order[PRIORITY_RANK[a.priority] ?? 1];
        const rankB = order[PRIORITY_RANK[b.priority] ?? 1];
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
}

function normalizedStatus(t: TaskItem): string {
    return t.status === 'open' ? 'Not Started' : t.status;
}

function updateTaskStatus(taskId: number, status: string): void {
    taskActionLoading.value = true;
    router.put(`/self-service/timezone/tasks/${taskId}`, { status }, {
        preserveScroll: true,
        onFinish: () => { taskActionLoading.value = false; },
        onSuccess: () => { closeViewModal(); },
    });
}

function deleteTask(taskId: number): void {
    if (!confirm('Delete this task?')) return;
    taskActionLoading.value = true;
    router.delete(`/self-service/timezone/tasks/${taskId}`, {
        preserveScroll: true,
        onFinish: () => { taskActionLoading.value = false; },
        onSuccess: () => { closeViewModal(); },
    });
}

// Create Task modal
const showCreateTaskModal = ref(false);
const taskTitle = ref('');
const taskDescription = ref('');
const taskPriority = ref<'Low' | 'Medium' | 'High'>('Low');
const taskDueDateRange = ref<{ start: Date; end: Date } | null>(null);
const taskCalendarKey = ref(0);
const taskSubmitLoading = ref(false);

// Disable Saturday and Sunday in task due date picker (weekdays 1 = Sunday, 7 = Saturday)
/* Disable Saturday and Sunday (v-calendar: 1=Sunday, 7=Saturday) */
const taskDisabledDates = [{ repeat: { weekdays: [1, 7] } }];

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

const taskViewAttributes = computed(() => {
    const out: Array<{ key: string; dates: Date; order: number; bar: { color: string }; popover: { label: string; visibility: string } }> = [];
    (props.openTasks ?? []).forEach((t, taskIndex) => {
        const days = getDaysInRange(t.due_date, t.due_date_end && t.due_date_end !== t.due_date ? t.due_date_end : null);
        const barColor = t.priority === 'High' ? 'red' : t.priority === 'Medium' ? 'orange' : 'green';
        days.forEach((day, dayIndex) => {
            out.push({
                key: `task-${t.id}-${day.getTime()}`,
                dates: day,
                order: taskIndex * 1000 + dayIndex,
                bar: { color: barColor },
                popover: { label: t.title, visibility: 'hover' },
            });
        });
    });
    return out;
});

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
        toast.error('Task description is required.');
        return;
    }
    const range = taskDueDateRange.value;
    if (!range?.start) {
        toast.error('Task due date is required.');
        return;
    }
    const end = range.end && range.end >= range.start ? range.end : range.start;
    taskSubmitLoading.value = true;
    router.post('/self-service/timezone/tasks', {
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
    router.post('/self-service/timezone/clock-in', {}, {
        preserveScroll: true,
        onFinish: () => { clockLoading.value = false; },
    });
}

function clockOut(): void {
    if (clockLoading.value) return;
    clockLoading.value = true;
    router.post('/self-service/timezone/clock-out', {}, {
        preserveScroll: true,
        onFinish: () => { clockLoading.value = false; },
    });
}

function toggleClock(): void {
    if (clockLoading.value) return;
    clockIconSpinning.value = true;
    setTimeout(() => { clockIconSpinning.value = false; }, 600);
    if (isClockedIn.value) clockOut();
    else clockIn();
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page timezone-attendance-page">
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
                        />
                    </div>
                </div>

                <!-- Activity Logs card (right) – hours worked + Clock In button in empty space -->
                <article
                    class="flex min-h-0 flex-col justify-between gap-3 rounded-2xl p-4 text-white"
                    :class="isClockedIn ? 'bg-green-500' : 'bg-red-500'"
                >
                    <Link
                        :href="'/self-service/time-logs'"
                        class="flex flex-col gap-3 transition hover:opacity-95"
                    >
                        <span
                            class="inline-block shrink-0"
                            :class="{ 'clock-icon-spin': clockIconSpinning }"
                        >
                            <Clock class="size-10 opacity-90" />
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
                    <div class="pt-2">
                        <p class="mb-1 text-xs font-medium opacity-90">You are currently {{ isClockedIn ? 'Clocked In' : 'Clocked Out' }}</p>
                        <button
                            type="button"
                            :disabled="clockLoading"
                            :class="[
                                'inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 disabled:opacity-60 disabled:pointer-events-none',
                                isClockedIn
                                    ? 'bg-amber-400 text-amber-950 hover:bg-amber-500 focus:ring-offset-green-500'
                                    : 'bg-green-500 text-white hover:bg-green-600 focus:ring-offset-red-500',
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
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <div class="relative flex-1 min-w-[180px]">
                            <Search class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                v-model="taskSearchQuery"
                                type="search"
                                placeholder="Search Task"
                                class="w-full rounded-md border border-input bg-background py-1.5 pl-8 pr-3 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <label for="task-priority-sort" class="text-xs font-medium text-muted-foreground">Sort by priority</label>
                            <select
                                id="task-priority-sort"
                                v-model="taskPrioritySort"
                                class="rounded-md border border-input bg-background px-2 py-1.5 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            >
                                <option value="High">High first</option>
                                <option value="Medium">Medium first</option>
                                <option value="Low">Low first</option>
                            </select>
                        </div>
                    </div>
                    <div v-if="tasksTab === 'open'" class="min-h-[120px]">
                        <ul v-if="sortedFilteredOpenTasks.length" class="space-y-2">
                            <li
                                v-for="t in sortedFilteredOpenTasks"
                                :key="t.id"
                                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border/80 bg-muted/20 px-3 py-2 text-sm"
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
                                            @click="updateTaskStatus(t.id, 'In Progress')"
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
                                            @click="updateTaskStatus(t.id, 'Complete')"
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
                                            @click="updateTaskStatus(t.id, 'In Progress')"
                                        >
                                            <Play class="size-3.5" />
                                            Resume Task
                                        </button>
                                    </template>
                                    <button
                                        type="button"
                                        :disabled="taskActionLoading"
                                        class="inline-flex items-center gap-1 rounded border border-red-300 bg-red-50 px-2 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 disabled:opacity-60"
                                        @click="deleteTask(t.id)"
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

        <!-- View Task modal -->
        <Teleport to="body">
            <div
                v-if="viewTask"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeViewModal"
            >
                <div
                    class="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-lg"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="view-task-title"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h2 id="view-task-title" class="text-lg font-semibold text-foreground">Task details</h2>
                        <button
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-muted"
                            aria-label="Close"
                            @click="closeViewModal"
                        >
                            <X class="size-5" />
                        </button>
                    </div>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-muted-foreground">Title</dt>
                            <dd class="mt-0.5 font-medium text-foreground">{{ viewTask.title }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-muted-foreground">Description</dt>
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
                    </dl>
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
                            <label for="task-description" class="block text-sm font-medium text-foreground">Task Description *</label>
                            <textarea
                                id="task-description"
                                v-model="taskDescription"
                                rows="4"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none"
                                placeholder="Enter task description"
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
                                    :disabled-dates="taskDisabledDates"
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

/* Task view calendar: bar under date and popover */
.task-view-calendar :deep(.vc-bars) {
    width: 100%;
}
.task-view-calendar :deep(.vc-bar) {
    height: 4px;
    border-radius: 2px;
}

/* Popover: show all tasks, each with visible priority color (green = low, orange = medium, red = high) */
.task-view-calendar :deep(.vc-day-popover) {
    max-height: min(70vh, 400px);
    overflow-y: auto;
}
.task-view-calendar :deep(.vc-day-popover-row) {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 1.75rem;
    padding: 0.25rem 0;
}
.task-view-calendar :deep(.vc-day-popover-row-indicator) {
    flex-shrink: 0;
    width: 16px;
    min-width: 16px;
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
}
.task-view-calendar :deep(.vc-day-popover-row-indicator span) {
    display: block;
    width: 100%;
    height: 100%;
    border-radius: 3px;
    background: var(--vc-accent-500);
}
.task-view-calendar :deep(.vc-day-popover-row-label) {
    flex: 1;
    min-width: 0;
}

/* Clock icon spin animation on clock in/out */
@keyframes clock-turn {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.clock-icon-spin {
    animation: clock-turn 0.6s ease-in-out;
}
</style>
