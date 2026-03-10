<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Calendar, DatePicker } from 'v-calendar';
import { Clock, FileText, Calendar as CalendarIcon, User, Mail, Briefcase, Hash } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem, User as UserType } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { toast } from 'vue3-toastify';

const pageTitle = 'Work From Home Accomplishment Monitor';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Self-Service',
        href: selfServiceRoutes.wfhTimeInOut().url,
    },
    {
        title: 'WFH TimeIn/Out',
    },
];

const page = usePage();
const authUser = computed(() => page.props.auth?.user as UserType | undefined);

// User information
const userInfo = computed(() => ({
    name: authUser.value?.name ?? authUser.value?.fullname ?? 'N/A',
    email: authUser.value?.email ?? 'N/A',
    role: authUser.value?.role ?? 'N/A',
    departmentId: authUser.value?.department_id ?? 'N/A',
    hrId: authUser.value?.hrId ?? authUser.value?.hrid ?? 'N/A',
    avatar: authUser.value?.avatar ?? null,
}));

// Plan/Accomplishment
const planText = ref('');
const isTimeInDisabled = computed(() => !planText.value.trim());

// Accomplishment Report - Date Range
type ReportDateRange = { start: Date | null; end: Date | null };
type CalendarRangeModel = { start: Date; end: Date };

const reportDateRange = ref<ReportDateRange>({
    start: null,
    end: null,
});

const calendarKey = ref(0);

const formatter = new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

const formatDate = (date: Date | null): string => {
    if (!date) {
        return '';
    }
    return formatter.format(date);
};

const normalizeDate = (date: Date): Date => {
    const normalized = new Date(date);
    normalized.setHours(0, 0, 0, 0);
    return normalized;
};

const calendarReportRange = computed<CalendarRangeModel | undefined>({
    get: () => {
        const { start, end } = reportDateRange.value;
        if (!start || !end) {
            return undefined;
        }
        return { start, end };
    },
    set: (value) => {
        const incomingStart = value?.start ? normalizeDate(value.start) : null;
        const incomingEnd = value?.end ? normalizeDate(value.end) : null;

        reportDateRange.value = {
            start: incomingStart,
            end: incomingEnd,
        };
    },
});

const isGenerateDisabled = computed(() => !reportDateRange.value.start || !reportDateRange.value.end);

const handleTimeIn = () => {
    if (!planText.value.trim()) {
        toast.error('Please enter your plan/accomplishment before time in.');
        return;
    }

    // TODO: Implement time in API call
    toast.success('Time In recorded successfully!');
    console.log('Time In:', {
        plan: planText.value,
        timestamp: new Date().toISOString(),
    });
};

const handleGenerateReport = () => {
    if (!reportDateRange.value.start || !reportDateRange.value.end) {
        toast.error('Please select both start and end dates.');
        return;
    }

    if (reportDateRange.value.start > reportDateRange.value.end) {
        toast.error('Start date must be before end date.');
        return;
    }

    // TODO: Implement report generation API call
    toast.success('Report generated successfully!');
    console.log('Generate Report:', {
        startDate: formatDate(reportDateRange.value.start),
        endDate: formatDate(reportDateRange.value.end),
    });
};

const getInitials = (name: string): string => {
    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 0) {
        return 'N/A';
    }
    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <!-- User Information Card -->
            <Card>
                <CardContent class="p-3">
                    <div class="flex items-center gap-3">
                        <Avatar class="h-12 w-12 border border-border flex-shrink-0">
                            <AvatarImage
                                v-if="userInfo.avatar"
                                :src="userInfo.avatar"
                                :alt="userInfo.name"
                            />
                            <AvatarFallback class="bg-primary text-primary-foreground text-sm">
                                {{ getInitials(userInfo.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-base font-semibold truncate">{{ userInfo.name }}</h2>
                            <div class="flex flex-wrap gap-x-3 gap-y-0.5 mt-0.5 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1">
                                    <Mail class="h-3 w-3" />
                                    {{ userInfo.email }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <Briefcase class="h-3 w-3" />
                                    {{ userInfo.role }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <Hash class="h-3 w-3" />
                                    Dept: {{ userInfo.departmentId }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <User class="h-3 w-3" />
                                    HR: {{ userInfo.hrId }}
                                </span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Main Content - Two Columns -->
            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Plan/Accomplishment Card -->
                <Card>
                    <CardContent class="p-4">
                        <div class="space-y-3">
                            <div>
                                <h3 class="text-base font-semibold mb-0.5 flex items-center gap-2">
                                    <FileText class="h-4 w-4 text-primary" />
                                    Plan/Accomplishment
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    Enter your daily plan or accomplishments before time in.
                                </p>
                            </div>
                            <div class="space-y-2 flex flex-col flex-1">
                                <Label for="plan-text" class="sr-only">
                                    Enter your plan or accomplishment for today
                                </Label>
                                <textarea
                                    id="plan-text"
                                    v-model="planText"
                                    placeholder="Enter text here..."
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 resize-none textarea-aligned"
                                />
                                <Button
                                    :disabled="isTimeInDisabled"
                                    class="w-full"
                                    size="sm"
                                    @click="handleTimeIn"
                                >
                                    <Clock class="mr-2 h-4 w-4" />
                                    Time In
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Accomplishment Report Card -->
                <Card>
                    <CardContent class="p-4">
                        <div class="space-y-3">
                            <div>
                                <h3 class="text-base font-semibold mb-0.5 flex items-center gap-2">
                                    <CalendarIcon class="h-4 w-4 text-primary" />
                                    Accomplishment Report
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    Select a date range to generate your report.
                                </p>
                            </div>
                            <div class="space-y-2 flex flex-col flex-1">
                                <!-- Date Range Display -->
                                <div v-if="reportDateRange.start || reportDateRange.end" class="rounded-md border p-2 bg-muted/50 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-muted-foreground">Start:</span>
                                        <span class="font-medium">{{ formatDate(reportDateRange.start) || 'Not selected' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between mt-0.5">
                                        <span class="text-muted-foreground">End:</span>
                                        <span class="font-medium">{{ formatDate(reportDateRange.end) || 'Not selected' }}</span>
                                    </div>
                                </div>

                                <!-- Inline Calendar -->
                                <div class="calendar-box calendar-aligned">
                                    <div class="calendar-picker-wrap">
                                        <DatePicker
                                            :key="calendarKey"
                                            v-model="calendarReportRange"
                                            is-range
                                            is-inline
                                            expanded
                                            :masks="{ weekdays: 'WWW' }"
                                            class="calendar-inline"
                                        />
                                    </div>
                                </div>

                                <Button
                                    :disabled="isGenerateDisabled"
                                    class="w-full"
                                    size="sm"
                                    @click="handleGenerateReport"
                                >
                                    <FileText class="mr-2 h-4 w-4" />
                                    Generate Report
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.textarea-aligned {
    min-height: 280px;
    flex: 1;
}

.calendar-box {
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    background: hsl(var(--card));
    padding: 0.4rem;
    min-width: 0;
}

.calendar-aligned {
    min-height: 280px;
    display: flex;
    flex-direction: column;
}

.calendar-picker-wrap {
    position: relative;
    width: 100%;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.calendar-inline :deep(.vc-container) {
    display: flex !important;
    width: 100%;
    max-width: none;
    border: 0;
    background: transparent;
}

.calendar-inline :deep(.vc-pane-layout) {
    width: 100% !important;
    grid-template-columns: minmax(0, 1fr) !important;
}

.calendar-inline :deep(.vc-pane) {
    width: 100% !important;
    min-width: 0 !important;
}

.calendar-inline :deep(.vc-title),
.calendar-inline :deep(.vc-weekday) {
    color: hsl(var(--muted-foreground));
    font-weight: 600;
}

.calendar-inline :deep(.vc-day-content:hover) {
    background: color-mix(in srgb, hsl(var(--primary)) 18%, white);
}

.calendar-inline :deep(.vc-highlight-bg-solid) {
    background-color: hsl(var(--primary));
}

@media (max-width: 1024px) {
    .calendar-box {
        width: 100%;
    }
}

@media (max-width: 760px) {
    .calendar-box {
        padding: 0.4rem;
    }

    .calendar-inline :deep(.vc-container) {
        font-size: 0.9rem;
    }
}
</style>
