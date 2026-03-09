<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Calendar, Clock, FolderOpen, ListPlus } from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue3-toastify';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';

type Props = {
    attendance?: {
        isClockedIn: boolean;
        hoursWorkedThisWeek: string;
    };
    successMessage?: string;
    errorMessage?: string;
};

const props = withDefaults(defineProps<Props>(), {
    attendance: () => ({ isClockedIn: false, hoursWorkedThisWeek: '00:00:00' }),
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
    if (isClockedIn.value) clockOut();
    else clockIn();
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page timezone-attendance-page">
            <!-- Date & time (text only, no card) -->
            <p class="text-right text-sm font-medium tabular-nums text-foreground sm:text-base">
                {{ dateTimeDisplay }}
            </p>

            <!-- Row 1: Summary / nav cards – uniform height, responsive grid -->
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <!-- 1. Calendar – teal -->
                <Link
                    :href="'/self-service/calendar'"
                    class="flex min-h-[160px] flex-col justify-between gap-3 rounded-2xl bg-teal-500 p-5 text-white shadow-sm transition hover:opacity-95"
                >
                    <Calendar class="size-10 shrink-0 opacity-90" />
                    <div class="flex-1">
                        <p class="text-sm font-medium opacity-90">Upcoming</p>
                        <p class="text-lg font-semibold">Events &amp; schedule</p>
                    </div>
                    <span class="text-xs font-medium opacity-90 underline decoration-white/70 underline-offset-2">
                        View calendar →
                    </span>
                </Link>

                <!-- 3. Hours worked – green (My Time / Weekly Time Logs) -->
                <Link
                    :href="'/self-service/time-logs'"
                    class="flex min-h-[160px] flex-col justify-between gap-3 rounded-2xl bg-green-500 p-5 text-white shadow-sm transition hover:opacity-95"
                >
                    <Clock class="size-10 shrink-0 opacity-90" />
                    <div class="flex-1">
                        <p class="text-sm font-medium opacity-90">You have worked</p>
                        <p class="text-2xl font-bold tabular-nums">{{ hoursWorkedThisWeek }}</p>
                        <p class="text-sm font-medium opacity-90">this week.</p>
                    </div>
                    <span class="text-xs font-medium opacity-90 underline decoration-white/70 underline-offset-2">
                        View time →
                    </span>
                </Link>

                <!-- 4. Clock in/out – action card, same min height -->
                <article class="ehris-card flex min-h-[160px] flex-col justify-between gap-4">
                    <div>
                        <p class="text-sm text-muted-foreground">You are currently</p>
                        <p class="mt-1 text-xl font-bold text-foreground">
                            {{ isClockedIn ? 'Clocked In' : 'Clocked Out' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        :disabled="clockLoading"
                        :class="[
                            'inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60 disabled:pointer-events-none',
                            isClockedIn
                                ? 'bg-amber-400 text-amber-950 hover:bg-amber-500 focus:ring-amber-500'
                                : 'bg-emerald-500 text-white hover:bg-emerald-600 focus:ring-emerald-500',
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
                </article>
            </section>

            <!-- Row 2: Recent Open Tasks – compact -->
            <section class="grid grid-cols-1 gap-4">
                <div class="ehris-card p-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Recent Open Tasks
                        </h3>
                        <Link
                            href="#"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                        >
                            <ListPlus class="size-4" />
                            Create Task
                        </Link>
                    </div>
                    <div class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-border/80 bg-muted/30 py-6">
                        <FolderOpen class="size-10 text-muted-foreground/60" />
                        <p class="text-sm text-muted-foreground">No Recent Open Tasks Found</p>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
