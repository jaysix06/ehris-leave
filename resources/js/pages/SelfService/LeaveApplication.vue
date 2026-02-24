<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { DatePicker } from 'v-calendar';
import {
    CheckCircle2,
    ImagePlus,
    SendHorizontal,
    X,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import { type BreadcrumbItem, type User } from '@/types';
import { addDays, differenceInDays } from 'date-fns';
import { echo } from '@laravel/echo-vue';

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

const leaveRange = ref<{ start: Date; end: Date }>({
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

const isFiledInAdvance = computed(() => leaveRange.value.start > today);
const requiresSupportingDoc = computed(
    () => isSickLeave.value && (isFiledInAdvance.value || noOfDays.value > 5),
);
const requiredDocType = computed(() => {
    if (!requiresSupportingDoc.value) return null;
    if (consultationAvailed.value === 'yes') return 'medical';
    if (consultationAvailed.value === 'no') return 'affidavit';
    return null;
});

const defaultLeaveTypeLabel = '- Select Leave Type -';
const selectedLeaveType = ref<string>(defaultLeaveTypeLabel);
const isSickLeave = computed(() => selectedLeaveType.value === 'Sick Leave');


const minSelectableDate = computed<Date | null>(() => {
    if (isSickLeave.value) {
        return null;
    }
    if (selectedLeaveType.value === 'Vacation Leave') {
        return addDays(today, 5);
    }
    return today;
});

const reason = ref<string>('');
const commutation = ref<string>('');
const consultationAvailed = ref<'yes' | 'no' | ''>('');
const medicalCertification = ref<File | null>(null);
const affidavitFile = ref<File | null>(null);
const medicalFileInput = ref<HTMLInputElement | null>(null);
const affidavitFileInput = ref<HTMLInputElement | null>(null);
const isMedicalDropActive = ref(false);
const isAffidavitDropActive = ref(false);
const submitError = ref<string | null>(null);

const page = usePage();
const authUser = computed(() => page.props.auth?.user as User | undefined);
const leaveEmployee = computed(() => page.props.leaveEmployee as Record<string, unknown> | undefined);
const dbLeaveTypes = computed(() => page.props.leaveTypes as string[] | undefined);
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

const onMedicalCertificationChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    medicalCertification.value = target.files?.[0] ?? null;
};

const openMedicalFilePicker = () => {
    medicalFileInput.value?.click();
};

const onMedicalDrop = (event: DragEvent) => {
    event.preventDefault();
    isMedicalDropActive.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    medicalCertification.value = file;
};

const clearMedicalCertification = () => {
    medicalCertification.value = null;
    if (medicalFileInput.value) {
        medicalFileInput.value.value = '';
    }
};

const onAffidavitChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    affidavitFile.value = target.files?.[0] ?? null;
};

const openAffidavitFilePicker = () => {
    affidavitFileInput.value?.click();
};

const onAffidavitDrop = (event: DragEvent) => {
    event.preventDefault();
    isAffidavitDropActive.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    affidavitFile.value = file;
};

const clearAffidavit = () => {
    affidavitFile.value = null;
    if (affidavitFileInput.value) {
        affidavitFileInput.value.value = '';
    }
};

watch(selectedLeaveType, () => {
    const minimumDate = minSelectableDate.value;
    if (minimumDate && leaveRange.value.start < minimumDate) {
        leaveRange.value = {
            start: minimumDate,
            end: minimumDate,
        };
    }
});

watch(leaveTypeOptions, (options) => {
    const isValid = options.some((option) => option.value === selectedLeaveType.value);
    if (!isValid) {
        selectedLeaveType.value = defaultLeaveTypeLabel;
    }
});

watch([selectedLeaveType, consultationAvailed], () => {
    submitError.value = null;
    if (!requiresSupportingDoc.value) {
        consultationAvailed.value = '';
        medicalCertification.value = null;
        affidavitFile.value = null;
    }
});

const formatDateForSubmit = (date: Date) => {
    const year = date.getFullYear();
    const month = `${date.getMonth() + 1}`.padStart(2, '0');
    const day = `${date.getDate()}`.padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const submitLeaveApplication = () => {
    submitError.value = null;

    if (selectedLeaveType.value === defaultLeaveTypeLabel) {
        submitError.value = 'Please select a leave type.';
        return;
    }

    if (requiresSupportingDoc.value) {
        if (requiredDocType.value === 'medical' && !medicalCertification.value) {
            submitError.value = 'Medical certificate is required when consultation was availed.';
            return;
        }
        if (requiredDocType.value === 'affidavit' && !affidavitFile.value) {
            submitError.value = 'Affidavit is required when consultation was not availed.';
            return;
        }
        if (!requiredDocType.value && !medicalCertification.value && !affidavitFile.value) {
            submitError.value = 'Please upload a medical certificate or an affidavit.';
            return;
        }
    }

    router.post(
        selfServiceRoutes.leaveApplication().url,
        {
            leave_type: selectedLeaveType.value,
            leave_start_date: formatDateForSubmit(leaveRange.value.start),
            leave_end_date: formatDateForSubmit(leaveRange.value.end),
            reason: reason.value || null,
            commutation: commutation.value || null,
            consultation_availed: consultationAvailed.value || null,
            medical_certificate: medicalCertification.value,
            affidavit: affidavitFile.value,
        },
        {
            forceFormData: true,
            preserveScroll: true,
        },
    );
};

const refreshLeaveTypes = () => {
    console.info('[LeaveApplication] LeaveTypeUpdated received. Refreshing leave types.');
    router.reload({ only: ['leaveTypes'] });
};

onMounted(() => {
    echo().channel('leave-types').listen('.LeaveTypeUpdated', refreshLeaveTypes);
});

onBeforeUnmount(() => {
    echo().channel('leave-types').stopListening('LeaveTypeUpdated');
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
                            <label v-if="selectedLeaveType === 'Others'">
                                <textarea placeholder="Specify..."></textarea>
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
                                        <input type="radio" v-model="reason" name="choice" value="a" />
                                        In Hospital
                                    </label>
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="reason" name="choice" value="b" />
                                        Outpatient
                                    </label>
                                </div>
                                <textarea placeholder="Specify Illness..."></textarea>
                            </label>
                            <label v-else-if="selectedLeaveType === 'Vacation Leave' || selectedLeaveType === 'Special Privilege Leave'">
                                Reason for {{ selectedLeaveType }}
                                <div class="flex flex-wrap gap-12 mt-2">
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="reason" name="within" value="Within the Philippines" />
                                        Within the Philippines
                                    </label>
                                    <label class="radio-option inline-flex  gap-2 cursor-pointer">
                                        <input type="radio" v-model="reason" name="abroad" value="Abroad" />
                                        Abroad
                                    </label>
                                </div>
                                <textarea placeholder="Specify..."></textarea>
                            </label>
                            <label v-else-if="selectedLeaveType === 'Special Leave Benefits for Women'">
                                Reason for {{ selectedLeaveType }}
                                <textarea placeholder="Specify..."></textarea>
                            </label>
                            <label v-else-if="selectedLeaveType === 'Study Leave'">
                                Reason for {{ selectedLeaveType }}
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <label class="radio-option inline-flex  gap-1 cursor-pointer">
                                        <input type="radio" v-model="reason" name="masters" value="Completion of Master's Degree" />
                                        Completion of Master's Degree
                                    </label>
                                    <label class="radio-option inline-flex  gap-1 cursor-pointer">
                                        <input type="radio" v-model="reason" name="review" value="BAR/Board Examination Review" />
                                        BAR/Board Examination Review
                                    </label>
                                </div>
                                <textarea placeholder="Specify..."></textarea>
                            </label>
                            <label v-else-if="selectedLeaveType === 'Others'">
                                Reason for {{ selectedLeaveType }}
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <label class="radio-option inline-flex  gap-1 cursor-pointer">
                                        <input type="radio" v-model="reason" name="monetization" value="Monetization of Leave Credits" />
                                        Monetization of Leave Credits
                                    </label>
                                    <label class="radio-option inline-flex  gap-1 cursor-pointer">
                                        <input type="radio" v-model="reason" name="terminal" value="Terminal Leave" />
                                        Terminal Leave
                                    </label>
                                </div>
                                <textarea placeholder="Specify..."></textarea>
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
                        </div>

                        <div class="calendar-box">
                            <DatePicker
                                v-model="leaveRange"
                                is-range
                                is-inline
                                expanded
                                :min-date="minSelectableDate ?? undefined"
                                :masks="{ weekdays: 'WWW' }"
                                class="calendar-inline"
                            />
                            <div
                                v-if="isSickLeave && requiresSupportingDoc"
                                class="medical-cert-panel"
                            >
                                <p class="upload-title">Supporting Documents Required</p>
                                <p class="dropzone-sub">
                                    Required when filed in advance or exceeding 5 days. Provide a medical certificate or an affidavit.
                                </p>

                                <label class="radio-option inline-flex gap-2 cursor-pointer">
                                    <input type="radio" v-model="consultationAvailed" name="consultation" value="yes" />
                                    Medical consultation availed
                                </label>
                                <label class="radio-option inline-flex gap-2 cursor-pointer">
                                    <input type="radio" v-model="consultationAvailed" name="consultation" value="no" />
                                    No medical consultation (affidavit)
                                </label>

                                <div
                                    v-if="!medicalCertification"
                                    class="medical-dropzone"
                                    :class="{ 'is-active': isMedicalDropActive }"
                                    @click="openMedicalFilePicker"
                                    @dragover.prevent="isMedicalDropActive = true"
                                    @dragleave.prevent="isMedicalDropActive = false"
                                    @drop="onMedicalDrop"
                                >
                                    <div class="dropzone-icon-wrap">
                                        <ImagePlus :size="30" />
                                    </div>
                                    <p class="dropzone-main">
                                        Drag &amp; drop
                                        <span>medical certificate</span>
                                    </p>
                                    <p class="dropzone-sub">
                                        or
                                        <button type="button" class="browse-link" @click.stop="openMedicalFilePicker">
                                            browse files
                                        </button>
                                        on your computer
                                    </p>
                                    <input
                                        ref="medicalFileInput"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="sr-only"
                                        @change="onMedicalCertificationChange"
                                    />
                                </div>

                                <div v-if="medicalCertification" class="medical-file-row">
                                    <div class="file-meta">
                                        <p class="file-name">{{ medicalCertification.name }}</p>
                                        <p class="file-info">
                                            {{ Math.max(1, Math.round(medicalCertification.size / 1024)) }} KB
                                        </p>
                                    </div>
                                    <button type="button" class="remove-file-btn" @click="clearMedicalCertification">
                                        <X :size="16" />
                                    </button>
                                </div>

                                <div
                                    v-if="!affidavitFile"
                                    class="medical-dropzone"
                                    :class="{ 'is-active': isAffidavitDropActive }"
                                    @click="openAffidavitFilePicker"
                                    @dragover.prevent="isAffidavitDropActive = true"
                                    @dragleave.prevent="isAffidavitDropActive = false"
                                    @drop="onAffidavitDrop"
                                >
                                    <div class="dropzone-icon-wrap">
                                        <ImagePlus :size="30" />
                                    </div>
                                    <p class="dropzone-main">
                                        Drag &amp; drop
                                        <span>affidavit</span>
                                    </p>
                                    <p class="dropzone-sub">
                                        or
                                        <button type="button" class="browse-link" @click.stop="openAffidavitFilePicker">
                                            browse files
                                        </button>
                                        on your computer
                                    </p>
                                    <input
                                        ref="affidavitFileInput"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="sr-only"
                                        @change="onAffidavitChange"
                                    />
                                </div>

                                <div v-if="affidavitFile" class="medical-file-row">
                                    <div class="file-meta">
                                        <p class="file-name">{{ affidavitFile.name }}</p>
                                        <p class="file-info">
                                            {{ Math.max(1, Math.round(affidavitFile.size / 1024)) }} KB
                                        </p>
                                    </div>
                                    <button type="button" class="remove-file-btn" @click="clearAffidavit">
                                        <X :size="16" />
                                    </button>
                                </div>
                            </div>

                            <div
                                v-else-if="selectedLeaveType === 'Maternity Leave' || selectedLeaveType === 'Paternity Leave'"
                                class="medical-cert-panel"
                            >
                                <p class="upload-title">Medical Certification</p>

                                <div
                                    v-if="!medicalCertification"
                                    class="medical-dropzone"
                                    :class="{ 'is-active': isMedicalDropActive }"
                                    @click="openMedicalFilePicker"
                                    @dragover.prevent="isMedicalDropActive = true"
                                    @dragleave.prevent="isMedicalDropActive = false"
                                    @drop="onMedicalDrop"
                                >
                                    <div class="dropzone-icon-wrap">
                                        <ImagePlus :size="30" />
                                    </div>
                                    <p class="dropzone-main">
                                        Drag &amp; drop
                                        <span>images, videos, or any file</span>
                                    </p>
                                    <p class="dropzone-sub">
                                        or
                                        <button type="button" class="browse-link" @click.stop="openMedicalFilePicker">
                                            browse files
                                        </button>
                                        on your computer
                                    </p>
                                    <input
                                        ref="medicalFileInput"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="sr-only"
                                        @change="onMedicalCertificationChange"
                                    />
                                </div>

                                <div v-if="medicalCertification" class="medical-file-row">
                                    <div class="file-meta">
                                        <p class="file-name">{{ medicalCertification.name }}</p>
                                        <p class="file-info">
                                            {{ Math.max(1, Math.round(medicalCertification.size / 1024)) }} KB
                                        </p>
                                    </div>
                                    <button type="button" class="remove-file-btn" @click="clearMedicalCertification">
                                        <X :size="16" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="request-actions">
                        <p v-if="submitError" class="text-sm text-destructive">{{ submitError }}</p>
                        <p v-else-if="$page.props.errors?.medical_certificate" class="text-sm text-destructive">
                            {{ $page.props.errors.medical_certificate }}
                        </p>
                        <p v-else-if="$page.props.errors?.affidavit" class="text-sm text-destructive">
                            {{ $page.props.errors.affidavit }}
                        </p>
                        <button class="cancel-btn" type="button">Cancel</button>
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
                        Reporting
                        <div class="info-readonly">{{ employeeDetails.reportingManager }}</div>
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
    border: 1px solid hsl(var(--border));
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

.cancel-btn,
.apply-btn {
    border-radius: 0.6rem;
    border: 1px solid hsl(var(--primary));
    padding: 0.48rem 0.85rem;
    font-weight: 600;
    font-size: 0.84rem;
    cursor: pointer;
}

.cancel-btn {
    background: hsl(var(--card));
    color: hsl(var(--primary));
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
    border: 1px solid hsl(var(--input));
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
