<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { echo } from '@laravel/echo-vue';
import { Download, User } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { toast } from 'vue3-toastify';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useSidebar } from '@/components/ui/sidebar/utils';
import AppLayout from '@/layouts/AppLayout.vue';
import EducationBackground from '@/pages/MyDetails/EducationBackground.vue';
import Eligibility from '@/pages/MyDetails/Eligibility.vue';
import FamilyBackground from '@/pages/MyDetails/FamilyBackground.vue';
import OfficialInfo from '@/pages/MyDetails/OfficialInfo.vue';
import Others from '@/pages/MyDetails/Others.vue';
import PersonalInfo from '@/pages/MyDetails/PersonalInfo.vue';
import Training from '@/pages/MyDetails/Training.vue';
import VoluntaryWork from '@/pages/MyDetails/VoluntaryWork.vue';
import WorkExperience from '@/pages/MyDetails/WorkExperience.vue';
import type { BreadcrumbItem } from '@/types';
import { formatPhilippineMobile } from '@/utils/phPhone';

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
    'Voluntary Work',
    'Training',
    'Others',
];

const sectionSlugs = [
    'official-info',
    'personal-info',
    'family-background',
    'education-background',
    'eligibility',
    'work-experience',
    'voluntary-work',
    'training',
    'others',
];

const sectionComponents = [
    OfficialInfo,
    PersonalInfo,
    FamilyBackground,
    EducationBackground,
    Eligibility,
    WorkExperience,
    VoluntaryWork,
    Training,
    Others,
] as const;

const page = usePage();
function getTabIndexFromUrl(url: string): number {
    try {
        const search = new URL(url, window.location.origin).searchParams.get('section');
        if (!search) return 0;
        const idx = sectionSlugs.indexOf(search);
        return idx >= 0 ? idx : 0;
    } catch {
        return 0;
    }
}
const activeTab = ref(getTabIndexFromUrl(page.url));
const avatarImageError = ref(false);

const exportModalOpen = ref(false);
const exportIncludePhoto = ref(true);
const exportIncludeSignature = ref(true);
const layoutHeaderHeight = ref(168);
let headerResizeObserver: ResizeObserver | null = null;

const updateLayoutHeaderHeight = () => {
    const headerShell = document.querySelector('.ehris-header-shell');
    if (!(headerShell instanceof HTMLElement)) {
        return;
    }

    layoutHeaderHeight.value = Math.ceil(headerShell.getBoundingClientRect().height);
};

function setActiveTab(index: number): void {
    activeTab.value = index;
    const slug = sectionSlugs[index];
    const url = slug ? `/my-details?section=${slug}` : '/my-details';
    window.history.replaceState(window.history.state, '', url);
}

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
    familyUpdateUrl?: string;
    education?: Record<string, unknown>[];
    educationUpdateUrl?: string;
    officialUpdateUrl?: string;
    personalUpdateUrl?: string;
    canEditOfficialInfo?: boolean;
    workExperience?: Record<string, unknown>[];
    eligibility?: Record<string, unknown>[];
    serviceRecord?: Record<string, unknown>[];
    leaveHistory?: Record<string, unknown>[];
    documents?: Record<string, unknown>[];
    voluntaryWork?: Record<string, unknown>[];
    training?: Record<string, unknown>[];
    awards?: Record<string, unknown>[];
    performance?: Record<string, unknown>[];
    researches?: Record<string, unknown>[];
    expertise?: Record<string, unknown>[];
    affiliation?: Record<string, unknown>[];
    officialOptions?: {
        salaryGrades?: string[];
        steps?: string[];
        positions?: string[];
        departments?: string[];
        divisionOffices?: string[];
        roles?: string[];
        employmentStatuses?: string[];
    };
}>();

const authUser = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
const lastToastedSuccess = ref<string | null>(null);
const lastToastedError = ref<string | null>(null);

watch(
    () => page.url,
    (url) => {
        activeTab.value = getTabIndexFromUrl(url);
    },
);

watch(
    () => flash.value?.success,
    (message) => {
        const msg = typeof message === 'string' ? message.trim() : '';
        if (!msg || msg === lastToastedSuccess.value) return;
        lastToastedSuccess.value = msg;
        toast.success(msg);
    },
    { immediate: true },
);

watch(
    () => flash.value?.error,
    (message) => {
        const msg = typeof message === 'string' ? message.trim() : '';
        if (!msg || msg === lastToastedError.value) return;
        lastToastedError.value = msg;
        toast.error(msg);
    },
    { immediate: true },
);
const canEditOfficialRole = computed(() => {
    const roleRaw = (authUser.value?.role ?? props.profile?.role ?? '').toString().trim();
    if (!roleRaw) return false;
    const role = roleRaw.toLowerCase();
    return /\bhr\b/i.test(roleRaw) || role.includes('human resources');
});

// Sidebar context may be unavailable during certain render timings.
let sidebarContext: ReturnType<typeof useSidebar> | null = null;
try {
    sidebarContext = useSidebar();
} catch {
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
    const c = props.contactInfo;
    const email = c?.email != null ? String(c.email).trim() : '';
    return email !== '' ? email : 'N/A';
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
    if (c?.mobile_num != null && String(c.mobile_num).trim() !== '') return formatPhilippineMobile(c.mobile_num);
    const o = props.officialInfo;
    if (o?.mobile_number != null && String(o.mobile_number).trim() !== '') return formatPhilippineMobile(o.mobile_number);
    return 'N/A';
});

function sectionProps(index: number): Record<string, unknown> {
    switch (index) {
        case 0:
            return {
                officialInfo: props.officialInfo,
                officialUpdateUrl: props.officialUpdateUrl,
                canEditOfficialRole: canEditOfficialRole.value,
                canEditOfficialInfo: props.canEditOfficialInfo,
                officialOptions: props.officialOptions,
            };
        case 1:
            return {
                personalInfo: props.personalInfo,
                officialInfo: props.officialInfo,
                contactInfo: props.contactInfo,
                profile: props.profile,
                personalUpdateUrl: props.personalUpdateUrl,
            };
        case 2:
            return { family: props.family, familyUpdateUrl: props.familyUpdateUrl };
        case 3:
            return { education: props.education, educationUpdateUrl: props.educationUpdateUrl };
        case 4:
            return { eligibility: props.eligibility };
        case 5:
            return { workExperience: props.workExperience };
        case 6:
            return { voluntaryWork: props.voluntaryWork };
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
                affiliation: props.affiliation,
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
    'voluntaryWork',
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
    exportIncludePhoto.value = true;
    exportIncludeSignature.value = true;
    exportModalOpen.value = true;
};

const confirmExportPdsExcel = () => {
    const params = new URLSearchParams();
    params.set('includePhoto', exportIncludePhoto.value ? '1' : '0');
    params.set('includeSignature', exportIncludeSignature.value ? '1' : '0');

    toast.info('Preparing PDS export...', { autoClose: 1500 });
    exportModalOpen.value = false;
    window.location.href = `/my-details/pds-export?${params.toString()}`;
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
    updateLayoutHeaderHeight();
    window.addEventListener('resize', updateLayoutHeaderHeight);

    const headerShell = document.querySelector('.ehris-header-shell');
    if (headerShell instanceof HTMLElement && typeof ResizeObserver !== 'undefined') {
        headerResizeObserver = new ResizeObserver(() => {
            updateLayoutHeaderHeight();
        });
        headerResizeObserver.observe(headerShell);
    }

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
    window.removeEventListener('resize', updateLayoutHeaderHeight);
    if (headerResizeObserver) {
        headerResizeObserver.disconnect();
        headerResizeObserver = null;
    }

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
        <div class="ehris-page ehris-mydetails-shell" :style="{ '--ehris-header-height': `${layoutHeaderHeight}px` }">
            <section class="ehris-tabs" aria-label="Employee detail sections">
                <button
                    v-for="(tab, index) in tabs"
                    :key="tab"
                    type="button"
                    class="ehris-tab"
                    :class="{ 'is-active': activeTab === index }"
                    @click="setActiveTab(index)"
                >
                    {{ tab }}
                </button>
            </section>

            <div class="ehris-mydetails-scroll-area">
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

            <Dialog :open="exportModalOpen" @update:open="(v) => { exportModalOpen = v; }">
                <DialogContent :show-close-button="true" class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Export PDS Excel</DialogTitle>
                    </DialogHeader>

                    <div class="space-y-3">
                        <p class="text-sm text-muted-foreground">
                            Choose what to include in the exported file.
                        </p>

                        <label class="flex items-start gap-3 rounded-md border p-3">
                            <input
                                v-model="exportIncludePhoto"
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-input text-primary focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            >
                            <span class="space-y-0.5">
                                <span class="block text-sm font-medium">Include photo</span>
                                <span class="block text-xs text-muted-foreground">Embeds your passport photo in the PDS.</span>
                            </span>
                        </label>

                        <label class="flex items-start gap-3 rounded-md border p-3">
                            <input
                                v-model="exportIncludeSignature"
                                type="checkbox"
                                class="mt-0.5 size-4 rounded border-input text-primary focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                            >
                            <span class="space-y-0.5">
                                <span class="block text-sm font-medium">Include eSignature</span>
                                <span class="block text-xs text-muted-foreground">Embeds your signature in the PDS.</span>
                            </span>
                        </label>
                    </div>

                    <DialogFooter class="mt-4">
                        <DialogClose as-child>
                            <Button type="button" variant="ghost">Cancel</Button>
                        </DialogClose>
                        <Button type="button" @click="confirmExportPdsExcel">Export</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

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

.ehris-tabs {
    min-height: 3rem;
    padding-bottom: 0.5rem;
    gap: 0.5rem;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}

.ehris-tabs::-webkit-scrollbar:vertical {
    width: 0;
    height: 0;
}

.ehris-mydetails-shell {
    min-height: 0;
    height: auto;
}

.ehris-mydetails-scroll-area {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

@media (max-width: 768px) {
    .ehris-mydetails-shell {
        height: calc(100dvh - var(--ehris-header-height, 168px));
        overflow: hidden;
    }

    .ehris-mydetails-scroll-area {
        min-height: 0;
        overflow-y: auto;
        padding-right: 0.125rem;
        -webkit-overflow-scrolling: touch;
    }
}
</style>
