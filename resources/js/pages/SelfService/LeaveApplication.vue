<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { DatePicker } from 'v-calendar';
import {
    Bell,
    CalendarDays,
    CheckCircle2,
    SendHorizontal,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import { type BreadcrumbItem } from '@/types';
import { differenceInDays } from 'date-fns';

const pageTitle = 'Leave Application';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Self-Service',
        href: selfServiceRoutes.wfhTimeInOut().url,
    },
    {
        title: pageTitle,
        href: selfServiceRoutes.leaveApplication().url,
    },
];

const announcements = [
    'Please submit leave applications at least 5 working days in advance.',
    'Medical leave requests require supporting documents upon return.',
    'Team leads should endorse requests before final submission.',
    'Unused leave credits are evaluated according to yearly policy.',
    'Keep emergency contact details updated before extended leave.',
    'Use the comments field for handover notes and critical tasks.',
];

const today = new Date();

const leaveRange = ref<{ start: Date | null; end: Date | null }>({
    start: today,
    end: today,
});

const formatter = new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

const formatDate = (date: Date | null) =>
    date ? formatter.format(date) : '--/--/----';

const selectedRangeText = computed(
    () => `${formatDate(leaveRange.value.start)} to ${formatDate(leaveRange.value.end)}`,
);

const noOfDays = computed(() => {
    if (!leaveRange.value.start || !leaveRange.value.end) {
        return 0;
    }
    return differenceInDays(leaveRange.value.end, leaveRange.value.start) + 1;
});

const selectedLeaveType = ref<string>('- Select Leave Type -');

const leaveTypeOptions: { label: string; value: string }[] = [
    { label: '- Select Leave Type -', value: '- Select Leave Type -' },
    { label: 'Sick Leave', value: 'Sick Leave' },
    { label: 'Vacation Leave', value: 'Vacation Leave' },
    { label: 'Maternity Leave', value: 'Maternity Leave' },
    { label: 'CTO', value: 'CTO' },
    { label: 'Paternity Leave', value: 'Paternity Leave' },
    { label: 'Force Leave', value: 'Force Leave' },
    { label: 'Others', value: 'Others' },
];

const picked = ref<string>('');
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page leave-application-page">

            <section class="leave-summary-row">
                <article class="ehris-card leave-highlight-card">
                    <h3>Upcoming leaves</h3>
                    <p class="days-count">03 Days</p>
                    <p class="range-line">04/04/2023 - 06/04/2023</p>
                    <div class="leave-type">
                        <span>Paid Time Off (PTO)</span>
                        <CheckCircle2 :size="16" />
                    </div>
                </article>

                <article class="ehris-card mini-stat">
                    <p class="mini-num">27/30</p>
                    <p class="mini-label">Leaves remaining</p>
                </article>

                <article class="ehris-card mini-stat">
                    <p class="mini-num">03/30</p>
                    <p class="mini-label">Leaves used</p>
                </article>
            </section>

            <section class="leave-main-row">
                <article class="ehris-card request-card">
                    <div class="request-head">
                        <h3>Create Leave request</h3>
                        <p class="date-range">{{ selectedRangeText }}</p>
                    </div>

                    <div class="request-grid">
                        <div class="left-form">
                            <label>
                                Leave Type
                                <select v-model="selectedLeaveType" class="border-5 border-primary">
                                    <option v-for="option in leaveTypeOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </label>
                            <label>
                                Leave For
                                <select>
                                    <option>Full Day</option>
                                </select>
                            </label>

                            <div class="date-range-row">
                                <label>
                                    Start date
                                    <div class="date-readonly">
                                        {{ formatDate(leaveRange.start) }}
                                    </div>
                                </label>

                                <label>
                                    End date
                                    <div class="date-readonly">
                                        {{ formatDate(leaveRange.end) }}
                                    </div>
                                </label>

                                <label>
                                    No. of Days
                                    <div class="date-readonly">
                                        {{ noOfDays }}
                                    </div>
                                </label>
                            </div>

                            <label v-if="selectedLeaveType === 'Sick Leave'">
                                Reason for Sick Leave
                                <div class="flex flex-wrap gap-12 mt-2">
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="picked" name="choice" value="a" />
                                        In Hospital
                                    </label>
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="picked" name="choice" value="b" />
                                        Outpatient
                                    </label>
                                </div>
                                <textarea placeholder="Specify..."></textarea>
                            </label>
                            <label v-else-if="selectedLeaveType === 'Vacation Leave'">
                                Reason for Vacation Leave
                                <div class="flex flex-wrap gap-12 mt-2">
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="picked" name="choice" value="a" />
                                        Within the Philippines
                                    </label>
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="picked" name="choice" value="b" />
                                        Abroad
                                    </label>
                                </div>
                                <textarea placeholder="Specify..."></textarea>
                            </label>
                            <label>
                                Commutation
                                <div class="flex flex-wrap gap-12 mt-2">
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="picked" name="choice" value="a" />
                                        Requested
                                    </label>
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="picked" name="choice" value="b" />
                                        Not Requested
                                    </label>
                                </div>
                            </label>
                        </div>

                        <div class="calendar-box">
                            <DatePicker
                                v-model="leaveRange"
                                is-range
                                is-inline
                                expanded
                                :min-date="today"
                                :masks="{ weekdays: 'WWW' }"
                                class="calendar-inline"
                            />
                        </div>
                    </div>

                    <div class="request-actions">
                        <button class="draft-btn" type="button">Save as draft</button>
                        <button class="send-btn" type="button">
                            Send request
                            <SendHorizontal :size="14" />
                        </button>
                    </div>
                </article>

                <aside class="ehris-card announcement-card">
                    <h3>Details</h3>
                    <label>
                        Office/School Name
                        <select>
                            <option selected>- Select Office/School Name -</option>
                        </select>
                    </label>
                    <label>
                        Monthly Salary <span class="text-red-500 text-xs">(SG | Steps | Amount)</span>
                        <select>
                            <option selected>- Select Office/School Name -</option>
                        </select>
                    </label>
                    <label>
                        Position
                        <select>
                            <option selected>- Select Office/School Name -</option>
                        </select>
                    </label>
                </aside>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.leave-application-page {
    gap: 1rem;
}

.leave-top-row {
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: 1rem;
}

.leave-kicker {
    margin: 0;
    color: hsl(var(--primary));
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.leave-heading {
    margin: 0.2rem 0 0;
    color: hsl(var(--foreground));
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1.1;
}

.leave-user-actions {
    display: flex;
    gap: 0.6rem;
}

.icon-btn {
    display: inline-flex;
    width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    border-radius: 0.7rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--card));
    color: hsl(var(--primary));
}

.leave-summary-row {
    display: grid;
    grid-template-columns: 1.7fr 1fr 1fr;
    gap: 1rem;
}

.leave-highlight-card h3,
.request-head h3,
.announcement-card h3 {
    margin: 0;
    color: hsl(var(--foreground));
    font-size: 1.4rem;
    font-weight: 800;
}

.days-count {
    margin: 0.6rem 0 0;
    font-size: 1.25rem;
    color: hsl(var(--foreground));
    font-weight: 700;
}

.range-line {
    margin: 0.25rem 0 0.4rem;
    font-size: 0.95rem;
    color: hsl(var(--muted-foreground));
}

.leave-type {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.92rem;
    color: hsl(var(--foreground));
}

.date-range-row{
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 0.7rem;
}

.leave-type :deep(svg) {
    color: #2ea37f;
}

.mini-stat {
    display: grid;
    place-content: center;
    min-height: 120px;
    text-align: center;
}

.mini-num {
    margin: 0;
    color: hsl(var(--primary));
    font-size: 2rem;
    font-weight: 800;
}

.mini-label {
    margin: 0.45rem 0 0;
    color: hsl(var(--muted-foreground));
    font-size: 0.9rem;
}

.leave-main-row {
    display: grid;
    grid-template-columns: 2.35fr 1fr;
    gap: 1rem;
}

.request-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.8rem;
}

.date-range {
    margin: 0;
    color: hsl(var(--primary));
    font-weight: 600;
    font-size: 0.9rem;
}

.request-grid {
    display: grid;
    grid-template-columns: 1fr 1.35fr;
    gap: 0.8rem;
}

.left-form {
    display: grid;
    gap: 0.7rem;
}

.left-form label {
    display: grid;
    gap: 0.4rem;
    color: hsl(var(--muted-foreground));
    font-size: 0.84rem;
}

.left-form label.radio-option {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.left-form select,
.left-form textarea,
.date-readonly {
    border-radius: 0.7rem;
    border: 1px solid hsl(var(--input));
    background: hsl(var(--card));
    color: hsl(var(--foreground));
    font-size: 0.94rem;
    padding: 0.65rem 0.75rem;
}

.date-readonly {
    width: 100%;
    text-align: left;
    cursor: default;
    user-select: none;
}

.left-form textarea {
    min-height: 122px;
    resize: none;
}

.calendar-box {
    border: 1px solid hsl(var(--border));
    border-radius: 0.8rem;
    background: hsl(var(--card));
    padding: 0.7rem;
    min-width: 0;
    overflow: hidden;
}

.calendar-inline :deep(.vc-container) {
    display: flex !important;
    width: 100%;
    max-width: none;
    border: 0;
    background: transparent;
    color: hsl(var(--foreground));
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

.request-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.55rem;
    margin-top: 0.9rem;
}

.draft-btn,
.send-btn {
    border-radius: 0.6rem;
    border: 1px solid hsl(var(--primary));
    padding: 0.48rem 0.85rem;
    font-weight: 600;
    font-size: 0.84rem;
    cursor: pointer;
}

.draft-btn {
    background: hsl(var(--card));
    color: hsl(var(--primary));
}

.send-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.announcement-card ul {
    margin: 0.6rem 0 0;
    padding-left: 1rem;
    display: grid;
    gap: 0.72rem;
}

.announcement-card li p {
    margin: 0;
    color: hsl(var(--foreground));
    font-size: 0.8rem;
    line-height: 1.4;
}

.announcement-card li span {
    display: block;
    margin-top: 0.2rem;
    text-align: right;
    color: hsl(var(--muted-foreground));
    font-size: 0.68rem;
}

@media (max-width: 1160px) {
    .leave-summary-row,
    .leave-main-row,
    .request-grid {
        grid-template-columns: 1fr;
    }

    .calendar-box {
        width: 100%;
    }
}

@media (max-width: 760px) {
    .leave-top-row {
        align-items: center;
    }

    .leave-heading {
        font-size: 1.45rem;
    }

    .calendar-box {
        padding: 0.55rem;
    }

    .calendar-inline :deep(.vc-container) {
        font-size: 0.9rem;
    }
}
</style>
