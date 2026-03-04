<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { echo } from '@laravel/echo-vue';
import { addDays, differenceInDays } from 'date-fns';
import {
    CheckCircle2,
    ImagePlus,
    SendHorizontal,
    X,
} from 'lucide-vue-next';
import { Calendar, DatePicker } from 'v-calendar';
import { toast } from 'vue3-toastify';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import { type BreadcrumbItem, type User } from '@/types';

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

const today = new Date();
today.setHours(0, 0, 0, 0);

const leaveRange = ref<{ start: Date | null; end: Date | null }>({
    start: null,
    end: null,
});
type LeaveRange = { start: Date | null; end: Date | null };
type CalendarRangeModel = { start: Date; end: Date };
type LeaveForMode = 'Specific dates' | 'Within selected date range' | '- Select Leave For -';
type CalendarDayClick = { date: Date; isDisabled?: boolean };

const formatter = new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
});

const formatDate = (date: Date | null) =>
    date ? formatter.format(date) : '--/--/----';

const leaveForMode = ref<LeaveForMode>('- Select Leave For -');
const specificLeaveDates = ref<Date[]>([]);
const isSpecificDaysMode = computed(() => leaveForMode.value === 'Specific dates');
const isLeaveForUnselected = computed(() => leaveForMode.value === '- Select Leave For -');

const dateKey = (date: Date) => {
    const normalized = normalizeDate(date);
    const year = normalized.getFullYear();
    const month = `${normalized.getMonth() + 1}`.padStart(2, '0');
    const day = `${normalized.getDate()}`.padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const sortedSpecificLeaveDates = computed(() =>
    [...specificLeaveDates.value].sort((a, b) => a.getTime() - b.getTime()),
);
const specificDatesSignature = computed(() =>
    sortedSpecificLeaveDates.value.map((date) => dateKey(date)).join('|'),
);

const selectedSpecificDatesText = computed(() => {
    const dates = sortedSpecificLeaveDates.value.map((date) => formatDate(date));
    return dates.length ? dates.join(', ') : '--/--/----';
});

const effectiveLeaveStartDate = computed(() => {
    if (!isSpecificDaysMode.value) {
        return leaveRange.value.start;
    }
    return sortedSpecificLeaveDates.value[0] ?? null;
});

const effectiveLeaveEndDate = computed(() => {
    if (!isSpecificDaysMode.value) {
        return leaveRange.value.end;
    }
    return sortedSpecificLeaveDates.value[sortedSpecificLeaveDates.value.length - 1] ?? null;
});

const noOfDays = computed(() => {
    if (isSpecificDaysMode.value) {
        return sortedSpecificLeaveDates.value.length;
    }

    if (!leaveRange.value.start || !leaveRange.value.end) {
        return 0;
    }
    return differenceInDays(leaveRange.value.end, leaveRange.value.start) + 1;
});

const defaultLeaveTypeLabel = '- Select Leave Type -';
const selectedLeaveType = ref<string>(defaultLeaveTypeLabel);
const isLeaveTypeUnselected = computed(() => selectedLeaveType.value === defaultLeaveTypeLabel);
const calendarKey = ref(0);
const isVacationLeave = computed(() => selectedLeaveType.value === 'Vacation Leave');
const isSpecialPrivilegeLeave = computed(() => selectedLeaveType.value === 'Special Privilege Leave');
const leaveTypeDayLimit = computed<number | null>(() => {
    const leaveType = selectedLeaveType.value;
    const limits: Record<string, number> = {
        'Paternity Leave': 7,
        'Maternity Leave': 105,
        'Special Privilege Leave': 3,
        'Solo Parent Leave': 7,
        'Study Leave': 180,
        'VAWC Leave': 10,
        '10-Day VAWC Leave': 10,
        'Rehabilitation Leave': 180,
        'Rehabilitation Privilege': 180,
        'Special Leave Benefits for Women': 60,
        'Special Emergency (Calamity) Leave': 5,
        'Mandatory/Force Leave': 5,
        'Mandatory Leave': 5,
        'Forced Leave': 5,
    };

    if (limits[leaveType]) {
        return limits[leaveType];
    }

    return null;
});


const minSelectableDate = computed<Date | null>(() => {
    const leaveType = selectedLeaveType.value;
    if (['Sick Leave', 'VAWC Leave', '10-Day VAWC Leave', 'Special Leave Benefits for Women'].includes(leaveType)) {
        return null;
    }
    if (leaveType === 'Vacation Leave' || leaveType === 'Solo Parent Leave') {
        return addDays(today, 5);
    }
    if (leaveType === 'Special Privilege Leave') {
        return addDays(today, 7);
    }
    return today;
});

const normalizeDate = (date: Date) => {
    const normalized = new Date(date);
    normalized.setHours(0, 0, 0, 0);
    return normalized;
};

const clampLeaveRange = (value: LeaveRange): LeaveRange => {
    let start = value.start ? normalizeDate(value.start) : null;
    let end = value.end ? normalizeDate(value.end) : null;

    if (!start || !end) {
        return { start, end };
    }

    const minimumDate = minSelectableDate.value ? normalizeDate(minSelectableDate.value) : null;
    if (minimumDate && start < minimumDate) {
        start = minimumDate;
        end = minimumDate;
    }

    const limit = leaveTypeDayLimit.value;
    if (limit) {
        const maxAllowedEnd = addDays(start, limit - 1);
        if (end > maxAllowedEnd) {
            end = maxAllowedEnd;
        }
    }

    return { start, end };
};

const clampSpecificLeaveDates = (value: Date[]) => {
    const minimumDate = minSelectableDate.value ? normalizeDate(minSelectableDate.value) : null;
    const uniqueSorted = [...value]
        .map((date) => normalizeDate(date))
        .filter((date) => !minimumDate || date >= minimumDate)
        .filter((date, index, arr) => index === arr.findIndex((item) => dateKey(item) === dateKey(date)))
        .sort((a, b) => a.getTime() - b.getTime());

    const limit = leaveTypeDayLimit.value;
    if (limit && uniqueSorted.length > limit) {
        return uniqueSorted.slice(0, limit);
    }

    return uniqueSorted;
};

const disabledDates = ref([
  {
    repeat: {
      weekdays: [1, 7],
    },
  },
]);

const calendarLeaveRange = computed<CalendarRangeModel | undefined>({
    get: () => {
        const { start, end } = leaveRange.value;
        if (!start || !end) {
            return undefined;
        }
        return { start, end };
    },
    set: (value) => {
        const incomingStart = value?.start ? normalizeDate(value.start) : null;
        const incomingEnd = value?.end ? normalizeDate(value.end) : null;
        const clamped = clampLeaveRange({
            start: incomingStart,
            end: incomingEnd,
        });

        leaveRange.value = clamped;

        const wasClamped =
            !!incomingStart &&
            !!incomingEnd &&
            (!!clamped.start && incomingStart.getTime() !== clamped.start.getTime() ||
                !!clamped.end && incomingEnd.getTime() !== clamped.end.getTime());

        if (wasClamped) {
            calendarKey.value += 1;
        }
    },
});

const specificDaysAttributes = computed(() => [
    {
        key: `specific-leave-days-${specificDatesSignature.value}`,
        highlight: true,
        dates: sortedSpecificLeaveDates.value.map((date) => new Date(date)),
    },
]);

const onSpecificDayClick = (day: CalendarDayClick) => {
    if (isLeaveTypeUnselected.value || day.isDisabled) {
        return;
    }

    const pickedDate = normalizeDate(day.date);
    const pickedKey = dateKey(pickedDate);
    const existing = specificLeaveDates.value;
    const hasDate = existing.some((date) => dateKey(date) === pickedKey);

    const nextDates = hasDate
        ? existing.filter((date) => dateKey(date) !== pickedKey)
        : [...existing, pickedDate];

    specificLeaveDates.value = clampSpecificLeaveDates(nextDates);
};

const reason = ref<string>('');
const reasonSpecify = ref<string>('');
const commutation = ref<string>('');
const supportingDocuments = ref<File[]>([]);
const supportingDocumentsInput = ref<HTMLInputElement | null>(null);
const destinationScope = ref<'within_ph' | 'abroad' | ''>('');
const supervisorNotes = ref('');

const showToast = (message: string, type: 'error' | 'success' = 'error') => {
    if (type === 'success') {
        toast.success(message);
        return;
    }

    toast.error(message);
};

const clearLeaveRequestForm = () => {
    selectedLeaveType.value = defaultLeaveTypeLabel;
    leaveForMode.value = '- Select Leave For -';
    leaveRange.value = { start: null, end: null };
    specificLeaveDates.value = [];
    calendarKey.value += 1;

    reason.value = '';
    reasonSpecify.value = '';
    commutation.value = '';
    supportingDocuments.value = [];
    if (supportingDocumentsInput.value) supportingDocumentsInput.value.value = '';

    destinationScope.value = '';
    supervisorNotes.value = '';
};

const page = usePage();
const authUser = computed(() => page.props.auth?.user as User | undefined);
const leaveEmployee = computed(() => page.props.leaveEmployee as Record<string, unknown> | undefined);
const dbLeaveTypes = computed(() => page.props.leaveTypes as string[] | undefined);
const mandatoryLeaveSummary = computed(
    () =>
        page.props.mandatoryLeaveSummary as
            | { year: number; usedDays: number; remainingDays: number; forfeitedDays: number }
            | undefined,
);
const salaryFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
});

const leaveTypeOptions = computed<{ label: string; value: string }[]>(() => {
    const baseOption = { label: defaultLeaveTypeLabel, value: defaultLeaveTypeLabel };
    const types = (dbLeaveTypes.value ?? [])
        .map((type) => type.trim())
        .filter((type) => type !== '');

    return [
        baseOption,
        ...types.map((type) => ({
            label: type,
            value: type,
        })),
    ];
});

const readRecordString = (record: Record<string, unknown> | undefined, keys: string[]) => {
    if (!record) return null;

    for (const key of keys) {
        const value = record[key];
        if (typeof value === 'string' && value.trim()) {
            return value.trim();
        }
        if (typeof value === 'number' && Number.isFinite(value)) {
            return String(value);
        }
    }

    return null;
};

const toNumber = (value: unknown): number | null => {
    if (typeof value === 'number' && Number.isFinite(value)) {
        return value;
    }

    if (typeof value === 'string') {
        const normalized = value.replace(/,/g, '').trim();
        if (!normalized) return null;
        const parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : null;
    }

    return null;
};

const pickUserValue = (keys: string[], fallback = 'Not available') => {
    const user = authUser.value as Record<string, unknown> | undefined;
    if (!user) return fallback;

    for (const key of keys) {
        const value = user[key];
        if (typeof value === 'string' && value.trim()) {
            return value.trim();
        }
    }
    return fallback;
};

const formattedSalary = computed(() => {
    const source = leaveEmployee.value;
    if (!source) return null;

    const grade = readRecordString(source, ['salaryGrade', 'salary_grade', 'salary']);
    const step = readRecordString(source, ['salaryStep', 'salary_step', 'step']);
    const amountValue = toNumber(source.salaryAmount ?? source.salary_actual ?? source.salary_authorized);

    const parts: string[] = [];
    if (grade) parts.push(`SG ${grade}`);
    if (step) parts.push(`Step ${step}`);
    if (amountValue !== null) parts.push(salaryFormatter.format(amountValue));

    return parts.length > 0 ? parts.join(' | ') : null;
});

const employeeDetails = computed(() => ({
    name:
        readRecordString(leaveEmployee.value, ['name', 'fullname']) ??
        pickUserValue(['name', 'firstname', 'lastname', 'middlename', 'extension']),
    reportingManager:
        readRecordString(leaveEmployee.value, ['reportingManager', 'reporting_manager']) ??
        pickUserValue(['reporting_manager', 'reportingManager']),
    position:
        readRecordString(leaveEmployee.value, ['position', 'job_title', 'designation']) ??
        pickUserValue(['position', 'job_title', 'designation']),
    officeSchool:
        readRecordString(leaveEmployee.value, ['officeSchool', 'office', 'department']) ??
        pickUserValue(['office']),
    salary:
        formattedSalary.value ??
        pickUserValue(['salary', 'monthly_salary', 'salary_grade']),
}));

const onSupportingDocumentsChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    supportingDocuments.value = target.files ? Array.from(target.files) : [];
};

const openSupportingDocumentsPicker = () => {
    supportingDocumentsInput.value?.click();
};

const clearSupportingDocuments = () => {
    supportingDocuments.value = [];
    if (supportingDocumentsInput.value) {
        supportingDocumentsInput.value.value = '';
    }
};

watch(selectedLeaveType, (newType, oldType) => {
    if (newType !== oldType || newType === defaultLeaveTypeLabel) {
        leaveRange.value = {
            start: null,
            end: null,
        };
        specificLeaveDates.value = [];
        calendarKey.value += 1;
    }
});

watch(leaveForMode, (newMode, oldMode) => {
    if (newMode === oldMode) return;

    if (newMode === 'Within selected date range') {
        specificLeaveDates.value = [];
    } else if (newMode === 'Specific dates') {
        leaveRange.value = {
            start: null,
            end: null,
        };
    }

    calendarKey.value += 1;
});

watch([minSelectableDate, leaveTypeDayLimit], () => {
    if (isSpecificDaysMode.value) {
        specificLeaveDates.value = clampSpecificLeaveDates(specificLeaveDates.value);
    }
});

watch(leaveTypeOptions, (options) => {
    const isValid = options.some((option) => option.value === selectedLeaveType.value);
    if (!isValid) {
        selectedLeaveType.value = defaultLeaveTypeLabel;
    }
});

watch(selectedLeaveType, () => {
    if (!(isVacationLeave.value || isSpecialPrivilegeLeave.value)) {
        destinationScope.value = '';
    }
});

watch(
    () => (page.props.errors as Record<string, string>) ?? {},
    (errors) => {
        const firstError = Object.values(errors)[0];
        if (firstError) {
            showToast(firstError, 'error');
        }
    },
);

const formatDateForSubmit = (date: Date) => {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const submitLeaveApplication = () => {
    const leaveStartDate = effectiveLeaveStartDate.value;
    const leaveEndDate = effectiveLeaveEndDate.value;

    if (selectedLeaveType.value === defaultLeaveTypeLabel) {
        showToast('Please select a leave type.');
        return;
    }
    if (isLeaveForUnselected.value) {
        showToast('Please select Leave For.');
        return;
    }
    if (!leaveStartDate || !leaveEndDate) {
        showToast(
            isSpecificDaysMode.value
            ? 'Please select at least one leave date.'
            : 'Please select both start and end dates.',
        );
        return;
    }

    if (leaveTypeDayLimit.value !== null && noOfDays.value > leaveTypeDayLimit.value) {
        showToast(`${selectedLeaveType.value} cannot exceed ${leaveTypeDayLimit.value} days.`);
        return;
    }

    if ((isVacationLeave.value || isSpecialPrivilegeLeave.value) && !destinationScope.value) {
        showToast('Please indicate if your destination is within the Philippines or abroad.');
        return;
    }

    router.post(
        selfServiceRoutes.leaveApplication().url,
        {
            leave_type: selectedLeaveType.value,
            leave_start_date: formatDateForSubmit(leaveStartDate),
            leave_end_date: formatDateForSubmit(leaveEndDate),
            reason: reason.value || null,
            reason_specify: reasonSpecify.value || null,
            commutation: commutation.value || null,
            destination_scope: destinationScope.value || null,
            supervisor_notes: supervisorNotes.value || null,
            supporting_documents: supportingDocuments.value,
            leave_for_mode: leaveForMode.value,
            leave_specific_dates: isSpecificDaysMode.value
                ? sortedSpecificLeaveDates.value.map((date) => formatDateForSubmit(date)).join(',')
                : null,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                showToast('Leave application submitted successfully.', 'success');
            },
        },
    );
};

const refreshLeaveTypes = () => {
    console.info('[LeaveApplication] LeaveTypeUpdated received. Refreshing leave types.');
    router.reload({ only: ['leaveTypes'] });
};

const refreshLeaveSummaryOnRealtimeUpdate = (payload: any) => {
    const authHrid = Number((authUser.value as Record<string, unknown> | undefined)?.hrId ?? 0);
    const employeeHrid = Number(payload?.employeeHrid ?? 0);
    if (authHrid > 0 && employeeHrid === authHrid) {
        router.reload({ only: ['mandatoryLeaveSummary'] });
    }
};

const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED !== 'false';

onMounted(() => {
    if (reverbEnabled) {
        try {
            echo().channel('leave-types').listen('.LeaveTypeUpdated', refreshLeaveTypes);
            echo().channel('leave-requests').listen('.LeaveRequestUpdated', refreshLeaveSummaryOnRealtimeUpdate);
        } catch {
            // Reverb not connected; real-time updates disabled
        }
    }
});

onBeforeUnmount(() => {
    if (reverbEnabled) {
        try {
            echo().channel('leave-types').stopListening('LeaveTypeUpdated');
            echo().channel('leave-requests').stopListening('LeaveRequestUpdated');
        } catch {
            // ignore
        }
    }
});
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
                    <p class="mini-num">
                        {{ mandatoryLeaveSummary ? `${mandatoryLeaveSummary.remainingDays}/5` : '--/5' }}
                    </p>
                    <p class="mini-label">Mandatory VL remaining</p>
                </article>

                <article class="ehris-card mini-stat">
                    <p class="mini-num">
                        {{ mandatoryLeaveSummary ? `${mandatoryLeaveSummary.usedDays}/5` : '--/5' }}
                    </p>
                    <p class="mini-label">Mandatory VL used</p>
                </article>
            </section>

            <section class="leave-main-row">
                <article class="ehris-card request-card">
                    <div class="request-head">
                        <h3>Create Leave request</h3>
                        <p class="date-range">{{ today.toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' }) }}</p>
                    </div>

                    <div class="request-grid">
                        <div class="left-form">
                            <label>
                                Leave Type
                                <select v-model="selectedLeaveType" class="border-5 border-primary">
                                    <option
                                        v-for="option in leaveTypeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                        :disabled="option.value === defaultLeaveTypeLabel"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </label>
                            <label>
                                Leave For
                                <select v-model="leaveForMode">
                                    <option value="- Select Leave For -" disabled selected>- Select Leave For -</option>
                                    <option value="Specific dates">Specific dates</option>
                                    <option value="Within selected date range">Within selected date range</option>
                                </select>
                            </label>

                            <div v-if="!isSpecificDaysMode" class="date-range-row">
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

                            <div v-else class="date-range-row-specific">
                                <label>
                                    <div class="specific-dates-label">
                                        <span>Specific dates</span>
                                    </div>
                                    <div class="date-readonly">
                                        {{ selectedSpecificDatesText }}
                                    </div>
                                </label>

                                <label>
                                    No. of Days
                                    <div class="date-readonly">
                                        {{ noOfDays }}
                                    </div>
                                </label>
                            </div>

                            <label v-if="selectedLeaveType !== '- Select Leave Type -'">
                                Reason for {{ selectedLeaveType }}
                                <div class="flex flex-wrap mt-2" :class="selectedLeaveType === 'Study Leave' || selectedLeaveType === 'Others' ? 'gap-3' : 'gap-12'">
                                    <!-- Sick Leave -->
                                    <label  v-if="selectedLeaveType === 'Sick Leave'" class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="reason" name="choice" value="a" />
                                        In Hospital
                                    </label>
                                    <label  v-if="selectedLeaveType === 'Sick Leave'" class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="reason" name="choice" value="b" />
                                        Outpatient
                                    </label>

                                    <!-- Vacation Leave or Special Privilege Leave -->
                                    <label v-if="selectedLeaveType === 'Vacation Leave' || selectedLeaveType === 'Special Privilege Leave'" class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="destinationScope" name="destination_scope" value="within_ph" />
                                        Within the Philippines
                                    </label>
                                    <label v-if="selectedLeaveType === 'Vacation Leave' || selectedLeaveType === 'Special Privilege Leave'" class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="destinationScope" name="destination_scope" value="abroad" />
                                        Abroad
                                    </label>

                                    <label v-if="selectedLeaveType === 'Study Leave'" class="radio-option inline-flex  gap-1 cursor-pointer">
                                        <input type="radio" v-model="reason" name="masters" value="Completion of Master's Degree" />
                                        Completion of Master's Degree
                                    </label>
                                    <label v-if="selectedLeaveType === 'Study Leave'" class="radio-option inline-flex  gap-1 cursor-pointer">
                                        <input type="radio" v-model="reason" name="review" value="BAR/Board Examination Review" />
                                        BAR/Board Examination Review
                                    </label>

                                    <label v-if="selectedLeaveType === 'Others'" class="radio-option inline-flex  gap-1 cursor-pointer">
                                        <input type="radio" v-model="reason" name="monetization" value="Monetization of Leave Credits" />
                                        Monetization of Leave Credits
                                    </label>
                                    <label v-if="selectedLeaveType === 'Others'" class="radio-option inline-flex  gap-1 cursor-pointer">
                                        <input type="radio" v-model="reason" name="terminal" value="Terminal Leave" />
                                        Terminal Leave
                                    </label>
                                </div>
                                <textarea v-model="reasonSpecify" placeholder="Specify..." />
                            </label>
                            <label>
                                Commutation
                                <div class="flex flex-wrap gap-12 mt-2">
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="commutation" name="requested" value="Requested" />
                                        Requested
                                    </label>
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="commutation" name="not_requested" value="Not Requested" />
                                        Not Requested
                                    </label>
                                </div>
                            </label>

                            <label>
                                Supervisor/Reviewer Notes (Optional)
                                <textarea v-model="supervisorNotes" placeholder="Optional notes for approving officers..." />
                            </label>
                        </div>

                        <div class="calendar-box">
                            <div class="calendar-picker-wrap" :class="{ 'is-disabled': isLeaveTypeUnselected || isLeaveForUnselected }">
                                <DatePicker
                                    v-if="!isSpecificDaysMode"
                                    :key="calendarKey"
                                    v-model="calendarLeaveRange"
                                    is-range
                                    is-inline
                                    expanded
                                    :disabled-dates="disabledDates"
                                    :min-date="minSelectableDate ?? undefined"
                                    :masks="{ weekdays: 'WWW' }"
                                    class="calendar-inline"
                                />
                                <Calendar
                                    v-else
                                    :key="`specific-${calendarKey}`"
                                    expanded
                                    :attributes="specificDaysAttributes"
                                    :disabled-dates="disabledDates"
                                    :min-date="minSelectableDate ?? undefined"
                                    :masks="{ weekdays: 'WWW' }"
                                    class="calendar-inline"
                                    @dayclick="onSpecificDayClick"
                                />
                                <div v-if="isLeaveTypeUnselected || isLeaveForUnselected" class="calendar-disabled-overlay" />
                            </div>
                            


                            <div class="medical-cert-panel">
                                <p class="upload-title">Supporting Documents</p>
                                <p class="dropzone-sub">
                                    Upload required documentary support for the selected leave type.
                                </p>
                                <div v-if="supportingDocuments.length === 0" class="medical-dropzone" @click="openSupportingDocumentsPicker">
                                    <div class="dropzone-icon-wrap">
                                        <ImagePlus :size="30" />
                                    </div>
                                    <p class="dropzone-main">
                                        Drag &amp; drop
                                        <span>supporting documents</span>
                                    </p>
                                    <p class="dropzone-sub">
                                        or
                                        <button type="button" class="browse-link" @click.stop="openSupportingDocumentsPicker">
                                            browse files
                                        </button>
                                        on your computer
                                    </p>
                                    <input
                                        ref="supportingDocumentsInput"
                                        type="file"
                                        multiple
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="sr-only"
                                        @change="onSupportingDocumentsChange"
                                    />
                                </div>
                                <div v-else class="medical-file-row">
                                    <div class="file-meta">
                                        <p class="file-name">{{ supportingDocuments.length }} file(s) selected</p>
                                        <p class="file-info">
                                            {{ supportingDocuments.map((file) => file.name).join(', ') }}
                                        </p>
                                    </div>
                                    <button type="button" class="remove-file-btn" @click="clearSupportingDocuments">
                                        <X :size="16" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="request-actions">
                        <button class="clear-btn" type="button" @click="clearLeaveRequestForm">
                            Clear
                        </button>
                        <button class="apply-btn" type="button" @click="submitLeaveApplication">
                            Apply Leave
                            <SendHorizontal :size="14" />
                        </button>
                    </div>
                </article>

                <aside class="ehris-card employee-details-card">
                    <h3>Employee Details</h3>
                    <label>
                        Employee Name
                        <div class="info-readonly">{{ employeeDetails.name }}</div>
                    </label>
                    <label>
                        Office/School Name
                        <div class="info-readonly">{{ employeeDetails.officeSchool }}</div>
                    </label>
                    <label>
                        <div class="salary-label-row">
                            <span>Monthly Salary</span>
                            <span class="salary-note">(SG | Steps | Amount)</span>
                        </div>
                        <div class="info-readonly">{{ employeeDetails.salary }}</div>
                    </label>
                    <label>
                        Position
                        <div class="info-readonly">{{ employeeDetails.position }}</div>
                    </label>
                    <label>
                        Reporting Manager
                        <div class="info-readonly">{{ employeeDetails.reportingManager }}</div>
                    </label>
                </aside>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.leave-application-page {
    position: relative;
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
.employee-details-card h3 {
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

.date-range-row-specific {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 0.7rem;
}

.specific-dates-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
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
.left-form input,
.left-form textarea,
.date-readonly {
    border-radius: 0.7rem;
    border: 1.5px solid hsl(var(--muted-foreground) / 0.45);
    background: hsl(var(--card));
    color: hsl(var(--foreground));
    font-size: 0.94rem;
    padding: 0.65rem 0.75rem;
}

.medical-cert-panel {
    display: grid;
    gap: 0.55rem;
    margin-top: 0.7rem;
}

.medical-cert-panel .upload-title {
    margin: 0;
    color: hsl(var(--foreground));
    font-size: 0.84rem;
    font-weight: 600;
}

.medical-dropzone {
    border: 2px dashed hsl(var(--border));
    border-radius: 1rem;
    background: hsl(var(--muted) / 0.25);
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s ease, background-color 0.2s ease;
}

.medical-dropzone.is-active {
    border-color: hsl(var(--primary));
    background: hsl(var(--primary) / 0.08);
}

.dropzone-icon-wrap {
    margin: 0 auto 0.45rem;
    width: 42px;
    height: 42px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: hsl(var(--primary) / 0.14);
    color: hsl(var(--primary));
}

.dropzone-main {
    margin: 0;
    color: hsl(var(--foreground));
    font-size: 0.95rem;
    font-weight: 700;
}

.dropzone-main span {
    color: hsl(var(--primary));
}

.dropzone-sub {
    margin: 0.2rem 0 0;
    color: hsl(var(--muted-foreground));
    font-size: 0.78rem;
}

.browse-link {
    border: 0;
    background: transparent;
    color: hsl(var(--primary));
    padding: 0;
    text-decoration: underline;
    font-weight: 600;
    cursor: pointer;
}

.medical-file-row {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: 0.55rem;
    border: 1.5px solid hsl(var(--muted-foreground) / 0.45);
    background: hsl(var(--card));
    border-radius: 0.7rem;
    padding: 0.5rem 0.6rem;
}

.file-meta .file-name {
    margin: 0;
    color: hsl(var(--foreground));
    font-size: 0.8rem;
    line-height: 1.2;
    word-break: break-all;
}

.file-meta .file-info {
    margin: 0.1rem 0 0;
    color: hsl(var(--muted-foreground));
    font-size: 0.68rem;
}

.remove-file-btn {
    border: 0;
    background: transparent;
    color: hsl(var(--destructive));
    width: 24px;
    height: 24px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.upload-medical-btn {
    justify-self: center;
    border: 0;
    border-radius: 999px;
    padding: 0.45rem 1.45rem;
    min-width: 112px;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
}

.date-readonly {
    width: 100%;
    text-align: left;
    cursor: default;
    user-select: none;
    white-space: normal;
    word-break: break-word;
}

.left-form textarea {
    min-height: 122px;
    resize: none;
}

.calendar-box {
    border: 1.5px solid hsl(var(--muted-foreground) / 0.45);
    border-radius: 0.8rem;
    background: hsl(var(--card));
    padding: 0.7rem;
    min-width: 0;
    overflow: hidden;
}

.calendar-picker-wrap {
    position: relative;
}

.calendar-picker-wrap.is-disabled {
    opacity: 0.6;
}

.calendar-disabled-overlay {
    position: absolute;
    inset: 0;
    cursor: not-allowed;
    background: transparent;
    z-index: 10;
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

/* Render weekdays-only layout (Mon-Fri) by removing Sun/Sat columns */
.calendar-box :deep(.vc-weekdays),
.calendar-box :deep(.vc-week) {
    grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
}

.calendar-box :deep(.vc-weekdays .vc-weekday-1),
.calendar-box :deep(.vc-weekdays .vc-weekday-7),
.calendar-box :deep(.vc-week .weekday-1),
.calendar-box :deep(.vc-week .weekday-7),
.calendar-box :deep(.vc-weekdays > *:nth-child(1)),
.calendar-box :deep(.vc-weekdays > *:nth-child(7)),
.calendar-box :deep(.vc-week > *:nth-child(1)),
.calendar-box :deep(.vc-week > *:nth-child(7)) {
    display: none !important;
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

.clear-btn,
.apply-btn {
    border-radius: 0.6rem;
    border: 1px solid hsl(var(--primary));
    padding: 0.48rem 0.85rem;
    font-weight: 600;
    font-size: 0.84rem;
    cursor: pointer;
}

.clear-btn {
    background: hsl(var(--card));
    color: hsl(var(--primary));
}

.clear-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.apply-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.employee-details-card {
    display: grid;
    gap: 0.7rem;
    align-content: start;
    align-self: start;
    height: fit-content;
}

.employee-details-card label {
    display: grid;
    gap: 0.38rem;
    color: hsl(var(--muted-foreground));
    font-size: 0.84rem;
}

.employee-details-card .salary-note {
    color: hsl(var(--destructive)) !important;
    font-size: 0.72rem;
    font-weight: 600;
    line-height: 1;
}

.employee-details-card .salary-label-row {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    flex-wrap: nowrap;
    white-space: nowrap;
}

.employee-details-card .info-readonly {
    border-radius: 0.7rem;
    border: 1.5px solid hsl(var(--muted-foreground) / 0.45);
    background: hsl(var(--card));
    color: hsl(var(--foreground));
    font-size: 0.92rem;
    padding: 0.65rem 0.75rem;
    min-height: 42px;
    display: flex;
    align-items: center;
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
