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
import { DatePicker } from 'v-calendar';
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

const isFiledInAdvance = computed(() => !!leaveRange.value.start && leaveRange.value.start > today);
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
const isLeaveTypeUnselected = computed(() => selectedLeaveType.value === defaultLeaveTypeLabel);
const calendarKey = ref(0);
const isSickLeave = computed(() => selectedLeaveType.value === 'Sick Leave');
const isMaternityLeave = computed(() => selectedLeaveType.value === 'Maternity Leave');
const isPaternityLeave = computed(() => selectedLeaveType.value === 'Paternity Leave');
const isVacationLeave = computed(() => selectedLeaveType.value === 'Vacation Leave');
const isSpecialPrivilegeLeave = computed(() => selectedLeaveType.value === 'Special Privilege Leave');
const isSoloParentLeave = computed(() => selectedLeaveType.value === 'Solo Parent Leave');
const isStudyLeave = computed(() => selectedLeaveType.value === 'Study Leave');
const isVawcLeave = computed(
    () => selectedLeaveType.value === 'VAWC Leave' || selectedLeaveType.value === '10-Day VAWC Leave',
);
const isRehabilitationLeave = computed(
    () => selectedLeaveType.value === 'Rehabilitation Leave' || selectedLeaveType.value === 'Rehabilitation Privilege',
);
const isSpecialWomenLeave = computed(() => selectedLeaveType.value === 'Special Leave Benefits for Women');
const isCalamityLeave = computed(() => selectedLeaveType.value === 'Special Emergency (Calamity) Leave');
const isMonetizationLeave = computed(() => selectedLeaveType.value === 'Monetization of Leave Credits');
const isTerminalLeave = computed(() => selectedLeaveType.value === 'Terminal Leave');
const isAdoptionLeave = computed(() => selectedLeaveType.value === 'Adoption Leave');
const isMandatoryForceLeave = computed(
    () =>
        selectedLeaveType.value === 'Mandatory/Force Leave' ||
        selectedLeaveType.value === 'Mandatory Leave' ||
        selectedLeaveType.value === 'Forced Leave',
);
const leaveTypeDayLimit = computed<number | null>(() => {
    if (isPaternityLeave.value) return 7;
    if (isMaternityLeave.value) return 105;
    if (isSpecialPrivilegeLeave.value) return 3;
    if (isSoloParentLeave.value) return 7;
    if (isStudyLeave.value) return 180;
    if (isVawcLeave.value) return 10;
    if (isRehabilitationLeave.value) return 180;
    if (isSpecialWomenLeave.value) return 60;
    if (isCalamityLeave.value) return 5;
    if (isMandatoryForceLeave.value) return 5;
    return null;
});


const minSelectableDate = computed<Date | null>(() => {
    if (isSickLeave.value || isVawcLeave.value || isSpecialWomenLeave.value) {
        return null;
    }
    if (isVacationLeave.value || isSoloParentLeave.value) {
        return addDays(today, 5);
    }
    if (isSpecialPrivilegeLeave.value) {
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
const reason = ref<string>('');
const commutation = ref<string>('');
const consultationAvailed = ref<'yes' | 'no' | ''>('');
const medicalCertification = ref<File | null>(null);
const affidavitFile = ref<File | null>(null);
const medicalFileInput = ref<HTMLInputElement | null>(null);
const affidavitFileInput = ref<HTMLInputElement | null>(null);
const proofOfDelivery = ref<File | null>(null);
const proofOfDeliveryInput = ref<HTMLInputElement | null>(null);
const isMedicalDropActive = ref(false);
const isAffidavitDropActive = ref(false);
const isProofOfDeliveryDropActive = ref(false);
const supportingDocuments = ref<File[]>([]);
const supportingDocumentsInput = ref<HTMLInputElement | null>(null);
const destinationScope = ref<'within_ph' | 'abroad' | ''>('');
const destinationDetails = ref('');
const travelAuthorityNo = ref('');
const isEmergencySpl = ref(false);
const emergencyReason = ref('');
const isTimingOverride = ref(false);
const timingOverrideReason = ref('');
const accidentDate = ref('');
const surgeryDate = ref('');
const calamityDate = ref('');
const calamityType = ref('');
const calamityArea = ref('');
const residenceAddressSnapshot = ref('');
const soloParentIdNo = ref('');
const soloParentIdValidUntil = ref('');
const studyContractId = ref('');
const isPrivatePhysician = ref(false);
const supervisorNotes = ref('');
const separationType = ref('');
const separationEffectiveDate = ref('');
const creditsMonetized = ref<number | null>(null);
const isMandatoryLeave = ref(false);
const submitError = ref<string | null>(null);

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

const onProofOfDeliveryChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    proofOfDelivery.value = target.files?.[0] ?? null;
};

const openProofOfDeliveryPicker = () => {
    proofOfDeliveryInput.value?.click();
};

const onProofOfDeliveryDrop = (event: DragEvent) => {
    event.preventDefault();
    isProofOfDeliveryDropActive.value = false;
    proofOfDelivery.value = event.dataTransfer?.files?.[0] ?? null;
};

const clearProofOfDelivery = () => {
    proofOfDelivery.value = null;
    if (proofOfDeliveryInput.value) {
        proofOfDeliveryInput.value.value = '';
    }
};

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
        calendarKey.value += 1;
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
        if (!isMaternityLeave.value) {
            medicalCertification.value = null;
        }
        affidavitFile.value = null;
    }

    if (requiresSupportingDoc.value) {
        if (consultationAvailed.value === 'yes') {
            affidavitFile.value = null;
        } else if (consultationAvailed.value === 'no') {
            medicalCertification.value = null;
        }
    }

    if (!isPaternityLeave.value) {
        proofOfDelivery.value = null;
    }

    if (!(isVacationLeave.value || isSpecialPrivilegeLeave.value)) {
        destinationScope.value = '';
        destinationDetails.value = '';
        travelAuthorityNo.value = '';
    }

    if (!isSpecialPrivilegeLeave.value) {
        isEmergencySpl.value = false;
        emergencyReason.value = '';
    }

    if (!(isVacationLeave.value || isSoloParentLeave.value || isRehabilitationLeave.value)) {
        isTimingOverride.value = false;
        timingOverrideReason.value = '';
    }

    if (!isRehabilitationLeave.value) {
        accidentDate.value = '';
        isPrivatePhysician.value = false;
    }

    if (!isSpecialWomenLeave.value) {
        surgeryDate.value = '';
    }

    if (!isCalamityLeave.value) {
        calamityDate.value = '';
        calamityType.value = '';
        calamityArea.value = '';
        residenceAddressSnapshot.value = '';
    }

    if (!isSoloParentLeave.value) {
        soloParentIdNo.value = '';
        soloParentIdValidUntil.value = '';
    }

    if (!isStudyLeave.value) {
        studyContractId.value = '';
    }

    if (!isMonetizationLeave.value) {
        creditsMonetized.value = null;
    }

    if (!isTerminalLeave.value) {
        separationType.value = '';
        separationEffectiveDate.value = '';
    }

    if (!(isVacationLeave.value || isMandatoryForceLeave.value)) {
        isMandatoryLeave.value = false;
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
    if (!leaveRange.value.start || !leaveRange.value.end) {
        submitError.value = 'Please select both start and end dates.';
        return;
    }

    if (leaveTypeDayLimit.value !== null && noOfDays.value > leaveTypeDayLimit.value) {
        submitError.value = `${selectedLeaveType.value} cannot exceed ${leaveTypeDayLimit.value} days.`;
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

    if (isPaternityLeave.value) {
        if (!proofOfDelivery.value) {
            submitError.value = 'Please upload proof of child\'s delivery (e.g. birth certificate, medical certificate, or marriage contract).';
            return;
        }
    }

    if ((isVacationLeave.value || isSpecialPrivilegeLeave.value) && !destinationScope.value) {
        submitError.value = 'Please indicate if your destination is within the Philippines or abroad.';
        return;
    }

    if (isSpecialPrivilegeLeave.value && isEmergencySpl.value && !emergencyReason.value.trim()) {
        submitError.value = 'Please provide emergency reason for Special Privilege Leave.';
        return;
    }

    if (isRehabilitationLeave.value && !accidentDate.value) {
        submitError.value = 'Accident date is required for Rehabilitation Leave.';
        return;
    }

    if (isCalamityLeave.value && !calamityDate.value) {
        submitError.value = 'Calamity date is required for Special Emergency (Calamity) Leave.';
        return;
    }

    if (isMonetizationLeave.value && (!creditsMonetized.value || creditsMonetized.value <= 0)) {
        submitError.value = 'Please provide credits to monetize.';
        return;
    }

    if (isTerminalLeave.value && (!separationType.value || !separationEffectiveDate.value)) {
        submitError.value = 'Please provide separation type and effective date for Terminal Leave.';
        return;
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
            proof_of_delivery: proofOfDelivery.value,
            destination_scope: destinationScope.value || null,
            destination_details: destinationDetails.value || null,
            travel_authority_no: travelAuthorityNo.value || null,
            is_emergency_spl: isEmergencySpl.value,
            emergency_reason: emergencyReason.value || null,
            is_timing_override: isTimingOverride.value,
            timing_override_reason: timingOverrideReason.value || null,
            accident_date: accidentDate.value || null,
            surgery_date: surgeryDate.value || null,
            calamity_date: calamityDate.value || null,
            calamity_type: calamityType.value || null,
            calamity_area: calamityArea.value || null,
            residence_address_snapshot: residenceAddressSnapshot.value || null,
            solo_parent_id_no: soloParentIdNo.value || null,
            solo_parent_id_valid_until: soloParentIdValidUntil.value || null,
            study_contract_id: studyContractId.value || null,
            is_private_physician: isPrivatePhysician.value,
            supervisor_notes: supervisorNotes.value || null,
            separation_type: separationType.value || null,
            separation_effective_date: separationEffectiveDate.value || null,
            credits_monetized: creditsMonetized.value ?? null,
            is_mandatory_leave: isMandatoryLeave.value,
            supporting_documents: supportingDocuments.value,
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

                            <label v-if="isVacationLeave || isSpecialPrivilegeLeave">
                                Destination Scope
                                <div class="flex flex-wrap gap-12 mt-2">
                                    <label class="radio-option inline-flex gap-2 cursor-pointer">
                                        <input type="radio" v-model="destinationScope" value="within_ph" />
                                        Within Philippines
                                    </label>
                                    <label class="radio-option inline-flex gap-2 cursor-pointer">
                                        <input type="radio" v-model="destinationScope" value="abroad" />
                                        Abroad
                                    </label>
                                </div>
                            </label>
                            <label v-if="isVacationLeave || isSpecialPrivilegeLeave">
                                Destination Details (Optional)
                                <input v-model="destinationDetails" type="text" />
                            </label>
                            <label v-if="isVacationLeave || isSpecialPrivilegeLeave">
                                Travel Authority No. (Optional)
                                <input v-model="travelAuthorityNo" type="text" />
                            </label>

                            <label v-if="isSpecialPrivilegeLeave">
                                <span class="inline-flex items-center gap-2">
                                    <input v-model="isEmergencySpl" type="checkbox" />
                                    Emergency filing
                                </span>
                            </label>
                            <label v-if="isSpecialPrivilegeLeave && isEmergencySpl">
                                Emergency Reason
                                <textarea v-model="emergencyReason" placeholder="Provide emergency justification..." />
                            </label>

                            <label v-if="isVacationLeave || isSoloParentLeave || isRehabilitationLeave">
                                <span class="inline-flex items-center gap-2">
                                    <input v-model="isTimingOverride" type="checkbox" />
                                    Timing exception override
                                </span>
                            </label>
                            <label v-if="(isVacationLeave || isSoloParentLeave || isRehabilitationLeave) && isTimingOverride">
                                Timing Override Reason
                                <textarea v-model="timingOverrideReason" placeholder="Explain why normal filing window is not possible..." />
                            </label>

                            <label v-if="isRehabilitationLeave">
                                Accident Date
                                <input v-model="accidentDate" type="date" />
                            </label>
                            <label v-if="isRehabilitationLeave">
                                <span class="inline-flex items-center gap-2">
                                    <input v-model="isPrivatePhysician" type="checkbox" />
                                    Attending physician is private
                                </span>
                            </label>

                            <label v-if="isSpecialWomenLeave">
                                Surgery Date
                                <input v-model="surgeryDate" type="date" />
                            </label>

                            <label v-if="isCalamityLeave">
                                Calamity Date
                                <input v-model="calamityDate" type="date" />
                            </label>
                            <label v-if="isCalamityLeave">
                                Calamity Type
                                <input v-model="calamityType" type="text" />
                            </label>
                            <label v-if="isCalamityLeave">
                                Calamity Area
                                <input v-model="calamityArea" type="text" />
                            </label>
                            <label v-if="isCalamityLeave">
                                Residence Address Snapshot
                                <input v-model="residenceAddressSnapshot" type="text" />
                            </label>

                            <label v-if="isSoloParentLeave">
                                Solo Parent ID No. (Optional)
                                <input v-model="soloParentIdNo" type="text" />
                            </label>
                            <label v-if="isSoloParentLeave">
                                Solo Parent ID Valid Until (Optional)
                                <input v-model="soloParentIdValidUntil" type="date" />
                            </label>

                            <label v-if="isStudyLeave">
                                Study Contract ID (Optional)
                                <input v-model="studyContractId" type="text" />
                            </label>

                            <label v-if="isMonetizationLeave">
                                Credits Monetized
                                <input v-model.number="creditsMonetized" type="number" min="0" step="0.01" />
                            </label>

                            <label v-if="isTerminalLeave">
                                Separation Type
                                <select v-model="separationType">
                                    <option value="">Select separation type</option>
                                    <option value="resignation">Resignation</option>
                                    <option value="retirement">Retirement</option>
                                    <option value="separation">Separation</option>
                                </select>
                            </label>
                            <label v-if="isTerminalLeave">
                                Separation Effective Date
                                <input v-model="separationEffectiveDate" type="date" />
                            </label>

                            <label v-if="isVacationLeave || isMandatoryForceLeave">
                                <span class="inline-flex items-center gap-2">
                                    <input v-model="isMandatoryLeave" type="checkbox" />
                                    Count toward mandatory VL
                                </span>
                            </label>

                            <label>
                                Supervisor/Reviewer Notes (Optional)
                                <textarea v-model="supervisorNotes" placeholder="Optional notes for approving officers..." />
                            </label>
                        </div>

                        <div class="calendar-box">
                            <div class="calendar-picker-wrap" :class="{ 'is-disabled': isLeaveTypeUnselected }">
                                <DatePicker
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
                                <div v-if="isLeaveTypeUnselected" class="calendar-disabled-overlay" />
                            </div>
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

                                <template v-if="consultationAvailed === 'yes'">
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
                                </template>

                                <template v-else-if="consultationAvailed === 'no'">
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
                                </template>
                            </div>

                            <div
                                v-else-if="isMaternityLeave"
                                class="medical-cert-panel"
                            >
                                <p class="upload-title">Proof of Pregnancy</p>

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

                            <div
                                v-else-if="isPaternityLeave"
                                class="medical-cert-panel"
                            >
                                <p class="upload-title">Proof of Child's Delivery</p>
                                <p class="dropzone-sub">
                                    Required for Paternity Leave (max 7 days). Upload one document as proof (e.g. birth certificate, medical certificate, or marriage contract).
                                </p>
                                <div
                                    v-if="!proofOfDelivery"
                                    class="medical-dropzone"
                                    :class="{ 'is-active': isProofOfDeliveryDropActive }"
                                    @click="openProofOfDeliveryPicker"
                                    @dragover.prevent="isProofOfDeliveryDropActive = true"
                                    @dragleave.prevent="isProofOfDeliveryDropActive = false"
                                    @drop="onProofOfDeliveryDrop"
                                >
                                    <div class="dropzone-icon-wrap">
                                        <ImagePlus :size="30" />
                                    </div>
                                    <p class="dropzone-main">
                                        Drag &amp; drop
                                        <span>proof of child's delivery</span>
                                    </p>
                                    <p class="dropzone-sub">
                                        or
                                        <button type="button" class="browse-link" @click.stop="openProofOfDeliveryPicker">
                                            browse files
                                        </button>
                                        on your computer
                                    </p>
                                    <input
                                        ref="proofOfDeliveryInput"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="sr-only"
                                        @change="onProofOfDeliveryChange"
                                    />
                                </div>
                                <div v-if="proofOfDelivery" class="medical-file-row">
                                    <div class="file-meta">
                                        <p class="file-name">{{ proofOfDelivery.name }}</p>
                                        <p class="file-info">
                                            {{ Math.max(1, Math.round(proofOfDelivery.size / 1024)) }} KB
                                        </p>
                                    </div>
                                    <button type="button" class="remove-file-btn" @click="clearProofOfDelivery">
                                        <X :size="16" />
                                    </button>
                                </div>
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
                        <p v-if="submitError" class="text-sm text-destructive">{{ submitError }}</p>
                        <p v-else-if="$page.props.errors?.medical_certificate" class="text-sm text-destructive">
                            {{ $page.props.errors.medical_certificate }}
                        </p>
                        <p v-else-if="$page.props.errors?.affidavit" class="text-sm text-destructive">
                            {{ $page.props.errors.affidavit }}
                        </p>
                        <p v-else-if="$page.props.errors?.proof_of_delivery" class="text-sm text-destructive">
                            {{ $page.props.errors.proof_of_delivery }}
                        </p>
                        <p v-else-if="$page.props.errors?.leave_end_date" class="text-sm text-destructive">
                            {{ $page.props.errors.leave_end_date }}
                        </p>
                        <p
                            v-else-if="
                                Object.keys(($page.props.errors as Record<string, string>) ?? {}).length > 0
                            "
                            class="text-sm text-destructive"
                        >
                            {{
                                (
                                    Object.values(($page.props.errors as Record<string, string>) ?? {})[0] ??
                                    'Please review your inputs.'
                                )
                            }}
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
.left-form input,
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
