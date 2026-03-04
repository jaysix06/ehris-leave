<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
import { activityLog, reportingManager } from '@/routes/utilities';
import type { BreadcrumbItem } from '@/types';
import type { Component } from 'vue';

const pageTitle = 'Dashboard';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
        href: dashboard().url,
    },
];

interface StatItem {
    title: string;
    value: string;
    icon: Component;
    color: string;
}

const stats: StatItem[] = [
    { title: 'Active Employees', value: '214', icon: Users, color: 'text-blue-600 bg-blue-500/10 dark:text-blue-400 dark:bg-blue-400/10' },
    { title: 'Pending Requests', value: '18', icon: ClipboardCheck, color: 'text-amber-600 bg-amber-500/10 dark:text-amber-400 dark:bg-amber-400/10' },
    { title: 'Upcoming Leaves', value: '7', icon: CalendarRange, color: 'text-emerald-600 bg-emerald-500/10 dark:text-emerald-400 dark:bg-emerald-400/10' },
    { title: 'Today Activity Logs', value: '63', icon: Activity, color: 'text-violet-600 bg-violet-500/10 dark:text-violet-400 dark:bg-violet-400/10' },
];

interface QuickAction {
    title: string;
    description: string;
    icon: Component;
    href: string;
    color: string;
}

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

interface PageCard {
    title: string;
    description: string;
    icon: Component;
    href: string;
    color: string;
    links: { label: string; href: string }[];
}

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
            { label: 'Reporting Manager', href: reportingManager().url },
            { label: 'Activity Log', href: activityLog().url },
        ],
    },
];
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
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
