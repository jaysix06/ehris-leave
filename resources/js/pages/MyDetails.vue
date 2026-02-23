<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    FileText,
    GraduationCap,
    Mail,
    MapPin,
    Pencil,
    Phone,
    User,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'Employee';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employee',
    },
    {
        title: 'Employee Detail',
    },
];

// Tabs matching the My Details UI (two rows)
const tabs = [
    'Official Info',
    'Personal Info',
    'Family Background',
    'Education Background',
    'Eligibility',
    'Work Experience',
    'Affiliation',
    'Training',
    'Others',
];

const activeTab = ref(0);
const avatarImageError = ref(false);

type Profile = {
    hrId?: number | null;
    email?: string | null;
    lastname?: string | null;
    firstname?: string | null;
    middlename?: string | null;
    extname?: string | null;
    avatar?: string | null;
    job_title?: string | null;
    role?: string | null;
    fullname?: string | null;
};

const props = defineProps<{
    profile?: Profile | null;
    officialInfo?: Record<string, unknown> | null;
    personalInfo?: Record<string, unknown> | null;
    contactInfo?: Record<string, unknown> | null;
    family?: Record<string, unknown>[];
    education?: Record<string, unknown>[];
    workExperience?: Record<string, unknown>[];
    eligibility?: Record<string, unknown>[];
    serviceRecord?: Record<string, unknown>[];
    leaveHistory?: Record<string, unknown>[];
    documents?: Record<string, unknown>[];
    training?: Record<string, unknown>[];
    awards?: Record<string, unknown>[];
    performance?: Record<string, unknown>[];
    researches?: Record<string, unknown>[];
    expertise?: Record<string, unknown>[];
    affiliation?: Record<string, unknown>[];
}>();

const page = usePage();
const authUser = computed(() => page.props.auth.user);

const employeeName = computed(() => {
    if (props.profile?.fullname) {
        return props.profile.fullname;
    }
    const o = props.officialInfo;
    if (o?.firstname || o?.lastname) {
        return [o.firstname, o.middlename, o.lastname].filter(Boolean).join(' ');
    }
    const authName = authUser.value?.name;
    return typeof authName === 'string' && authName.length > 0
        ? authName
        : 'N/A';
});

const employeeId = computed(() => {
    if (props.profile?.hrId !== null && props.profile?.hrId !== undefined) {
        return String(props.profile.hrId);
    }
    const o = props.officialInfo;
    if (o?.hrid != null) return String(o.hrid);
    const authId = authUser.value?.id;
    return authId !== null && authId !== undefined ? String(authId) : 'N/A';
});

const employeeEmail = computed(() => {
    if (props.profile?.email) return props.profile.email;
    const c = props.contactInfo;
    if (c?.email) return String(c.email);
    const o = props.officialInfo;
    if (o?.email) return String(o.email);
    const authEmail = authUser.value?.email;
    return typeof authEmail === 'string' && authEmail.length > 0 ? authEmail : 'N/A';
});

const employeeJobTitle = computed(() => {
    return (props.profile?.job_title ?? props.officialInfo?.job_title) as string || 'N/A';
});

const avatarSrc = computed(() => {
    const avatar = props.profile?.avatar || authUser.value?.avatar;
    if (typeof avatar !== 'string' || avatar.length === 0) return null;
    // Don't show default avatar placeholder images
    if (avatar.includes('avatar-default') || avatar.includes('default')) return null;
    if (avatar.startsWith('http://') || avatar.startsWith('https://') || avatar.startsWith('/')) {
        return avatar;
    }
    return `/${avatar}`;
});

// Reset avatar error when avatarSrc changes
watch(avatarSrc, () => {
    avatarImageError.value = false;
});


const employeeNo = computed(() => {
    const o = props.officialInfo;
    if (o?.employee_id != null) return String(o.employee_id);
    return 'N/A';
});

const contactNo = computed(() => {
    const c = props.contactInfo;
    if (c?.mobile_num != null && String(c.mobile_num).trim() !== '') return String(c.mobile_num);
    const o = props.officialInfo;
    if (o?.mobile_number != null && String(o.mobile_number).trim() !== '') return String(o.mobile_number);
    return 'N/A';
});

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

// Personal info rows from DB (all fields that have values)
const personalInfoRows = computed(() => {
    const p = props.personalInfo;
    if (!p) return [];
    const rows = [
        { label: 'Place of birth', value: p.pob },
        { label: 'Birth date', value: p.dob },
        { label: 'Blood type', value: p.blood_type },
        { label: 'Marital status', value: p.civil_stat },
        { label: 'Citizenship', value: p.citizenship },
        { label: 'Gender', value: p.gender },
        { label: 'Height', value: p.height },
        { label: 'Weight', value: p.weight },
        { label: 'PRC no.', value: p.prc_no },
        { label: 'TIN', value: p.tin },
        { label: 'SSS', value: p.sss },
        { label: 'GSIS', value: p.gsis },
        { label: 'PhilHealth', value: p.philhealth },
        { label: 'Pag-IBIG', value: p.pag_ibig },
    ];
    return rows.filter((r) => r.value != null && String(r.value).trim() !== '');
});

// Family display: relationship + full name
function familyName(item: Record<string, unknown>): string {
    const parts = [item.firstname, item.middlename, item.lastname, item.extension].filter(Boolean);
    return parts.map(String).join(' ').trim() || '—';
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page">
            <section class="ehris-tabs" aria-label="Employee detail sections">
                <button
                    v-for="(tab, index) in tabs"
                    :key="tab"
                    type="button"
                    class="ehris-tab"
                    :class="{ 'is-active': activeTab === index }"
                    @click="activeTab = index"
                >
                    {{ tab }}
                </button>
            </section>

            <!-- My Details summary: always visible, does not change when switching tabs -->
            <section class="ehris-card ehris-mydetails-summary" aria-label="My Details">
                <h2 class="ehris-mydetails-summary-title">My Details</h2>
                <div class="ehris-mydetails-summary-inner">
                    <div class="ehris-avatar ehris-avatar-placeholder ehris-mydetails-avatar" :aria-label="employeeName">
                        <img v-if="avatarSrc && !avatarImageError" :src="avatarSrc" class="ehris-avatar" :alt="employeeName" @error="avatarImageError = true">
                        <div v-if="!avatarSrc || avatarImageError" class="ehris-avatar-default-wrapper">
                            <User class="ehris-avatar-default-icon" />
                        </div>
                    </div>
                    <dl class="ehris-mydetails-fields">
                        <div class="ehris-details-row">
                            <dt>Employee Name</dt>
                            <dd>{{ employeeName }}</dd>
                        </div>
                        <div class="ehris-details-row">
                            <dt>HR ID</dt>
                            <dd>{{ employeeId }}</dd>
                        </div>
                        <div class="ehris-details-row">
                            <dt>Employee No</dt>
                            <dd>{{ employeeNo }}</dd>
                        </div>
                        <div class="ehris-details-row">
                            <dt>Email</dt>
                            <dd>
                                <a
                                    v-if="employeeEmail !== 'N/A'"
                                    :href="`mailto:${employeeEmail}`"
                                    class="ehris-email-link"
                                >
                                    {{ employeeEmail }}
                                </a>
                                <span v-else>{{ employeeEmail }}</span>
                            </dd>
                        </div>
                        <div class="ehris-details-row">
                            <dt>Contact No</dt>
                            <dd>{{ contactNo }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <h2 class="ehris-page-title">{{ tabs[activeTab] }}</h2>

            <!-- OFFICIAL INFO: three-column layout + Grade & Subject Taught button -->
            <template v-if="activeTab === 0">
                <section class="ehris-card">
                    <div class="ehris-official-info-header">
                        <h3>Official information</h3>
                        <button
                            type="button"
                            class="ehris-btn-grade-subject"
                            aria-label="Grade and subject taught"
                        >
                            <Pencil class="size-4" />
                            <span>Grade & Subject Taught</span>
                        </button>
                    </div>
                    <div v-if="officialInfo" class="ehris-official-info-grid">
                        <dl class="ehris-official-info-col">
                            <div class="ehris-details-row"><dt>Employee No</dt><dd>{{ val(officialInfo.employee_id) }}</dd></div>
                            <div class="ehris-details-row"><dt>HR ID</dt><dd>{{ val(officialInfo.hrid) }}</dd></div>
                            <div class="ehris-details-row"><dt>Prefix Name</dt><dd>{{ val(officialInfo.prefix_name) }}</dd></div>
                            <div class="ehris-details-row"><dt>Firstname</dt><dd>{{ val(officialInfo.firstname) }}</dd></div>
                            <div class="ehris-details-row"><dt>Middlename</dt><dd>{{ val(officialInfo.middlename) }}</dd></div>
                            <div class="ehris-details-row"><dt>Lastname</dt><dd>{{ val(officialInfo.lastname) }}</dd></div>
                            <div class="ehris-details-row"><dt>Extension</dt><dd>{{ val(officialInfo.extension) }}</dd></div>
                            <div class="ehris-details-row"><dt>DepEd Email</dt><dd>
                                <a
                                    v-if="officialInfo.email"
                                    :href="`mailto:${officialInfo.email}`"
                                    class="ehris-email-link"
                                >{{ val(officialInfo.email) }}</a>
                                <span v-else>—</span>
                            </dd></div>
                        </dl>
                        <dl class="ehris-official-info-col">
                            <div class="ehris-details-row"><dt>Salary Grade</dt><dd>{{ val(officialInfo.salary_grade) }}</dd></div>
                            <div class="ehris-details-row"><dt>Step</dt><dd>{{ val(officialInfo.step) }}</dd></div>
                            <div class="ehris-details-row"><dt>Role</dt><dd>{{ val(officialInfo.role) }}</dd></div>
                            <div class="ehris-details-row"><dt>Division Office</dt><dd>{{ val(officialInfo.division_code) }}</dd></div>
                            <div class="ehris-details-row"><dt>Business Unit</dt><dd>{{ val(officialInfo.business_id) }}</dd></div>
                            <div class="ehris-details-row"><dt>Department</dt><dd>{{ val(officialInfo.office) }}</dd></div>
                            <div class="ehris-details-row"><dt>Reporting Manager</dt><dd>{{ val(officialInfo.reporting_manager) }}</dd></div>
                        </dl>
                        <dl class="ehris-official-info-col">
                            <div class="ehris-details-row"><dt>Item No.</dt><dd>{{ val(officialInfo.item_no) }}</dd></div>
                            <div class="ehris-details-row"><dt>Plantilla Assignment.</dt><dd>{{ val(officialInfo.plantilla) }}</dd></div>
                            <div class="ehris-details-row"><dt>Position</dt><dd>{{ val(officialInfo.job_title) }}</dd></div>
                            <div class="ehris-details-row"><dt>Employment Status</dt><dd>{{ val(officialInfo.employ_status) }}</dd></div>
                            <div class="ehris-details-row"><dt>Date of Joining</dt><dd>{{ val(officialInfo.date_of_joining) }}</dd></div>
                            <div class="ehris-details-row"><dt>Last Date of Promotion</dt><dd>{{ val(officialInfo.date_of_promotion) }}</dd></div>
                            <div class="ehris-details-row"><dt>Years of Experience</dt><dd>{{ val(officialInfo.year_experience) }}</dd></div>
                        </dl>
                    </div>
                    <p v-else class="ehris-muted">No official information on file.</p>
                </section>
            </template>

            <!-- PERSONAL INFO -->
            <template v-if="activeTab === 1">
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Personal information</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit personal information">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div v-if="personalInfo" class="ehris-official-info-grid">
                        <dl class="ehris-official-info-col">
                            <div class="ehris-details-row"><dt>Gender</dt><dd>{{ val(personalInfo.gender) }}</dd></div>
                            <div class="ehris-details-row"><dt>Citizenship</dt><dd>{{ val(personalInfo.citizenship) }}</dd></div>
                            <div class="ehris-details-row"><dt>Pls. Indicate Country</dt><dd>{{ val(personalInfo.country) }}</dd></div>
                            <div class="ehris-details-row"><dt>Height (in Meters)</dt><dd>{{ val(personalInfo.height) }}</dd></div>
                            <div class="ehris-details-row"><dt>Date of Birth</dt><dd>{{ val(personalInfo.dob) }}</dd></div>
                        </dl>
                        <dl class="ehris-official-info-col">
                            <div class="ehris-details-row"><dt>Civil Status</dt><dd>{{ val(personalInfo.civil_stat) }}</dd></div>
                            <div class="ehris-details-row"><dt>If Dual Citizenship</dt><dd>{{ val(personalInfo.dual_citizenship) }}</dd></div>
                            <div class="ehris-details-row"><dt>Blood Type</dt><dd>{{ val(personalInfo.blood_type) }}</dd></div>
                            <div class="ehris-details-row"><dt>Weight (in Kilograms)</dt><dd>{{ val(personalInfo.weight) }}</dd></div>
                            <div class="ehris-details-row"><dt>Place of Birth</dt><dd>{{ val(personalInfo.pob) }}</dd></div>
                        </dl>
                    </div>
                    <h4 class="ehris-gov-id-header">GOVERNMENT IDENTIFICATION</h4>
                    <div v-if="personalInfo" class="ehris-official-info-grid">
                        <dl class="ehris-official-info-col">
                            <div class="ehris-details-row"><dt>PRC No.</dt><dd>{{ val(personalInfo.prc_no) }}</dd></div>
                            <div class="ehris-details-row"><dt>TIN</dt><dd>{{ val(personalInfo.tin) }}</dd></div>
                            <div class="ehris-details-row"><dt>GSIS BP No.</dt><dd>{{ val(personalInfo.gsis_bp) }}</dd></div>
                            <div class="ehris-details-row"><dt>PAG-IBIG No.</dt><dd>{{ val(personalInfo.pag_ibig) }}</dd></div>
                        </dl>
                        <dl class="ehris-official-info-col">
                            <div class="ehris-details-row"><dt>SSS No.</dt><dd>{{ val(personalInfo.sss) }}</dd></div>
                            <div class="ehris-details-row"><dt>Philhealth No.</dt><dd>{{ val(personalInfo.philhealth) }}</dd></div>
                            <div class="ehris-details-row"><dt>GSIS No.</dt><dd>{{ val(personalInfo.gsis) }}</dd></div>
                        </dl>
                    </div>
                    <p v-if="!personalInfo" class="ehris-muted">No personal information on file.</p>
                </section>
            </template>

            <!-- FAMILY BACKGROUND -->
            <template v-if="activeTab === 2">
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Family Background</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit family">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div class="ehris-table-wrap" v-if="family && family.length">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>Relationship</th>
                                    <th>Name</th>
                                    <th>DOB</th>
                                    <th>Occupation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in family" :key="i">
                                    <td>{{ val(item.relationship) }}</td>
                                    <td>{{ familyName(item) }}</td>
                                    <td>{{ val(item.dob) }}</td>
                                    <td>{{ val(item.occupation) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="ehris-muted">No family information on file.</p>
                </section>
            </template>

            <!-- EDUCATION BACKGROUND -->
            <template v-if="activeTab === 3">
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Education Background</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit education">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <ul class="ehris-timeline" v-if="education && education.length">
                        <li v-for="(item, i) in education" :key="i">
                            <span class="ehris-timeline-dot" aria-hidden="true"></span>
                            <div>
                                <p class="ehris-degree"><GraduationCap class="size-4" /><span>{{ val(item.education_level) }} – {{ val(item.school_name) }}</span></p>
                                <p>{{ val(item.course) }}</p>
                                <p class="ehris-muted"><Calendar class="size-4" /><span>{{ val(item.from_year) }} – {{ val(item.to_year) }} ({{ val(item.year_graduated) }})</span></p>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="ehris-muted">No education records on file.</p>
                </section>
            </template>

            <!-- ELIGIBILITY -->
            <template v-if="activeTab === 4">
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Eligibility (Civil service)</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit eligibility">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div class="ehris-table-wrap" v-if="eligibility && eligibility.length">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Rating</th>
                                    <th>Date of exam</th>
                                    <th>Place</th>
                                    <th>License no.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in eligibility" :key="i">
                                    <td>{{ val(item.title) }}</td>
                                    <td>{{ val(item.rating) }}</td>
                                    <td>{{ val(item.date_exam) }}</td>
                                    <td>{{ val(item.place_exam) }}</td>
                                    <td>{{ val(item.license_no) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="ehris-muted">No eligibility records on file.</p>
                </section>
            </template>

            <!-- WORK EXPERIENCE -->
            <template v-if="activeTab === 5">
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Work experience</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit work experience">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div class="ehris-table-wrap" v-if="workExperience && workExperience.length">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>Company</th>
                                    <th>Position</th>
                                    <th>Inclusive dates</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in workExperience" :key="i">
                                    <td>{{ val(item.company_name) }}</td>
                                    <td>{{ val(item.position_title) }}</td>
                                    <td>{{ val(item.inclusive_date_from) }} – {{ val(item.inclusive_date_to) }}</td>
                                    <td>{{ val(item.employment_status) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="ehris-muted">No work experience on file.</p>
                </section>
            </template>

            <!-- AFFILIATION -->
            <template v-if="activeTab === 6">
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Affiliation</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit affiliation">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <ul class="ehris-stacked-list" v-if="affiliation && affiliation.length">
                        <li v-for="(item, i) in affiliation" :key="i">
                            {{ val(item.affiliation) }}
                        </li>
                    </ul>
                    <p v-else class="ehris-muted">No affiliation on file.</p>
                </section>
            </template>

            <!-- TRAINING -->
            <template v-if="activeTab === 7">
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Training</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit training">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div class="ehris-table-wrap" v-if="training && training.length">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Venue</th>
                                    <th>Start – End</th>
                                    <th>Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in training" :key="i">
                                    <td>{{ val(item.training_title) }}</td>
                                    <td>{{ val(item.training_venue) }}</td>
                                    <td>{{ val(item.start_date) }} – {{ val(item.end_date) }}</td>
                                    <td>{{ val(item.number_hours) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="ehris-muted">No training on file.</p>
                </section>
            </template>

            <!-- OTHERS: Combined section for Service Record, Leave History, Documents, Awards, Performance, Researches, Expertise -->
            <template v-if="activeTab === 8">
                <!-- SERVICE RECORD -->
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Service Record</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit service record">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div class="ehris-table-wrap" v-if="serviceRecord && serviceRecord.length">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Designation</th>
                                    <th>Status</th>
                                    <th>Place of assignment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in serviceRecord" :key="i">
                                    <td>{{ val(item.service_from) }}</td>
                                    <td>{{ val(item.service_to) }}</td>
                                    <td>{{ val(item.designation) }}</td>
                                    <td>{{ val(item.status) }}</td>
                                    <td>{{ val(item.place_of_assign) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="ehris-muted">No service record on file.</p>
                </section>

                <!-- LEAVE HISTORY -->
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Leave History</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit leave history">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div class="ehris-table-wrap" v-if="leaveHistory && leaveHistory.length">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>Credits from</th>
                                    <th>Credits to</th>
                                    <th>Type</th>
                                    <th>No. of days</th>
                                    <th>Balance</th>
                                    <th>Particulars</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in leaveHistory" :key="i">
                                    <td>{{ val(item.credits_from) }}</td>
                                    <td>{{ val(item.credits_to) }}</td>
                                    <td>{{ val(item.type) }}</td>
                                    <td>{{ val(item.no_of_days) }}</td>
                                    <td>{{ val(item.balance) }}</td>
                                    <td>{{ val(item.particulars) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="ehris-muted">No leave history on file.</p>
                </section>

                <!-- DOCUMENTS -->
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Documents</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit documents">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <ul class="ehris-stacked-list" v-if="documents && documents.length">
                        <li v-for="(item, i) in documents" :key="i" class="ehris-doc-row">
                            <FileText class="size-4" />
                            <span>{{ val(item.title) }}</span>
                            <span v-if="item.document" class="ehris-muted"> – {{ val(item.document) }}</span>
                        </li>
                    </ul>
                    <p v-else class="ehris-muted">No documents on file.</p>
                </section>

                <!-- AWARDS -->
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Awards</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit awards">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div class="ehris-table-wrap" v-if="awards && awards.length">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>Award title</th>
                                    <th>Category</th>
                                    <th>School year</th>
                                    <th>Award</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in awards" :key="i">
                                    <td>{{ val(item.award_title) }}</td>
                                    <td>{{ val(item.category) }}</td>
                                    <td>{{ val(item.school_year) }}</td>
                                    <td>{{ val(item.award) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="ehris-muted">No awards on file.</p>
                </section>

                <!-- PERFORMANCE -->
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Performance</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit performance">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div class="ehris-table-wrap" v-if="performance && performance.length">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th>CBC</th>
                                    <th>Other competencies</th>
                                    <th>KRA</th>
                                    <th>Adjectival rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in performance" :key="i">
                                    <td>{{ val(item.year) }}</td>
                                    <td>{{ val(item.cbc) }}</td>
                                    <td>{{ val(item.other_competencies) }}</td>
                                    <td>{{ val(item.kra) }}</td>
                                    <td>{{ val(item.adjectival_rating) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="ehris-muted">No performance records on file.</p>
                </section>

                <!-- RESEARCHES -->
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Researches</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit researches">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <div class="ehris-table-wrap" v-if="researches && researches.length">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Year conducted</th>
                                    <th>Category</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, i) in researches" :key="i">
                                    <td>{{ val(item.title_of_research) }}</td>
                                    <td>{{ val(item.year_conducted) }}</td>
                                    <td>{{ val(item.category) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="ehris-muted">No researches on file.</p>
                </section>

                <!-- EXPERTISE -->
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Expertise</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit expertise">
                            <Pencil class="size-4" />
                        </button>
                    </div>
                    <ul class="ehris-stacked-list" v-if="expertise && expertise.length">
                        <li v-for="(item, i) in expertise" :key="i">
                            {{ val(item.expertise) }}
                        </li>
                    </ul>
                    <p v-else class="ehris-muted">No expertise on file.</p>
                </section>
            </template>

        </div>
    </AppLayout>
</template>

<style scoped>
.ehris-mydetails-avatar {
    width: 240px !important;
    height: 240px !important;
    min-width: 240px;
    min-height: 240px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f3f4f6;
    border-radius: 0.5rem;
}

.ehris-mydetails-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 0.5rem;
}

.ehris-gov-id-header {
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #dc2626;
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
}

.ehris-avatar-default-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.ehris-avatar-default-icon {
    width: 120px;
    height: 120px;
    color: #9ca3af;
    stroke-width: 1.5;
}

.ehris-mydetails-fields dd {
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
