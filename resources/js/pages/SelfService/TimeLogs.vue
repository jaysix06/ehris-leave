<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';

type TimeLogEntry = {
    year: number;
    date_in: string;
    time_in: string;
    date_out: string;
    time_out: string;
    hours: string;
};

type WeekData = {
    entries: TimeLogEntry[];
    total: string;
};

type Props = {
    logsByYear: Record<number, Record<number, WeekData>>;
    years: number[];
};

const props = defineProps<Props>();

const pageTitle = 'My Time Logs';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Self-Service', href: selfServiceRoutes.timezone().url },
    { title: 'Timezone', href: selfServiceRoutes.timezone().url },
    { title: pageTitle },
];

const selectedYear = ref<number>(
    props.years.length > 0 ? props.years[0] : new Date().getFullYear(),
);

const weeksForYear = computed(() => {
    const yearData = props.logsByYear[selectedYear.value];
    if (!yearData) return [];
    return Object.keys(yearData)
        .map(Number)
        .sort((a, b) => b - a);
});

const getWeekData = (week: number) => props.logsByYear[selectedYear.value]?.[week] ?? null;

const expandedWeeks = ref<Set<string>>(new Set());

function toggleWeek(year: number, week: number) {
    const key = `${year}-${week}`;
    if (expandedWeeks.value.has(key)) {
        expandedWeeks.value = new Set([...expandedWeeks.value].filter((k) => k !== key));
    } else {
        expandedWeeks.value = new Set([...expandedWeeks.value, key]);
    }
}

function isExpanded(year: number, week: number) {
    return expandedWeeks.value.has(`${year}-${week}`);
}

watch(
    [selectedYear, weeksForYear],
    () => {
        if (weeksForYear.value.length > 0) {
            const first = weeksForYear.value[0];
            expandedWeeks.value = new Set([`${selectedYear.value}-${first}`]);
        } else {
            expandedWeeks.value = new Set();
        }
    },
    { immediate: true },
);
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page flex flex-col gap-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-2xl font-bold text-foreground">My Time Logs</h1>
                <Link
                    :href="selfServiceRoutes.timezone().url"
                    class="text-sm font-medium text-primary hover:underline"
                >
                    ← Back to Timezone
                </Link>
            </div>

            <div class="flex flex-col gap-4">
                <div class="flex items-center gap-2">
                    <label for="year-select" class="text-sm font-medium text-foreground">Year</label>
                    <select
                        id="year-select"
                        v-model.number="selectedYear"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <option
                            v-for="y in years"
                            :key="y"
                            :value="y"
                        >
                            {{ y }}
                        </option>
                    </select>
                </div>

                <template v-if="weeksForYear.length">
                    <div class="space-y-2">
                        <div
                            v-for="week in weeksForYear"
                            :key="`${selectedYear}-${week}`"
                            class="rounded-lg border border-border bg-card overflow-hidden"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center justify-between gap-2 px-4 py-3 text-left hover:bg-muted/50 transition"
                                @click="toggleWeek(selectedYear, week)"
                            >
                                <span class="font-medium text-foreground">
                                    Week: {{ week }} - Year: {{ selectedYear }}
                                </span>
                                <ChevronDown
                                    v-if="isExpanded(selectedYear, week)"
                                    class="size-5 shrink-0 text-muted-foreground"
                                />
                                <ChevronRight
                                    v-else
                                    class="size-5 shrink-0 text-muted-foreground"
                                />
                            </button>
                            <div
                                v-if="isExpanded(selectedYear, week)"
                                class="border-t border-border bg-muted/20"
                            >
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-border bg-muted/40">
                                                <th class="px-4 py-2 text-left font-semibold text-foreground">Year</th>
                                                <th class="px-4 py-2 text-left font-semibold text-foreground">Date In</th>
                                                <th class="px-4 py-2 text-left font-semibold text-foreground">Time In</th>
                                                <th class="px-4 py-2 text-left font-semibold text-foreground">Date Out</th>
                                                <th class="px-4 py-2 text-left font-semibold text-foreground">Time Out</th>
                                                <th class="px-4 py-2 text-left font-semibold text-foreground">Hours</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="(entry, idx) in getWeekData(week)?.entries ?? []"
                                                :key="idx"
                                                class="border-b border-border/60"
                                            >
                                                <td class="px-4 py-2 text-muted-foreground">{{ entry.year }}</td>
                                                <td class="px-4 py-2">{{ entry.date_in }}</td>
                                                <td class="px-4 py-2">{{ entry.time_in }}</td>
                                                <td class="px-4 py-2">{{ entry.date_out }}</td>
                                                <td class="px-4 py-2">{{ entry.time_out }}</td>
                                                <td class="px-4 py-2 font-medium">{{ entry.hours }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="px-4 py-3 border-t border-border bg-muted/30">
                                    <span class="text-sm font-semibold text-foreground">
                                        Total: {{ getWeekData(week)?.total ?? '00:00:00' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                <p v-else class="text-sm text-muted-foreground py-8 text-center">
                    No time logs for {{ selectedYear }}.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
