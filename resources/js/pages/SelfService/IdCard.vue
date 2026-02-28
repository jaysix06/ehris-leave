<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Upload } from 'lucide-vue-next';

const pageTitle = 'Request ID';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Self-Service' },
    { title: pageTitle },
];

// Default ID photo when user has no photo (neutral silhouette)
const defaultIdPhotoSvg =
    'data:image/svg+xml,' +
    encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%239ca3af"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>'
    );

// Local previews for uploaded files (before or instead of server save)
const idPhotoPreview = ref<string | null>(null);
const signaturePreview = ref<string | null>(null);
const idPhotoInput = ref<HTMLInputElement | null>(null);
const signatureInput = ref<HTMLInputElement | null>(null);

function readFileAsDataUrl(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result as string);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

function onIdPhotoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file || !file.type.startsWith('image/')) return;
    readFileAsDataUrl(file).then((url) => {
        idPhotoPreview.value = url;
    });
    input.value = '';
}

function onSignatureChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file || !file.type.startsWith('image/')) return;
    readFileAsDataUrl(file).then((url) => {
        signaturePreview.value = url;
    });
    input.value = '';
}

const idPhotoSrc = computed((): string => {
    if (idPhotoPreview.value) return idPhotoPreview.value;

    const avatar = props.profile?.avatar;
    if (typeof avatar === 'string') {
        const s = avatar.trim();
        // Treat the system default filename as "no photo yet"
        if (s !== '' && s !== 'avatar-default.jpg' && s !== '/avatar-default.jpg') {
            return s;
        }
    }

    return defaultIdPhotoSvg;
});

function splitFullName(fullname: unknown): { firstname: string; middlename: string; lastname: string } {
    const s = typeof fullname === 'string' ? fullname.trim() : '';
    if (!s) return { firstname: '', middlename: '', lastname: '' };
    const parts = s.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return { firstname: parts[0], middlename: '', lastname: '' };
    if (parts.length === 2) return { firstname: parts[0], middlename: '', lastname: parts[1] };
    return { firstname: parts[0], middlename: parts.slice(1, -1).join(' '), lastname: parts[parts.length - 1] };
}

function val(v: unknown): string {
    if (v == null || v === '') return '';
    return String(v).trim();
}

const props = defineProps<{
    profile?: Record<string, unknown> | null;
    officialInfo?: Record<string, unknown> | null;
    personalInfo?: Record<string, unknown> | null;
    contactInfo?: Record<string, unknown> | null;
    templates?: string[];
    templateBaseUrl?: string;
}>();

// Form state for User Details (synced from props, can be edited)
const form = ref({
    hrid: '',
    employee_id: '',
    prefix_name: '',
    firstname: '',
    middlename: '',
    lastname: '',
    extension: '',
    birth_date: '',
    prc_no: '',
    tin: '',
    gsis: '',
    gsis_bp: '',
    pag_ibig: '',
    philhealth: '',
    blood_type: '',
    job_title: '',
    emergency_name: '',
    emergency_contact: '',
    emergency_email: '',
});

function syncFormFromProps() {
    const o = props.officialInfo;
    const p = props.personalInfo;
    const c = props.contactInfo;
    const pr = props.profile;
    const nameParts = splitFullName(pr?.fullname);
    form.value = {
        hrid: val(o?.hrid ?? pr?.hrId ?? pr?.userId),
        employee_id: val(o?.employee_id),
        prefix_name: val(o?.prefix_name),
        firstname: val(o?.firstname ?? pr?.firstname ?? nameParts.firstname),
        middlename: val(o?.middlename ?? pr?.middlename ?? nameParts.middlename),
        lastname: val(o?.lastname ?? pr?.lastname ?? nameParts.lastname),
        extension: val(o?.extension ?? pr?.extname),
        birth_date: val(p?.dob),
        prc_no: val(p?.prc_no),
        tin: val(p?.tin),
        gsis: val(p?.gsis),
        gsis_bp: val(p?.gsis_bp),
        pag_ibig: val(p?.pag_ibig),
        philhealth: val(p?.philhealth),
        blood_type: val(p?.blood_type),
        job_title: val(o?.job_title ?? pr?.job_title),
        emergency_name: val(c?.emergency_name ?? c?.emergency_contact_name),
        emergency_contact: val(c?.emergency_num ?? c?.emergency_phone ?? c?.emergency_contact_number),
        emergency_email: val(c?.emergency_email),
    };
}

watch(
    () => [props.profile, props.officialInfo, props.personalInfo, props.contactInfo],
    () => syncFormFromProps(),
    { immediate: true }
);

const fullName = computed(() => {
    const f = form.value;
    const parts = [f.prefix_name, f.firstname, f.middlename, f.lastname].filter(Boolean);
    const name = parts.join(' ').trim();
    const ext = f.extension ? ` ${f.extension}` : '';
    return name + ext;
});

const displayEmail = computed(() => val(props.profile?.email ?? props.officialInfo?.email));
const displayRole = computed(() => val(props.profile?.role ?? props.officialInfo?.role));
const displayDeptId = computed(() => val(props.officialInfo?.division_code ?? props.officialInfo?.office));

// (Print section removed)

function applyChanges() {
    router.put('/self-service/id-card/update', {
        hrid: form.value.hrid || undefined,
        employee_id: form.value.employee_id || undefined,
        prefix_name: form.value.prefix_name || undefined,
        firstname: form.value.firstname || undefined,
        middlename: form.value.middlename || undefined,
        lastname: form.value.lastname || undefined,
        extension: form.value.extension || undefined,
        birth_date: form.value.birth_date || undefined,
        prc_no: form.value.prc_no || undefined,
        tin: form.value.tin || undefined,
        gsis: form.value.gsis || undefined,
        gsis_bp: form.value.gsis_bp || undefined,
        pag_ibig: form.value.pag_ibig || undefined,
        philhealth: form.value.philhealth || undefined,
        blood_type: form.value.blood_type || undefined,
        job_title: form.value.job_title || undefined,
        emergency_name: form.value.emergency_name || undefined,
        emergency_contact: form.value.emergency_contact || undefined,
        emergency_email: form.value.emergency_email || undefined,
    });
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 w-full">
            <h1 class="text-2xl font-semibold text-foreground mb-6">Request ID</h1>

            <div class="rounded-lg border border-sidebar-border/70 bg-white shadow-sm overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6">
                    <!-- Left: Photo, signature, read-only info -->
                    <div class="flex flex-col items-center gap-4">
                        <div class="w-32 h-40 rounded border border-sidebar-border/70 bg-muted/30 flex items-center justify-center overflow-hidden">
                            <img
                                :src="idPhotoSrc"
                                alt="ID Photo"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <span class="text-xs text-muted-foreground">Crop. 8x10</span>
                        <input
                            ref="idPhotoInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onIdPhotoChange"
                        />
                        <button
                            type="button"
                            class="rounded border border-input bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted/50 inline-flex items-center gap-2"
                            @click="idPhotoInput?.click()"
                        >
                            <Upload class="size-4" />
                            Choose File
                        </button>
                        <div class="w-40 h-16 rounded border border-sidebar-border/70 bg-muted/30 flex items-center justify-center overflow-hidden">
                            <img
                                v-if="signaturePreview"
                                :src="signaturePreview"
                                alt="Signature"
                                class="max-w-full max-h-full object-contain"
                            />
                            <span v-else class="text-xs text-muted-foreground">Signature</span>
                        </div>
                        <input
                            ref="signatureInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="onSignatureChange"
                        />
                        <button
                            type="button"
                            class="rounded border border-input bg-background px-3 py-1.5 text-sm font-medium hover:bg-muted/50 inline-flex items-center gap-2"
                            @click="signatureInput?.click()"
                        >
                            <Upload class="size-4" />
                            Choose File
                        </button>
                        <div class="text-center">
                            <p class="font-semibold text-lg">{{ fullName || '—' }}</p>
                            <p class="text-sm text-muted-foreground">Email: {{ displayEmail || '—' }}</p>
                            <p class="text-sm text-muted-foreground">Role: {{ displayRole || '—' }}</p>
                            <p class="text-sm text-muted-foreground">Department ID: {{ displayDeptId || '—' }}</p>
                            <p class="text-sm text-muted-foreground">HR ID: {{ form.hrid || '—' }}</p>
                        </div>
                    </div>

                    <!-- Right: User Details form -->
                    <div class="lg:col-span-2">
                        <h2 class="text-lg font-medium mb-4">User Details</h2>
                        <form class="grid grid-cols-1 md:grid-cols-3 gap-4" @submit.prevent="applyChanges">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">HRID</label>
                                <input v-model="form.hrid" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Employee ID</label>
                                <input v-model="form.employee_id" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Prefix Name</label>
                                <input v-model="form.prefix_name" type="text" placeholder="Select Prefix" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">First Name</label>
                                <input v-model="form.firstname" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Middle Name</label>
                                <input v-model="form.middlename" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Last Name</label>
                                <input v-model="form.lastname" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Extension Name</label>
                                <input v-model="form.extension" type="text" placeholder="e.g. Jr., Sr." class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Birth Date</label>
                                <input v-model="form.birth_date" type="text" placeholder="YYYY/MM/DD" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">PRC No.</label>
                                <input v-model="form.prc_no" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">TIN</label>
                                <input v-model="form.tin" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">GSIS No.</label>
                                <input v-model="form.gsis" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Pag IBIG No.</label>
                                <input v-model="form.pag_ibig" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">PhilHealth No.</label>
                                <input v-model="form.philhealth" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Blood Type</label>
                                <input v-model="form.blood_type" type="text" placeholder="e.g. O-" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="text-sm font-medium">Job Title</label>
                                <input v-model="form.job_title" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                            </div>
                            <div class="md:col-span-3 border-t pt-4 mt-2">
                                <p class="text-sm font-medium mb-3">In Case of Emergency</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="space-y-2">
                                        <label class="text-sm">Name</label>
                                        <input v-model="form.emergency_name" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm">Contact No.</label>
                                        <input v-model="form.emergency_contact" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-sm">Email</label>
                                        <input v-model="form.emergency_email" type="text" class="w-full rounded border border-input bg-background px-3 py-2 text-sm" />
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-3 flex gap-3 justify-end">
                                <button
                                    type="button"
                                    class="rounded border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-muted/50"
                                    @click="syncFormFromProps"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="rounded bg-destructive text-destructive-foreground px-4 py-2 text-sm font-medium hover:bg-destructive/90"
                                >
                                    Apply Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.text-shadow-sm {
    text-shadow: 0 0 2px #fff, 0 0 4px #fff;
}
</style>
