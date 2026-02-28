<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { echo } from '@laravel/echo-vue';
import { Download, User } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { useSidebar } from '@/components/ui/sidebar/utils';
import Affiliation from '@/pages/MyDetails/Affiliation.vue';
import EducationBackground from '@/pages/MyDetails/EducationBackground.vue';
import Eligibility from '@/pages/MyDetails/Eligibility.vue';
import FamilyBackground from '@/pages/MyDetails/FamilyBackground.vue';
import OfficialInfo from '@/pages/MyDetails/OfficialInfo.vue';
import Others from '@/pages/MyDetails/Others.vue';
import PersonalInfo from '@/pages/MyDetails/PersonalInfo.vue';
import Training from '@/pages/MyDetails/Training.vue';
import WorkExperience from '@/pages/MyDetails/WorkExperience.vue';

const pageTitle = 'Employee';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employee',
    },
    {
        title: 'Employee Detail',
    },
];

const tabs = [
    'Official Info',
    'Personal Info',
    'Family Background',
    'Education Background',
    'Eligibility',
    'Work Experience',
    'Work Voluntary',
    'Training',
    'Others',
];

const sectionComponents = [
    OfficialInfo,
    PersonalInfo,
    FamilyBackground,
    EducationBackground,
    Eligibility,
    WorkExperience,
    Affiliation,
    Training,
    Others,
] as const;

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

// Sidebar context may be unavailable during certain render timings.
let sidebarContext: ReturnType<typeof useSidebar> | null = null;
try {
    sidebarContext = useSidebar();
} catch (error) {
    console.debug('[MyDetails] Sidebar context not available');
}

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

const currentHrid = computed<number | null>(() => {
    const rawHrid = props.profile?.hrId ?? props.officialInfo?.hrid ?? authUser.value?.hrId;
    const parsedHrid = Number(rawHrid);

    return Number.isFinite(parsedHrid) ? parsedHrid : null;
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

const avatarSrc = computed(() => {
    const avatar = props.profile?.avatar ?? authUser.value?.avatar;
    if (typeof avatar !== 'string') return null;

    const s = avatar.trim();
    if (s === '') return null;

    const cleaned = s.split('?')[0]?.split('#')[0] ?? '';
    const normalizedName = cleaned.split('/').pop()?.toLowerCase() ?? '';
    if (normalizedName === 'avatar-default.jpg') return null;

    if (/^(https?:)?\/\//i.test(s) || s.startsWith('/') || s.startsWith('data:') || s.startsWith('blob:')) {
        return s;
    }
    return `/${s}`;
});

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

function sectionProps(index: number): Record<string, unknown> {
    switch (index) {
        case 0:
            return { officialInfo: props.officialInfo };
        case 1:
            return {
                personalInfo: props.personalInfo,
                officialInfo: props.officialInfo,
                contactInfo: props.contactInfo,
                profile: props.profile,
            };
        case 2:
            return { family: props.family };
        case 3:
            return { education: props.education };
        case 4:
            return { eligibility: props.eligibility };
        case 5:
            return { workExperience: props.workExperience };
        case 6:
            return { affiliation: props.affiliation };
        case 7:
            return { training: props.training };
        case 8:
            return {
                serviceRecord: props.serviceRecord,
                leaveHistory: props.leaveHistory,
                documents: props.documents,
                awards: props.awards,
                performance: props.performance,
                researches: props.researches,
                expertise: props.expertise,
            };
        default:
            return {};
    }
}
const myDetailsReloadProps = [
    'profile',
    'officialInfo',
    'personalInfo',
    'contactInfo',
    'family',
    'education',
    'workExperience',
    'eligibility',
    'serviceRecord',
    'leaveHistory',
    'documents',
    'training',
    'awards',
    'performance',
    'researches',
    'expertise',
    'affiliation',
];

const refreshMyDetails = () => {
    router.reload({
        only: myDetailsReloadProps,
    });
};

const exportPdsExcel = () => {
    window.location.href = '/my-details/pds-export';
};

const onMyDetailsUpdated = (event: { hrid?: number | string } = {}) => {
    const updatedHrid = Number(event.hrid);
    if (!Number.isFinite(updatedHrid) || currentHrid.value === null || updatedHrid !== currentHrid.value) {
        return;
    }

    console.info('[MyDetails] MyDetailsUpdated received. Refreshing details.');
    refreshMyDetails();
};

let isRealtimeBound = false;

onMounted(() => {
    // Close mobile sidebar if open to prevent overlay from blocking clicks.
    if (sidebarContext && sidebarContext.isMobile.value) {
        sidebarContext.setOpenMobile(false);
        setTimeout(() => {
            if (sidebarContext && sidebarContext.isMobile.value && sidebarContext.openMobile.value) {
                sidebarContext.setOpenMobile(false);
            }
        }, 200);
    }

    try {
        const realtime = echo();

        if (realtime && typeof realtime.channel === 'function') {
            realtime.channel('my-details').listen('.MyDetailsUpdated', onMyDetailsUpdated);
            isRealtimeBound = true;
        }
    } catch (error) {
        console.warn('[MyDetails] Realtime channel unavailable:', error);
    }
});

onBeforeUnmount(() => {
    if (!isRealtimeBound) {
        return;
    }

    try {
        echo().leave('my-details');
    } catch (error) {
        console.warn('[MyDetails] Failed to leave realtime channel:', error);
    }
});
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

            <div class="flex items-center justify-between gap-3">
                <h2 class="ehris-page-title mb-0!">{{ tabs[activeTab] }}</h2>
                <button type="button" class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90" @click="exportPdsExcel">
                    <Download class="h-4 w-4" />
                    Export PDS Excel
                </button>
            </div>

            <component
                :is="sectionComponents[activeTab]"
                v-bind="sectionProps(activeTab)"
            />
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
