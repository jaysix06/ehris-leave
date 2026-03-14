<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    BriefcaseBusiness,
    CalendarDays,
    ChevronDown,
    Clock3,
    Download,
    Eye,
    ShieldAlert,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { DatePicker } from 'v-calendar';
import { toast } from 'vue3-toastify';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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

const filterDate = ref<Date | null>(
    props.selectedDate ? new Date(`${props.selectedDate}T00:00:00`) : null,
);

const disabledDates = ref([
    {
        repeat: {
            weekdays: [1, 7], // Sunday (1) and Saturday (7)
        },
    },
]);

const filterDateInputLabel = computed(() => {
    if (!filterDate.value) {
        return 'Select a date';
    }
    return new Intl.DateTimeFormat('en-US', {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
    }).format(filterDate.value);
});

/**
 * Inject a <style> tag to hide Saturday/Sunday in the v-calendar popover.
 * The popover is teleported to <body>, so scoped CSS cannot reach it.
 * We use the vc-popover-content-wrapper class that v-calendar puts on all popovers.
 */
const POPOVER_STYLE_ID = 'employee-task-calendar-popover-style';

function ensurePopoverStyle(): void {
    if (document.getElementById(POPOVER_STYLE_ID)) {
        return;
    }
    const style = document.createElement('style');
    style.id = POPOVER_STYLE_ID;
    style.textContent = `
        .vc-popover-content-wrapper {
            z-index: 50 !important;
        }
        .vc-popover-content-wrapper .vc-weekdays,
        .vc-popover-content-wrapper .vc-week {
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
        }
        .vc-popover-content-wrapper .vc-weekdays .vc-weekday-1,
        .vc-popover-content-wrapper .vc-weekdays .vc-weekday-7,
        .vc-popover-content-wrapper .vc-week .weekday-1,
        .vc-popover-content-wrapper .vc-week .weekday-7,
        .vc-popover-content-wrapper .vc-weekdays > *:nth-child(1),
        .vc-popover-content-wrapper .vc-weekdays > *:nth-child(7),
        .vc-popover-content-wrapper .vc-week > *:nth-child(1),
        .vc-popover-content-wrapper .vc-week > *:nth-child(7) {
            display: none !important;
        }
    `;
    document.head.appendChild(style);
}

function removePopoverStyle(): void {
    document.getElementById(POPOVER_STYLE_ID)?.remove();
}

onMounted(ensurePopoverStyle);
onUnmounted(removePopoverStyle);

watch(
    () => props.selectedDate,
    (value) => {
        if (value) {
            filterDate.value = new Date(`${value}T00:00:00`);
        } else {
            filterDate.value = null;
        }
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
    if (!filterDate.value) {
        return;
    }

    // Format date in local timezone to avoid timezone shift
    const year = filterDate.value.getFullYear();
    const month = String(filterDate.value.getMonth() + 1).padStart(2, '0');
    const day = String(filterDate.value.getDate()).padStart(2, '0');
    const dateString = `${year}-${month}-${day}`;

    router.get(
        employeeManagementRoutes.employeeTasks().url,
        {
            date: dateString,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

const exportPdfLoading = ref<Record<number, boolean>>({});

// View task modal
const viewTask = ref<EmployeeTask | null>(null);

function openViewModal(task: EmployeeTask): void {
    viewTask.value = task;
}

function closeViewModal(): void {
    viewTask.value = null;
}

function exportEmployeeTasks(employee: EmployeeCard): void {
    if (!props.selectedDate) {
        toast.error('Please select a date first.');
        return;
    }

    exportPdfLoading.value[employee.user_id] = true;

    const url = `/employee-management/employee-tasks/export/pdf?user_id=${encodeURIComponent(employee.user_id)}&date=${encodeURIComponent(props.selectedDate)}`;

    fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/pdf',
            'X-Requested-With': 'XMLHttpRequest',
        },
    })
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
            let filename = `employee_task_report_${employee.name}_${props.selectedDate}.pdf`;
            filename = filename.replace(/[^a-zA-Z0-9_-]/g, '_');
            if (result.disposition) {
                const m = result.disposition.match(/filename="?([^";\n]+)"?/);
                if (m) {
                    filename = m[1].trim();
                }
            }
            const u = URL.createObjectURL(result.blob);
            const a = document.createElement('a');
            a.href = u;
            a.download = filename;
            a.click();
            URL.revokeObjectURL(u);
            toast.success('Export downloaded.');
        })
        .catch((err: Error) => {
            toast.error(err?.message ?? 'Export failed.');
        })
        .finally(() => {
            exportPdfLoading.value[employee.user_id] = false;
        });
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
                class="overflow-visible rounded-3xl border border-slate-200 bg-linear-to-br from-white via-slate-50 to-emerald-50 shadow-sm"
            >
                <div
                    class="relative flex flex-col gap-5 px-5 py-6 sm:px-7 lg:flex-row lg:items-start lg:justify-between"
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
                        class="relative z-10 flex w-full max-w-lg flex-col gap-2 rounded-2xl border border-white/70 bg-white/90 p-3 shadow-sm backdrop-blur lg:w-auto"
                        @submit.prevent="applyDateFilter"
                    >
                        <label
                            for="employee-task-date"
                            class="text-xs font-medium text-slate-700"
                            >Filter by date</label
                        >
                        <div class="flex flex-col gap-2">
                            <DatePicker
                                id="employee-task-date"
                                v-model="filterDate"
                                :disabled-dates="disabledDates"
                                :masks="{ input: 'MM/DD/YYYY' }"
                                :popover="{ visibility: 'click' }"
                            >
                                <template #default="{ togglePopover }">
                                    <button
                                        type="button"
                                        class="inline-flex w-full items-center justify-between rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 shadow-sm transition hover:bg-slate-50"
                                        @click="togglePopover"
                                    >
                                        <span>{{ filterDateInputLabel }}</span>
                                        <ChevronDown class="h-4 w-4 text-slate-400" />
                                    </button>
                                </template>
                            </DatePicker>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-1.5 text-sm font-medium text-white transition hover:bg-slate-700"
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
                            <div class="flex items-center gap-2">
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
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2 disabled:opacity-60 disabled:pointer-events-none"
                                    :disabled="exportPdfLoading[employee.user_id] || employee.tasks.length === 0"
                                    @click="exportEmployeeTasks(employee)"
                                >
                                    <Download class="h-3.5 w-3.5" />
                                    {{ exportPdfLoading[employee.user_id] ? 'Exporting…' : 'Export' }}
                                </button>
                            </div>
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

                                    <div class="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
                                            @click="openViewModal(task)"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            View
                                        </button>
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

        <!-- View Task Modal -->
        <Teleport to="body">
            <div
                v-if="viewTask"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="closeViewModal"
            >
                <div
                    class="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 shadow-lg"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="view-task-title"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h2 id="view-task-title" class="text-lg font-semibold text-slate-900">
                            Task details
                        </h2>
                        <button
                            type="button"
                            class="rounded p-1 text-slate-500 hover:bg-slate-100"
                            aria-label="Close"
                            @click="closeViewModal"
                        >
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-slate-500">Title</dt>
                            <dd class="mt-0.5 font-medium text-slate-900">{{ viewTask.title }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Description</dt>
                            <dd class="mt-0.5 text-slate-900">{{ viewTask.description || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Priority</dt>
                            <dd class="mt-0.5 text-slate-900">{{ viewTask.priority }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Due date</dt>
                            <dd class="mt-0.5 text-slate-900">
                                {{
                                    viewTask.due_date_end &&
                                    viewTask.due_date_end !== viewTask.due_date
                                        ? `${viewTask.due_date} – ${viewTask.due_date_end}`
                                        : viewTask.due_date
                                }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-medium text-slate-500">Status</dt>
                            <dd class="mt-0.5 text-slate-900">{{ viewTask.status }}</dd>
                        </div>
                        <div v-if="viewTask.accomplishment_report">
                            <dt class="font-medium text-slate-500">Accomplishment Report</dt>
                            <dd class="mt-0.5 whitespace-pre-wrap text-slate-900">{{ viewTask.accomplishment_report }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

