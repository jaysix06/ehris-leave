<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
} from 'chart.js';
import {
    Activity,
    ArrowRight,
    CalendarRange,
    ClipboardCheck,
    IdCard,
    Settings,
    UserCircle,
    Users,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { Component } from 'vue';
import { Bar, Doughnut, Line } from 'vue-chartjs';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    dashboard,
    myDetails,
    utilities,
} from '@/routes';
import {
    idCard as selfServiceIdCard,
    wfhTimeInOut as selfServiceWFHAttendance,
} from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, LineElement, PointElement, Tooltip, Legend);

const pageTitle = 'Dashboard';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
        href: dashboard().url,
    },
];

const page = usePage();
const authUserRole = computed(() => String((page.props.auth?.user as Record<string, unknown> | undefined)?.role ?? '').trim().toLowerCase());
const isEmployeeOrTeacher = computed(() => ['employee', 'teacher'].includes(authUserRole.value));
const canViewOverviewMetrics = computed(() => !['employee', 'teacher'].includes(authUserRole.value));

type DashboardAttendance = {
    isClockedIn: boolean;
    hoursWorkedThisWeek: string;
    lastTimeIn: string | null;
    lastTimeOut: string | null;
};
type DashboardAttendanceTrends = {
    recentTimeline: number[];
    monthlyLateCount: number;
    monthlyUndertimeCount: number;
};
const dashboardAttendance = computed<DashboardAttendance>(() => {
    const source = (page.props.dashboardAttendance as Partial<DashboardAttendance> | undefined) ?? {};
    return {
        isClockedIn: Boolean(source.isClockedIn),
        hoursWorkedThisWeek: typeof source.hoursWorkedThisWeek === 'string' ? source.hoursWorkedThisWeek : '00:00:00',
        lastTimeIn: typeof source.lastTimeIn === 'string' ? source.lastTimeIn : null,
        lastTimeOut: typeof source.lastTimeOut === 'string' ? source.lastTimeOut : null,
    };
});
const dashboardAttendanceTrends = computed<DashboardAttendanceTrends>(() => {
    const source = (page.props.dashboardAttendanceTrends as Partial<DashboardAttendanceTrends> | undefined) ?? {};
    return {
        recentTimeline: Array.isArray(source.recentTimeline) && source.recentTimeline.length === 7
            ? source.recentTimeline.map(value => (Number(value) > 0 ? 1 : 0))
            : [0, 0, 0, 0, 0, 0, 0],
        monthlyLateCount: Number.isFinite(Number(source.monthlyLateCount)) ? Number(source.monthlyLateCount) : 0,
        monthlyUndertimeCount: Number.isFinite(Number(source.monthlyUndertimeCount)) ? Number(source.monthlyUndertimeCount) : 0,
    };
});
const clockStatusLabel = computed(() => (dashboardAttendance.value.isClockedIn ? 'Clocked In' : 'Clocked Out'));
const clockStatusClass = computed(() =>
    dashboardAttendance.value.isClockedIn
        ? 'border-emerald-200 bg-emerald-500/10 text-emerald-700 dark:border-emerald-900/70 dark:text-emerald-300'
        : 'border-slate-200 bg-slate-500/10 text-slate-700 dark:border-slate-700 dark:text-slate-300',
);

function formatClockStamp(value: string | null): string {
    if (!value) {
        return '-';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '-';
    }

    return date.toLocaleString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}
const showPopups = computed(() => (page.props.showPopups ?? false) as boolean);
const activePopups = computed(() => (page.props.activePopups ?? []) as Array<{
    id: number;
    message: string;
    link: string | null;
    status: string;
    created_at: string;
}>);

const dismissedPopups = ref<number[]>([]);

const visiblePopups = computed(() => {
    if (!showPopups.value) {
        return [];
    }
    return activePopups.value.filter(popup => !dismissedPopups.value.includes(popup.id));
});

const dismissPopup = (id: number) => {
    dismissedPopups.value.push(id);
};

// ---------------------------------------------------------------------------
// Theme-reactive chart rendering
// ---------------------------------------------------------------------------
const themeKey = ref(0);
let themeObserver: MutationObserver | null = null;

function cssVar(name: string): string {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

onMounted(() => {
    themeObserver = new MutationObserver(() => { themeKey.value++; });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onBeforeUnmount(() => {
    themeObserver?.disconnect();
});

// ---------------------------------------------------------------------------
// Stats
// ---------------------------------------------------------------------------
interface StatItem { title: string; value: string; icon: Component; color: string }

const stats: StatItem[] = [
    { title: 'Active Employees', value: '214', icon: Users, color: 'text-blue-600 bg-blue-500/10 dark:text-blue-400 dark:bg-blue-400/10' },
    { title: 'Pending Requests', value: '18', icon: ClipboardCheck, color: 'text-amber-600 bg-amber-500/10 dark:text-amber-400 dark:bg-amber-400/10' },
    { title: 'Upcoming Leaves', value: '7', icon: CalendarRange, color: 'text-emerald-600 bg-emerald-500/10 dark:text-emerald-400 dark:bg-emerald-400/10' },
    { title: 'Today Activity Logs', value: '63', icon: Activity, color: 'text-violet-600 bg-violet-500/10 dark:text-violet-400 dark:bg-violet-400/10' },
];

// ---------------------------------------------------------------------------
// Charts — Monthly Leave Trend (Bar)
// ---------------------------------------------------------------------------
const durationToHours = (value: string): number => {
    const [rawHours, rawMinutes, rawSeconds] = value.split(':');
    const hours = Number.parseInt(rawHours ?? '0', 10);
    const minutes = Number.parseInt(rawMinutes ?? '0', 10);
    const seconds = Number.parseInt(rawSeconds ?? '0', 10);

    if (Number.isNaN(hours) || Number.isNaN(minutes) || Number.isNaN(seconds)) {
        return 0;
    }

    return Math.round((hours + (minutes / 60) + (seconds / 3600)) * 100) / 100;
};

const expectedWeeklyHours = 40;
const workedWeeklyHours = computed(() => durationToHours(dashboardAttendance.value.hoursWorkedThisWeek));
const recentAttendanceTimeline = computed(() => dashboardAttendanceTrends.value.recentTimeline);
const monthlyLateCount = computed(() => dashboardAttendanceTrends.value.monthlyLateCount);
const monthlyUndertimeCount = computed(() => dashboardAttendanceTrends.value.monthlyUndertimeCount);

const workHoursTrendData = computed(() => {
    void themeKey.value;

    return {
        labels: ['Worked Hours', 'Expected Hours'],
        datasets: [
            {
                label: 'Hours',
                data: [workedWeeklyHours.value, expectedWeeklyHours],
                backgroundColor: [cssVar('--chart-1'), cssVar('--chart-3')],
                borderRadius: 8,
                borderSkipped: false as const,
            },
        ],
    };
});

const workHoursTrendOptions = computed(() => {
    void themeKey.value;
    const mutedFg = cssVar('--muted-foreground');
    const border = cssVar('--border');

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: cssVar('--card'),
                titleColor: cssVar('--foreground'),
                bodyColor: mutedFg,
                borderColor: border,
                borderWidth: 1,
                cornerRadius: 8,
                padding: 10,
                titleFont: { family: 'Manrope', weight: 'bold' as const },
                bodyFont: { family: 'Manrope' },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: mutedFg, font: { size: 12, family: 'Manrope', weight: 'bold' as const } },
                border: { display: false },
            },
            y: {
                beginAtZero: true,
                suggestedMax: expectedWeeklyHours,
                grid: { color: border },
                ticks: { color: mutedFg, font: { size: 12, family: 'Manrope' } },
                border: { display: false },
            },
        },
    };
});

const attendanceTimelineData = computed(() => {
    void themeKey.value;

    return {
        labels: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        datasets: [
            {
                label: 'Attendance',
                data: recentAttendanceTimeline.value,
                borderColor: cssVar('--chart-2'),
                backgroundColor: cssVar('--chart-2'),
                tension: 0.35,
                pointRadius: 3,
                pointHoverRadius: 4,
                fill: false,
            },
        ],
    };
});

const attendanceTimelineOptions = computed(() => {
    void themeKey.value;
    const mutedFg = cssVar('--muted-foreground');
    const border = cssVar('--border');

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: cssVar('--card'),
                titleColor: cssVar('--foreground'),
                bodyColor: mutedFg,
                borderColor: border,
                borderWidth: 1,
                cornerRadius: 8,
                padding: 10,
                titleFont: { family: 'Manrope', weight: 'bold' as const },
                bodyFont: { family: 'Manrope' },
                callbacks: {
                    label(context: { parsed: { y: number } }): string {
                        return context.parsed.y > 0 ? 'Present' : 'Absent/Leave/WFH';
                    },
                },
            },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: mutedFg, font: { size: 12, family: 'Manrope' } },
                border: { display: false },
            },
            y: {
                min: 0,
                max: 1,
                ticks: {
                    color: mutedFg,
                    stepSize: 1,
                    callback(value: string | number): string {
                        return Number(value) === 1 ? 'Present' : 'Absent';
                    },
                    font: { size: 12, family: 'Manrope' },
                },
                grid: { color: border },
                border: { display: false },
            },
        },
    };
});

const leaveOverviewData = computed(() => {
    void themeKey.value;

    return {
        labels: ['Vacation', 'Sick', 'Emergency'],
        datasets: [
            {
                data: [0, 0, 0],
                backgroundColor: [cssVar('--chart-3'), cssVar('--chart-4'), cssVar('--chart-5')],
                borderColor: cssVar('--card'),
                borderWidth: 2,
            },
        ],
    };
});

const leaveOverviewOptions = computed(() => {
    void themeKey.value;
    const mutedFg = cssVar('--muted-foreground');

    return {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
            legend: {
                position: 'bottom' as const,
                labels: {
                    color: mutedFg,
                    usePointStyle: true,
                    pointStyle: 'circle' as const,
                    padding: 14,
                    font: { size: 12, family: 'Manrope', weight: 'bold' as const },
                },
            },
            tooltip: { enabled: false },
        },
    };
});

const requestStatusData = computed(() => {
    void themeKey.value;

    return {
        labels: ['Pending', 'Approved', 'Rejected'],
        datasets: [
            {
                label: 'Requests',
                data: [0, 0, 0],
                backgroundColor: [cssVar('--chart-3'), cssVar('--chart-2'), cssVar('--chart-5')],
                borderRadius: 8,
                borderSkipped: false as const,
            },
        ],
    };
});

const requestStatusOptions = computed(() => {
    void themeKey.value;
    const mutedFg = cssVar('--muted-foreground');
    const border = cssVar('--border');

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false },
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: mutedFg, font: { size: 12, family: 'Manrope', weight: 'bold' as const } },
                border: { display: false },
            },
            y: {
                beginAtZero: true,
                suggestedMax: 3,
                grid: { color: border },
                ticks: { color: mutedFg, font: { size: 12, family: 'Manrope' } },
                border: { display: false },
            },
        },
    };
});

// ---------------------------------------------------------------------------
// Action Cards
// ---------------------------------------------------------------------------
interface ActionCard {
    title: string;
    description: string;
    icon: Component;
    href: string;
    color: string;
}

const actionCards = computed<ActionCard[]>(() => {
    const cards: ActionCard[] = [];

    if (isEmployeeOrTeacher.value) {
        cards.push({
            title: 'WFH Attendance',
            description: 'Log your daily time in/out and accomplishment report.',
            icon: Activity,
            href: selfServiceWFHAttendance().url,
            color: 'text-cyan-600 bg-cyan-500/10 dark:text-cyan-400 dark:bg-cyan-400/10',
        });
    }

    cards.push(
        // {
        //     title: 'Leave Application',
        //     description: 'Apply for leave or check your balance.',
        //     icon: CalendarRange,
        //     href: selfServiceLeaveApplication().url,
        //     color: 'text-emerald-600 bg-emerald-500/10 dark:text-emerald-400 dark:bg-emerald-400/10',
        // },
        {
            title: 'My ID Card',
            description: 'View or download your employee ID card.',
            icon: IdCard,
            href: selfServiceIdCard().url,
            color: 'text-blue-600 bg-blue-500/10 dark:text-blue-400 dark:bg-blue-400/10',
        },
        {
            title: 'My Details',
            description: 'View and update your personal information.',
            icon: UserCircle,
            href: myDetails().url,
            color: 'text-violet-600 bg-violet-500/10 dark:text-violet-400 dark:bg-violet-400/10',
        },
        // {
        //     title: 'Request Status',
        //     description: 'Track your pending and completed requests.',
        //     icon: FileClock,
        //     href: requestStatusMyRequests().url,
        //     color: 'text-amber-600 bg-amber-500/10 dark:text-amber-400 dark:bg-amber-400/10',
        // },
    );

    if (canViewOverviewMetrics.value) {
        cards.push({
            title: 'Utilities',
            description: 'Manage leave types, reporting managers, and system settings.',
            icon: Settings,
            href: utilities().url,
            color: 'text-slate-600 bg-slate-500/10 dark:text-slate-400 dark:bg-slate-400/10',
        });
    }

    return cards;
});

const footerNavItems = [];
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <!-- Popup Messages -->
        <div
            v-if="visiblePopups.length > 0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            style="pointer-events: none;"
        >
            <div class="w-full max-w-2xl space-y-4" style="pointer-events: auto;">
                <div
                    v-for="popup in visiblePopups"
                    :key="popup.id"
                    class="relative rounded-lg border border-primary/20 bg-card p-6 shadow-lg"
                >
                    <button
                        type="button"
                        class="absolute right-2 top-2 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-background/95 text-muted-foreground shadow-lg transition-all hover:bg-destructive hover:text-destructive-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                        @click="dismissPopup(popup.id)"
                        aria-label="Close popup"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="pr-12">
                        <p class="text-lg font-semibold text-foreground">{{ popup.message }}</p>

                        <div v-if="popup.link" class="mt-4">
                            <a
                                :href="popup.link"
                                class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
                            >
                                Click Here
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ehris-page">
            <!-- Stats Overview (admin/HR/manager roles) -->
            <section v-if="canViewOverviewMetrics">
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-foreground">Overview</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Summary of key HR metrics for today.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="item in stats"
                        :key="item.title"
                        class="ehris-card flex items-start gap-4"
                    >
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg" :class="item.color">
                            <component :is="item.icon" class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-2xl font-bold leading-tight text-foreground">{{ item.value }}</p>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ item.title }}</p>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Clock In/Out Status (employees/teachers) -->
            <section v-if="isEmployeeOrTeacher" class="ehris-card">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl" :class="clockStatusClass">
                            <Activity class="size-6" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Today's Status</p>
                            <p class="text-2xl font-bold text-foreground">{{ clockStatusLabel }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                        <div>
                            <span class="text-muted-foreground">Time In: </span>
                            <span class="font-medium text-foreground">{{ formatClockStamp(dashboardAttendance.lastTimeIn) }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">Time Out: </span>
                            <span class="font-medium text-foreground">{{ formatClockStamp(dashboardAttendance.lastTimeOut) }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">This Week: </span>
                            <span class="font-medium text-foreground">{{ dashboardAttendance.hoursWorkedThisWeek }}</span>
                        </div>
                    </div>

                    <Link
                        :href="selfServiceWFHAttendance().url"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-primary transition hover:underline lg:ml-4"
                    >
                        Open WFH Attendance
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>
            </section>

            <!-- Action Cards -->
            <section>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="card in actionCards"
                        :key="card.title"
                        :href="card.href"
                        class="group relative flex items-start gap-4 rounded-2xl border border-border/80 bg-card p-5 shadow-sm transition-all hover:border-primary/30 hover:shadow-md"
                    >
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl" :class="card.color">
                            <component :is="card.icon" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-foreground group-hover:text-primary">{{ card.title }}</p>
                            <p class="mt-1 text-sm leading-relaxed text-muted-foreground">{{ card.description }}</p>
                        </div>
                        <ArrowRight class="mt-1 size-4 shrink-0 text-muted-foreground/40 transition-all group-hover:translate-x-0.5 group-hover:text-primary" />
                    </Link>
                </div>
            </section>

            <section class="space-y-4">

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <article class="ehris-card xl:col-span-2">
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-foreground">Work Hours &amp; Attendance Trends</h3>
                            <p class="mt-0.5 text-sm text-muted-foreground">Hours this week, recent activity, and monthly counts.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Hours This Week vs Expected</p>
                                <div class="min-h-[220px]">
                                    <Bar :key="`work-hours-${themeKey}`" :data="workHoursTrendData" :options="workHoursTrendOptions" />
                                </div>
                            </div>
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Recent 7-Day Timeline</p>
                                <div class="min-h-[220px]">
                                    <Line :key="`work-timeline-${themeKey}`" :data="attendanceTimelineData" :options="attendanceTimelineOptions" />
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 sm:max-w-sm">
                            <div class="rounded-xl border border-border bg-muted/25 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-muted-foreground">Late Count (Month)</p>
                                <p class="mt-1 text-xl font-bold text-foreground">{{ monthlyLateCount }}</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/25 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-muted-foreground">Undertime (Month)</p>
                                <p class="mt-1 text-xl font-bold text-foreground">{{ monthlyUndertimeCount }}</p>
                            </div>
                        </div>
                    </article>

                    <article class="ehris-card relative">
                        <div class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center rounded-2xl bg-background/70 backdrop-blur-[1px]">
                            <span class="rounded-md border border-border bg-card px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Under Construction
                            </span>
                        </div>
                        <div class="mb-4">
                            <h3 class="text-lg font-bold text-foreground">Leave Overview</h3>
                            <p class="mt-0.5 text-sm text-muted-foreground">Under construction.</p>
                        </div>
                        <div class="relative mx-auto h-[220px] w-[220px]">
                            <Doughnut :key="`leave-overview-${themeKey}`" :data="leaveOverviewData" :options="leaveOverviewOptions" />
                        </div>
                        <ul class="mt-4 space-y-2 text-sm text-muted-foreground">
                            <li>Leave balance by type: N/A</li>
                            <li>Upcoming approved leaves: N/A</li>
                            <li>Recent leave history: N/A</li>
                        </ul>
                    </article>
                </div>

                <article class="ehris-card relative">
                    <div class="pointer-events-none absolute inset-0 z-10 flex items-center justify-center rounded-2xl bg-background/70 backdrop-blur-[1px]">
                        <span class="rounded-md border border-border bg-card px-3 py-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Under Construction
                        </span>
                    </div>
                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-foreground">Request &amp; Ticket Status</h3>
                        <p class="mt-0.5 text-sm text-muted-foreground">Under construction.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1.2fr_1fr]">
                        <div class="min-h-[220px]">
                            <Bar :key="`request-status-${themeKey}`" :data="requestStatusData" :options="requestStatusOptions" />
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            <div class="rounded-xl border border-border bg-muted/25 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-muted-foreground">My Open Requests</p>
                                <p class="mt-1 text-xl font-bold text-foreground">N/A</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/25 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-muted-foreground">Latest Requests</p>
                                <p class="mt-1 text-xl font-bold text-foreground">N/A</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/25 px-4 py-3">
                                <p class="text-xs uppercase tracking-wide text-muted-foreground">Needs Action</p>
                                <p class="mt-1 text-xl font-bold text-foreground">N/A</p>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </AppLayout>
</template>
