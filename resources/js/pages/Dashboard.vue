<script setup lang="ts">
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Tooltip,
} from 'chart.js';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    CalendarRange,
    ClipboardCheck,
    CreditCard,
    FileText,
    IdCard,
    LayoutGrid,
    Settings,
    UserCircle,
    Users,
    UsersRound,
} from 'lucide-vue-next';
import { Bar, Doughnut } from 'vue-chartjs';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    dashboard,
    employeeManagement,
    myDetails,
    reports,
    requestStatus,
    selfService,
    utilities,
} from '@/routes';
import { leaveApplication, idCard as selfServiceIdCard } from '@/routes/self-service';
import { employeeListing } from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';
import type { Component } from 'vue';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Tooltip, Legend);

const pageTitle = 'Dashboard';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
        href: dashboard().url,
    },
];

const page = usePage();
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
    // Only show popups if showPopups flag is true (after login)
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
const leaveMonthlyData = computed(() => {
    void themeKey.value;
    return {
        labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
        datasets: [
            {
                label: 'Approved',
                data: [12, 19, 8, 15, 22, 14],
                backgroundColor: cssVar('--chart-2'),
                borderRadius: 6,
                borderSkipped: false as const,
            },
            {
                label: 'Pending',
                data: [3, 5, 2, 8, 4, 6],
                backgroundColor: cssVar('--chart-3'),
                borderRadius: 6,
                borderSkipped: false as const,
            },
        ],
    };
});

const leaveMonthlyOptions = computed(() => {
    void themeKey.value;
    const mutedFg = cssVar('--muted-foreground');
    const border = cssVar('--border');

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top' as const,
                align: 'end' as const,
                labels: {
                    color: mutedFg,
                    usePointStyle: true,
                    pointStyle: 'rectRounded' as const,
                    padding: 16,
                    font: { size: 12, family: 'Manrope', weight: 'bold' as const },
                },
            },
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
                ticks: { color: mutedFg, font: { size: 12, family: 'Manrope' } },
                border: { display: false },
            },
            y: {
                grid: { color: border },
                ticks: { color: mutedFg, font: { size: 12, family: 'Manrope' }, stepSize: 5 },
                border: { display: false },
            },
        },
    };
});

// ---------------------------------------------------------------------------
// Charts — Leave Status (Doughnut)
// ---------------------------------------------------------------------------
const leaveStatusData = computed(() => {
    void themeKey.value;
    return {
        labels: ['Approved', 'Pending', 'Rejected'],
        datasets: [
            {
                data: [45, 18, 5],
                backgroundColor: [cssVar('--chart-2'), cssVar('--chart-3'), cssVar('--chart-5')],
                borderWidth: 0,
                spacing: 3,
                borderRadius: 4,
            },
        ],
    };
});

const leaveStatusOptions = computed(() => {
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
                    padding: 16,
                    font: { size: 12, family: 'Manrope', weight: 'bold' as const },
                },
            },
            tooltip: {
                backgroundColor: cssVar('--card'),
                titleColor: cssVar('--foreground'),
                bodyColor: mutedFg,
                borderColor: cssVar('--border'),
                borderWidth: 1,
                cornerRadius: 8,
                padding: 10,
                titleFont: { family: 'Manrope', weight: 'bold' as const },
                bodyFont: { family: 'Manrope' },
            },
        },
    };
});

const leaveStatusTotal = computed(() => {
    const ds = leaveStatusData.value.datasets[0];
    return (ds.data as number[]).reduce((sum, n) => sum + n, 0);
});

// ---------------------------------------------------------------------------
// Quick Actions
// ---------------------------------------------------------------------------
interface QuickAction { title: string; description: string; icon: Component; href: string; color: string }

const quickActions: QuickAction[] = [
    {
        title: 'Apply for Leave',
        description: 'Submit a new leave application',
        icon: CalendarRange,
        href: leaveApplication().url,
        color: 'text-emerald-600 bg-emerald-500/10 dark:text-emerald-400 dark:bg-emerald-400/10',
    },
    {
        title: 'My ID Card',
        description: 'View or update your ID card',
        icon: IdCard,
        href: selfServiceIdCard().url,
        color: 'text-blue-600 bg-blue-500/10 dark:text-blue-400 dark:bg-blue-400/10',
    },
    {
        title: 'My Details',
        description: 'View your personal information',
        icon: UserCircle,
        href: myDetails().url,
        color: 'text-violet-600 bg-violet-500/10 dark:text-violet-400 dark:bg-violet-400/10',
    },
    {
        title: 'Request Status',
        description: 'Track your pending requests',
        icon: ClipboardCheck,
        href: requestStatus().url,
        color: 'text-amber-600 bg-amber-500/10 dark:text-amber-400 dark:bg-amber-400/10',
    },
];

// ---------------------------------------------------------------------------
// Explore Cards
// ---------------------------------------------------------------------------
interface PageCard { title: string; description: string; icon: Component; href: string; color: string; links: { label: string; href: string }[] }

const pageCards: PageCard[] = [
    {
        title: 'Self-Service',
        description: 'Access your personal HR services including leave applications, ID card, service record, and more.',
        icon: CreditCard,
        href: selfService().url,
        color: 'text-emerald-600 bg-emerald-500/10 dark:text-emerald-400 dark:bg-emerald-400/10',
        links: [
            { label: 'Leave Application', href: leaveApplication().url },
            { label: 'ID Card', href: selfServiceIdCard().url },
        ],
    },
    {
        title: 'Employee Management',
        description: 'Manage employee profiles, PSIPOP updates, ID card printing, and DepEd email requests.',
        icon: UsersRound,
        href: employeeManagement().url,
        color: 'text-blue-600 bg-blue-500/10 dark:text-blue-400 dark:bg-blue-400/10',
        links: [],
    },
    {
        title: 'Reports',
        description: 'Generate and export employee listings, summary statistics, and other HR reports.',
        icon: FileText,
        href: reports().url,
        color: 'text-violet-600 bg-violet-500/10 dark:text-violet-400 dark:bg-violet-400/10',
        links: [
            { label: 'Employee Listing', href: employeeListing().url },
        ],
    },
    {
        title: 'Utilities',
        description: 'Configure system settings such as leave types, reporting managers, user list, and activity logs.',
        icon: Settings,
        href: utilities().url,
        color: 'text-amber-600 bg-amber-500/10 dark:text-amber-400 dark:bg-amber-400/10',
        links: [
            { label: 'Reporting Manager', href: '/utilities/reporting-manager' },
            { label: 'Activity Log', href: '/utilities/activity-log' },
        ],
    },
];
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
                        class="absolute right-4 top-4 text-muted-foreground hover:text-foreground"
                        @click="dismissPopup(popup.id)"
                    >
                        <X class="h-5 w-5" />
                    </button>

                    <div class="pr-8">
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
            <!-- Stats Overview -->
            <section>
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

            <!-- Charts -->
            <section class="grid grid-cols-1 gap-4 lg:grid-cols-[1.6fr_1fr]">
                <div class="ehris-card flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-foreground">Monthly Leave Trend</h3>
                        <p class="mt-0.5 text-sm text-muted-foreground">Leave applications over the last 6 months.</p>
                    </div>
                    <div class="relative min-h-[280px] flex-1">
                        <Bar :key="`bar-${themeKey}`" :data="leaveMonthlyData" :options="leaveMonthlyOptions" />
                    </div>
                </div>

                <div class="ehris-card flex flex-col items-center">
                    <div class="mb-4 w-full">
                        <h3 class="text-lg font-bold text-foreground">Leave Status</h3>
                        <p class="mt-0.5 text-sm text-muted-foreground">Distribution of all leave requests.</p>
                    </div>
                    <div class="relative flex flex-1 items-center justify-center">
                        <div class="relative h-[230px] w-[230px]">
                            <Doughnut :key="`doughnut-${themeKey}`" :data="leaveStatusData" :options="leaveStatusOptions" />
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-3xl font-bold text-foreground">{{ leaveStatusTotal }}</span>
                                <span class="text-xs text-muted-foreground">Total</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 flex w-full justify-center gap-6">
                        <div v-for="(label, i) in leaveStatusData.labels" :key="label" class="text-center">
                            <p class="text-lg font-bold text-foreground">{{ leaveStatusData.datasets[0].data[i] }}</p>
                            <p class="text-xs text-muted-foreground">{{ label }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section>
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-foreground">Quick Actions</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Common tasks you can perform right away.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="action in quickActions"
                        :key="action.title"
                        :href="action.href"
                        class="ehris-card group flex items-start gap-4 transition-colors hover:border-primary/30"
                    >
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg" :class="action.color">
                            <component :is="action.icon" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-foreground group-hover:text-primary">{{ action.title }}</p>
                            <p class="mt-0.5 text-sm text-muted-foreground">{{ action.description }}</p>
                        </div>
                        <ArrowRight class="mt-0.5 size-4 shrink-0 text-muted-foreground opacity-0 transition group-hover:translate-x-0.5 group-hover:text-primary group-hover:opacity-100" />
                    </Link>
                </div>
            </section>

            <!-- Page Overview Cards -->
            <section>
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-foreground">Explore</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Navigate to the main sections of the system.</p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div
                        v-for="card in pageCards"
                        :key="card.title"
                        class="ehris-card flex flex-col"
                    >
                        <div class="flex items-start gap-4">
                            <div class="flex size-11 shrink-0 items-center justify-center rounded-xl" :class="card.color">
                                <component :is="card.icon" class="size-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-lg font-bold text-foreground">{{ card.title }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-muted-foreground">{{ card.description }}</p>
                            </div>
                        </div>

                        <div v-if="card.links.length" class="mt-4 flex flex-wrap gap-2">
                            <Link
                                v-for="link in card.links"
                                :key="link.label"
                                :href="link.href"
                                class="inline-flex items-center gap-1 rounded-md border border-border/80 bg-muted/50 px-2.5 py-1 text-xs font-medium text-muted-foreground transition hover:border-primary/30 hover:bg-primary/5 hover:text-primary"
                            >
                                <LayoutGrid class="size-3" />
                                {{ link.label }}
                            </Link>
                        </div>

                        <div class="mt-auto pt-4">
                            <Link
                                :href="card.href"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-primary transition hover:underline"
                            >
                                View {{ card.title }}
                                <ArrowRight class="size-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
