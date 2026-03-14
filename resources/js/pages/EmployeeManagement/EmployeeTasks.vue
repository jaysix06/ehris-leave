<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    CalendarDays,
    Clock3,
    ShieldAlert,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import employeeManagementRoutes from '@/routes/employee-management';
import type { BreadcrumbItem } from '@/types';

type EmployeeTask = {
    id: number;
    title: string;
    description: string;
    priority: string;
    status: string;
    due_date: string | null;
    due_date_end: string | null;
    accomplishment_report: string | null;
};

type EmployeeCard = {
    user_id: number;
    hrid: number;
    name: string;
    role: string;
    job_title: string;
    avatar: string | null;
    clock_in: string | null;
    clock_out: string | null;
    tasks: EmployeeTask[];
};

const pageTitle = 'Employee Management - Employee Tasks';

const props = withDefaults(
    defineProps<{
        accessDenied?: boolean;
        deniedMessage?: string | null;
        selectedDate: string;
        employees: EmployeeCard[];
    }>(),
    {
        accessDenied: false,
        deniedMessage: null,
    },
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
    },
];

const filterDate = ref(props.selectedDate);

watch(
    () => props.selectedDate,
    (value) => {
        filterDate.value = value;
    },
);

const filteredDateLabel = computed(() => {
    if (!props.selectedDate) {
        return '';
    }

    const parsedDate = new Date(`${props.selectedDate}T00:00:00`);

    return Number.isNaN(parsedDate.getTime())
        ? props.selectedDate
        : new Intl.DateTimeFormat('en-US', {
              month: 'long',
              day: 'numeric',
              year: 'numeric',
          }).format(parsedDate);
});

const employeeSummaries = computed(() =>
    props.employees.map((employee) => ({
        ...employee,
        avatarSrc: resolveAvatarSrc(employee.avatar),
        initials:
            employee.name
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2)
                .map((part) => part.charAt(0).toUpperCase())
                .join('') || 'EM',
    })),
);

const totalTasks = computed(() =>
    props.employees.reduce(
        (count, employee) => count + employee.tasks.length,
        0,
    ),
);

function applyDateFilter(): void {
    router.get(
        employeeManagementRoutes.employeeTasks().url,
        {
            date: filterDate.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function resolveAvatarSrc(avatar: string | null): string | null {
    if (!avatar) {
        return null;
    }

    const cleaned = avatar.trim();
    if (!cleaned) {
        return null;
    }

    const normalizedName =
        cleaned.split('?')[0]?.split('#')[0]?.split('/').pop()?.toLowerCase() ??
        '';

    if (
        normalizedName === 'avatar-default.jpg' ||
        cleaned.toLowerCase().endsWith('/avatar-default.jpg')
    ) {
        return '/storage/avatars/avatar-default.jpg';
    }

    if (
        /^(https?:)?\/\//i.test(cleaned) ||
        cleaned.startsWith('data:') ||
        cleaned.startsWith('blob:')
    ) {
        return cleaned;
    }

    if (cleaned.startsWith('/')) {
        return cleaned;
    }

    if (cleaned.includes('/')) {
        return `/${cleaned}`;
    }

    return `/storage/avatars/${cleaned}`;
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8"
        >
            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-linear-to-br from-white via-slate-50 to-emerald-50 shadow-sm"
            >
                <div
                    class="flex flex-col gap-5 px-5 py-6 sm:px-7 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-3xl">
                        <p
                            class="text-xs font-semibold tracking-[0.28em] text-emerald-700 uppercase"
                        >
                            Employee Management
                        </p>
                        <h1
                            class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl"
                        >
                            Daily Employee Tasks
                        </h1>
                        <p
                            class="mt-2 max-w-2xl text-sm leading-6 text-slate-600"
                        >
                            Review each employee&apos;s task list and their
                            clock-in and clock-out record for
                            <span class="font-semibold text-slate-900">{{
                                filteredDateLabel
                            }}</span
                            >.
                        </p>
                    </div>

                    <form
                        class="flex w-full max-w-md flex-col gap-3 rounded-2xl border border-white/70 bg-white/90 p-4 shadow-sm backdrop-blur lg:w-auto"
                        @submit.prevent="applyDateFilter"
                    >
                        <label
                            for="employee-task-date"
                            class="text-sm font-medium text-slate-700"
                            >Filter by date</label
                        >
                        <div class="flex flex-col gap-3 sm:flex-row">
                            <Input
                                id="employee-task-date"
                                v-model="filterDate"
                                type="date"
                                class="border-slate-300 bg-white text-slate-900"
                            />
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
                            >
                                Apply
                            </button>
                        </div>
                    </form>
                </div>

                <div
                    class="grid gap-3 border-t border-slate-200/80 bg-white/70 px-5 py-4 sm:grid-cols-2 sm:px-7 xl:grid-cols-3"
                >
                    <div
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3"
                    >
                        <p
                            class="text-xs font-medium tracking-[0.2em] text-slate-500 uppercase"
                        >
                            Employees shown
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">
                            {{ props.employees.length }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3"
                    >
                        <p
                            class="text-xs font-medium tracking-[0.2em] text-slate-500 uppercase"
                        >
                            Tasks for the day
                        </p>
                        <p class="mt-2 text-2xl font-semibold text-slate-900">
                            {{ totalTasks }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-200 bg-white px-4 py-3"
                    >
                        <p
                            class="text-xs font-medium tracking-[0.2em] text-slate-500 uppercase"
                        >
                            Selected date
                        </p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">
                            {{ filteredDateLabel }}
                        </p>
                    </div>
                </div>
            </section>

            <section
                v-if="props.accessDenied"
                class="rounded-3xl border border-rose-200 bg-rose-50 px-6 py-8 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-rose-600 shadow-sm"
                >
                    <ShieldAlert class="h-7 w-7" />
                </div>
                <h2 class="mt-4 text-xl font-semibold text-rose-950">
                    Access denied
                </h2>
                <p
                    class="mx-auto mt-2 max-w-xl text-sm leading-6 text-rose-800"
                >
                    {{
                        props.deniedMessage ||
                        'Only HR and admin users can access this page.'
                    }}
                </p>
            </section>

            <section
                v-else-if="employeeSummaries.length === 0"
                class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500"
                >
                    <CalendarDays class="h-7 w-7" />
                </div>
                <h2 class="mt-4 text-xl font-semibold text-slate-900">
                    No employee records for this date
                </h2>
                <p
                    class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-600"
                >
                    No employee has a task scheduled or an attendance record for
                    {{ filteredDateLabel }}.
                </p>
            </section>

            <section v-else class="grid gap-5 lg:grid-cols-2 2xl:grid-cols-3">
                <article
                    v-for="employee in employeeSummaries"
                    :key="employee.user_id"
                    class="flex h-full flex-col rounded-3xl border border-slate-200 bg-white shadow-sm"
                >
                    <div class="border-b border-slate-100 px-5 py-5">
                        <div class="flex items-start gap-4">
                            <Avatar
                                class="h-16 w-16 rounded-2xl border border-slate-200"
                            >
                                <AvatarImage
                                    v-if="employee.avatarSrc"
                                    :src="employee.avatarSrc"
                                    :alt="employee.name"
                                    class="object-cover"
                                />
                                <AvatarFallback
                                    class="rounded-2xl bg-slate-100 text-base font-semibold text-slate-700"
                                >
                                    {{ employee.initials }}
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <h2
                                    class="truncate text-lg font-semibold text-slate-900"
                                >
                                    {{ employee.name }}
                                </h2>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span
                                        class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700"
                                    >
                                        {{ employee.role }}
                                    </span>
                                    <span
                                        class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600"
                                    >
                                        HRID {{ employee.hrid }}
                                    </span>
                                </div>
                                <p
                                    class="mt-3 text-sm font-medium text-slate-700"
                                >
                                    {{ employee.job_title }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                <div
                                    class="flex items-center gap-2 text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                >
                                    <Clock3 class="h-3.5 w-3.5" />
                                    Clock in
                                </div>
                                <p
                                    class="mt-2 text-base font-semibold text-slate-900"
                                >
                                    {{ employee.clock_in || 'No record' }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                <div
                                    class="flex items-center gap-2 text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                >
                                    <Clock3 class="h-3.5 w-3.5" />
                                    Clock out
                                </div>
                                <p
                                    class="mt-2 text-base font-semibold text-slate-900"
                                >
                                    {{ employee.clock_out || 'No record' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col px-5 py-5">
                        <div class="flex items-center justify-between">
                            <div
                                class="flex items-center gap-2 text-sm font-semibold text-slate-900"
                            >
                                <BriefcaseBusiness
                                    class="h-4 w-4 text-emerald-700"
                                />
                                Tasks
                            </div>
                            <span
                                class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600"
                            >
                                {{ employee.tasks.length }}
                                {{
                                    employee.tasks.length === 1
                                        ? 'task'
                                        : 'tasks'
                                }}
                            </span>
                        </div>

                        <div
                            v-if="employee.tasks.length === 0"
                            class="mt-4 flex flex-1 items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500"
                        >
                            No task scheduled for this employee on
                            {{ filteredDateLabel }}.
                        </div>

                        <div v-else class="mt-4 space-y-3">
                            <div
                                v-for="task in employee.tasks"
                                :key="task.id"
                                class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4"
                            >
                                <div
                                    class="flex flex-wrap items-start justify-between gap-3"
                                >
                                    <div>
                                        <h3
                                            class="text-sm font-semibold text-slate-900"
                                        >
                                            {{ task.title }}
                                        </h3>
                                        <p
                                            class="mt-1 text-sm leading-6 text-slate-600"
                                        >
                                            {{ task.description }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-600"
                                            >{{ task.status }}</span
                                        >
                                        <span
                                            class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-800"
                                            >{{ task.priority }}</span
                                        >
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500"
                                >
                                    <span
                                        >Due:
                                        {{
                                            task.due_date_end &&
                                            task.due_date_end !== task.due_date
                                                ? `${task.due_date} to ${task.due_date_end}`
                                                : task.due_date
                                        }}</span
                                    >
                                    <span v-if="task.accomplishment_report"
                                        >Accomplishment logged</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AppLayout>
</template>
