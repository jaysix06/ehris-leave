<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Upload } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue3-toastify';
import { useAvatarSrc } from '@/composables/useAvatarSrc';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'Request ID';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Self-Service' },
    { title: pageTitle },
];

// Default ID photo when user has no custom avatar
const defaultIdPhoto = '/storage/avatars/avatar-default.jpg';
const defaultIdPhotoPlaceholder = `data:image/svg+xml;utf8,${encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 240 300"><rect width="240" height="300" fill="#f1f5f9"/><circle cx="120" cy="104" r="46" fill="#cbd5e1"/><rect x="46" y="168" width="148" height="102" rx="51" fill="#cbd5e1"/><text x="120" y="290" text-anchor="middle" font-family="Arial,sans-serif" font-size="16" fill="#64748b">No Photo</text></svg>',
)}`;

// Local previews for uploaded files (before or instead of server save)
const idPhotoPreview = ref<string | null>(null);
const signaturePreview = ref<string | null>(null);
const idPhotoFile = ref<File | null>(null);
const signatureFile = ref<File | null>(null);
const idPhotoInput = ref<HTMLInputElement | null>(null);
const signatureInput = ref<HTMLInputElement | null>(null);
const PRINT_DPI = 300;
const PASSPORT_PHOTO_WIDTH_IN = 2;
const PASSPORT_PHOTO_HEIGHT_IN = 2;
const SIGNATURE_PLACEHOLDER_WIDTH_PX = 160;
const SIGNATURE_PLACEHOLDER_HEIGHT_PX = 64;

function readFileAsDataUrl(file: File): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result as string);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

function loadImageFromFile(file: File): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const image = new Image();
        const objectUrl = URL.createObjectURL(file);

        image.onload = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(image);
        };
        image.onerror = (error) => {
            URL.revokeObjectURL(objectUrl);
            reject(error);
        };
        image.src = objectUrl;
    });
}

function getCenterCropRect(sourceWidth: number, sourceHeight: number, targetAspectRatio: number) {
    const sourceAspectRatio = sourceWidth / sourceHeight;

    if (sourceAspectRatio > targetAspectRatio) {
        const croppedWidth = sourceHeight * targetAspectRatio;
        return {
            sx: (sourceWidth - croppedWidth) / 2,
            sy: 0,
            sw: croppedWidth,
            sh: sourceHeight,
        };
    }

    const croppedHeight = sourceWidth / targetAspectRatio;
    return {
        sx: 0,
        sy: (sourceHeight - croppedHeight) / 2,
        sw: sourceWidth,
        sh: croppedHeight,
    };
}

async function cropImageFileToPrintSize(file: File, outputWidthInches: number, outputHeightInches: number): Promise<File> {
    const image = await loadImageFromFile(file);
    const outputWidthPx = Math.round(outputWidthInches * PRINT_DPI);
    const outputHeightPx = Math.round(outputHeightInches * PRINT_DPI);
    const targetAspectRatio = outputWidthPx / outputHeightPx;
    const { sx, sy, sw, sh } = getCenterCropRect(image.width, image.height, targetAspectRatio);
    const canvas = document.createElement('canvas');

    canvas.width = outputWidthPx;
    canvas.height = outputHeightPx;

    const context = canvas.getContext('2d');
    if (!context) {
        throw new Error('Unable to initialize image cropper.');
    }

    context.drawImage(image, sx, sy, sw, sh, 0, 0, outputWidthPx, outputHeightPx);

    const blob = await new Promise<Blob>((resolve, reject) => {
        canvas.toBlob(
            (result) => {
                if (!result) {
                    reject(new Error('Unable to generate cropped image.'));
                    return;
                }

                resolve(result);
            },
            file.type && file.type.startsWith('image/') ? file.type : 'image/jpeg',
            0.92,
        );
    });

    return new File([blob], file.name, { type: blob.type || file.type, lastModified: Date.now() });
}

async function cropImageFileToFixedSizePx(file: File, outputWidthPx: number, outputHeightPx: number): Promise<File> {
    const image = await loadImageFromFile(file);
    const targetAspectRatio = outputWidthPx / outputHeightPx;
    const { sx, sy, sw, sh } = getCenterCropRect(image.width, image.height, targetAspectRatio);
    const canvas = document.createElement('canvas');

    canvas.width = outputWidthPx;
    canvas.height = outputHeightPx;

    const context = canvas.getContext('2d');
    if (!context) {
        throw new Error('Unable to initialize image cropper.');
    }

    context.drawImage(image, sx, sy, sw, sh, 0, 0, outputWidthPx, outputHeightPx);

    const blob = await new Promise<Blob>((resolve, reject) => {
        canvas.toBlob(
            (result) => {
                if (!result) {
                    reject(new Error('Unable to generate cropped image.'));
                    return;
                }

                resolve(result);
            },
            file.type && file.type.startsWith('image/') ? file.type : 'image/jpeg',
            0.92,
        );
    });

    return new File([blob], file.name, { type: blob.type || file.type, lastModified: Date.now() });
}

async function onIdPhotoChange(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file || !file.type.startsWith('image/')) {
        return;
    }

    try {
        const croppedFile = await cropImageFileToPrintSize(file, PASSPORT_PHOTO_WIDTH_IN, PASSPORT_PHOTO_HEIGHT_IN);
        idPhotoFile.value = croppedFile;
        idPhotoPreview.value = await readFileAsDataUrl(croppedFile);
    } catch {
        idPhotoFile.value = file;
        idPhotoPreview.value = await readFileAsDataUrl(file);
    }

    if (errors.value.id_photo) {
        delete errors.value.id_photo;
    }

    input.value = '';
}

async function onSignatureChange(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file || !file.type.startsWith('image/')) {
        return;
    }

    try {
        const croppedFile = await cropImageFileToFixedSizePx(file, SIGNATURE_PLACEHOLDER_WIDTH_PX, SIGNATURE_PLACEHOLDER_HEIGHT_PX);
        signatureFile.value = croppedFile;
        signaturePreview.value = await readFileAsDataUrl(croppedFile);
    } catch {
        signatureFile.value = file;
        signaturePreview.value = await readFileAsDataUrl(file);
    }

    if (errors.value.signature) {
        delete errors.value.signature;
    }

    input.value = '';
}

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

/** Philippine mobile: 11 digits, display as 09XX-XXX-XXXX */
const MOBILE_DIGITS_MAX = 11;
function formatPhilippineMobile(raw: string): string {
    const digits = raw.replace(/\D/g, '').slice(0, MOBILE_DIGITS_MAX);
    if (digits.length <= 4) return digits;
    if (digits.length <= 7) return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    return `${digits.slice(0, 4)}-${digits.slice(4, 7)}-${digits.slice(7)}`;
}

/** TIN: up to 12 digits, display as XXX-XXX-XXX-XXX */
const TIN_DIGITS_MAX = 12;
function formatTin(raw: string): string {
    const digits = raw.replace(/\D/g, '').slice(0, TIN_DIGITS_MAX);
    const parts: string[] = [];
    for (let i = 0; i < digits.length; i += 3) {
        parts.push(digits.slice(i, i + 3));
    }
    return parts.join('-');
}

/** GSIS / PAG-IBIG / PHILHEALTH: 12 digits, display as XXXX-XXXX-XXXX */
const ID12_DIGITS_MAX = 12;
function formatId12(raw: string): string {
    const digits = raw.replace(/\D/g, '').slice(0, ID12_DIGITS_MAX);
    if (digits.length <= 4) return digits;
    if (digits.length <= 8) return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    return `${digits.slice(0, 4)}-${digits.slice(4, 8)}-${digits.slice(8)}`;
}

const BLOOD_TYPE_OPTIONS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as const;

const props = defineProps<{
    profile?: Record<string, unknown> | null;
    officialInfo?: Record<string, unknown> | null;
    personalInfo?: Record<string, unknown> | null;
    contactInfo?: Record<string, unknown> | null;
    templates?: string[];
    templateBaseUrl?: string;
    signaturePath?: string | null;
    cardOptions?: Array<{
        value?: string | null;
        label?: string | null;
        description?: string | null;
        sampleImage?: string | null;
    }>;
    selectedCardOption?: string | null;
}>();

const resolvedProfileAvatarUrl = useAvatarSrc(() => props.profile?.avatar as string | null | undefined);

const idPhotoSrc = computed((): string => {
    if (idPhotoPreview.value) return idPhotoPreview.value;
    const url = resolvedProfileAvatarUrl.value;
    return url ?? defaultIdPhoto;
});

function onIdPhotoError(event: Event): void {
    const image = event.currentTarget as HTMLImageElement | null;
    if (!image) {
        return;
    }

    if (image.dataset.placeholderApplied === '1') {
        return;
    }

    image.dataset.placeholderApplied = '1';
    image.src = defaultIdPhotoPlaceholder;
}

const signatureSrc = computed((): string | null => {
    if (signaturePreview.value) return signaturePreview.value;

    const raw = props.signaturePath;
    if (typeof raw !== 'string') return null;
    const s = raw.trim();
    if (s === '') return null;
    if (/^(https?:)?\/\//i.test(s) || s.startsWith('/') || s.startsWith('data:') || s.startsWith('blob:')) {
        return s;
    }
    return `/${s}`;
});

type CardOptionValue = 'pocket_id' | 'eodb_id_bb';

type CardOption = {
    value: CardOptionValue;
    label: string;
    description: string;
    sampleImage: string;
};

const fallbackCardOptions: CardOption[] = [
    {
        value: 'pocket_id',
        label: 'ID only',
        description: 'Compact layout for pocket-size printing.',
        sampleImage: '/self-service/id-card/sample/pocket_id',
    },
    {
        value: 'eodb_id_bb',
        label: 'EODB ID BB',
        description: 'Standard EODB ID BB layout.',
        sampleImage: '/self-service/id-card/sample/eodb_id_bb',
    },
];

const cardOptions = computed<CardOption[]>(() => {
    const incoming = Array.isArray(props.cardOptions) ? props.cardOptions : [];
    const normalized = incoming
        .map((option) => {
            const optionValue = val(option?.value) as CardOptionValue;
            if (optionValue !== 'pocket_id' && optionValue !== 'eodb_id_bb') {
                return null;
            }

            const fallback = fallbackCardOptions.find((item) => item.value === optionValue);
            return {
                value: optionValue,
                label: val(option?.label) || fallback?.label || optionValue,
                description: val(option?.description) || fallback?.description || '',
                sampleImage: val(option?.sampleImage) || fallback?.sampleImage || '',
            } satisfies CardOption;
        })
        .filter((option): option is CardOption => option !== null);

    if (normalized.length === 0) {
        return fallbackCardOptions;
    }

    const byValue = new Map<CardOptionValue, CardOption>();
    for (const option of normalized) {
        if (!byValue.has(option.value)) {
            byValue.set(option.value, option);
        }
    }

    for (const fallback of fallbackCardOptions) {
        if (!byValue.has(fallback.value)) {
            byValue.set(fallback.value, fallback);
        }
    }

    return [...byValue.values()];
});

const selectedCardOptionValue = ref<CardOptionValue>('eodb_id_bb');
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const page = usePage();
const flash = computed(() => page.props.flash as { success?: string; error?: string } | undefined);
const lastToastedSuccess = ref<string | null>(null);
const lastToastedError = ref<string | null>(null);
const hasRequiredFiles = computed(() => idPhotoFile.value !== null && signatureFile.value !== null);

function syncCardOptionFromProps(): void {
    const selected = val(props.selectedCardOption) as CardOptionValue;
    if (cardOptions.value.some((option) => option.value === selected)) {
        selectedCardOptionValue.value = selected;
        return;
    }

    if (cardOptions.value.some((option) => option.value === 'eodb_id_bb')) {
        selectedCardOptionValue.value = 'eodb_id_bb';
        return;
    }

    selectedCardOptionValue.value = cardOptions.value[0]?.value ?? 'eodb_id_bb';
}

watch(
    () => [props.selectedCardOption, props.cardOptions] as const,
    () => {
        syncCardOptionFromProps();
    },
    { immediate: true },
);

watch(
    () => flash.value?.success,
    (message) => {
        const msg = typeof message === 'string' ? message.trim() : '';
        if (!msg || msg === lastToastedSuccess.value) {
            return;
        }

        lastToastedSuccess.value = msg;
        toast.success(msg);
    },
    { immediate: true },
);

watch(
    () => flash.value?.error,
    (message) => {
        const msg = typeof message === 'string' ? message.trim() : '';
        if (!msg || msg === lastToastedError.value) {
            return;
        }

        lastToastedError.value = msg;
        toast.error(msg);
    },
    { immediate: true },
);

const fullName = computed(() => {
    const o = props.officialInfo;
    const pr = props.profile;
    const nameParts = splitFullName(pr?.fullname);
    const parts = [
        val(o?.prefix_name),
        val(o?.firstname ?? pr?.firstname ?? nameParts.firstname),
        val(o?.middlename ?? pr?.middlename ?? nameParts.middlename),
        val(o?.lastname ?? pr?.lastname ?? nameParts.lastname),
    ].filter((part) => part !== '');
    const ext = val(o?.extension ?? pr?.extname);
    return `${parts.join(' ')}${ext !== '' ? ` ${ext}` : ''}`.trim();
});

const displayEmail = computed(() => val(props.profile?.email ?? props.officialInfo?.email));
const displayRole = computed(() => val(props.profile?.role ?? props.officialInfo?.role));
const displayDeptId = computed(() => val(props.officialInfo?.division_code ?? props.officialInfo?.office));
const displayHrid = computed(() => val(props.officialInfo?.hrid ?? props.profile?.hrId ?? props.profile?.userId));
const displayEmployeeId = computed(() => val(props.officialInfo?.employee_id));
const displayJobTitle = computed(() => val(props.officialInfo?.job_title ?? props.profile?.job_title));
const emergencyContact = ref(formatPhilippineMobile(val(props.contactInfo?.emergency_num)));
const stationNo = ref(val(props.officialInfo?.station_no ?? props.officialInfo?.station_code));
const tin = ref(formatTin(val(props.personalInfo?.tin ?? props.personalInfo?.tin_no)));
const gsis = ref(formatId12(val(props.personalInfo?.gsis)));
const pagIbig = ref(formatId12(val(props.personalInfo?.pag_ibig ?? props.personalInfo?.pagibig_no)));
const philhealth = ref(formatId12(val(props.personalInfo?.philhealth ?? props.personalInfo?.philhealth_no)));
const birthDate = ref(val(props.personalInfo?.dob));
const bloodType = ref(val(props.personalInfo?.blood_type));
const hasRequiredPocketFields = computed(() => {
    if (selectedCardOptionValue.value !== 'pocket_id') {
        return true;
    }

    return [
        emergencyContact.value,
        stationNo.value,
        tin.value,
        gsis.value,
        pagIbig.value,
        philhealth.value,
        birthDate.value,
        bloodType.value,
    ].every((value) => value.trim() !== '');
});
const canSubmitRequest = computed(() => !processing.value && hasRequiredFiles.value && hasRequiredPocketFields.value);

// (Print section removed)

function applyChanges() {
    errors.value = {};

    if (idPhotoFile.value === null || signatureFile.value === null) {
        if (idPhotoFile.value === null) {
            errors.value.id_photo = 'ID photo is required.';
        }
        if (signatureFile.value === null) {
            errors.value.signature = 'Signature is required.';
        }

        toast.error(errors.value.id_photo ?? errors.value.signature ?? 'ID photo and signature are required.');
        return;
    }
    if (selectedCardOptionValue.value === 'pocket_id' && !hasRequiredPocketFields.value) {
        if (emergencyContact.value.trim() === '') errors.value.emergency_contact = 'Emergency contact is required.';
        if (stationNo.value.trim() === '') errors.value.station_no = 'Station number is required.';
        if (tin.value.trim() === '') errors.value.tin = 'TIN is required.';
        if (gsis.value.trim() === '') errors.value.gsis = 'GSIS is required.';
        if (pagIbig.value.trim() === '') errors.value.pag_ibig = 'PAG-IBIG is required.';
        if (philhealth.value.trim() === '') errors.value.philhealth = 'PHILHEALTH is required.';
        if (birthDate.value.trim() === '') errors.value.birth_date = 'Birthdate is required.';
        if (bloodType.value.trim() === '') errors.value.blood_type = 'Blood type is required.';
        toast.error('Please fill in the required ID only details.');
        return;
    }

    processing.value = true;
    router.post('/self-service/id-card/update', {
        _method: 'put',
        card_option: selectedCardOptionValue.value,
        id_photo: idPhotoFile.value ?? undefined,
        signature: signatureFile.value ?? undefined,
        emergency_contact: emergencyContact.value || undefined,
        station_no: stationNo.value || undefined,
        tin: tin.value || undefined,
        gsis: gsis.value || undefined,
        pag_ibig: pagIbig.value || undefined,
        philhealth: philhealth.value || undefined,
        birth_date: birthDate.value || undefined,
        blood_type: bloodType.value || undefined,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            idPhotoFile.value = null;
            signatureFile.value = null;
        },
        onError: (errs) => {
            const formErrors = (errs as Record<string, string>) || {};
            errors.value = formErrors;
            const firstError = Object.values(formErrors)[0];
            if (typeof firstError === 'string' && firstError.trim() !== '') {
                toast.error(firstError);
                return;
            }

            toast.error('Unable to submit ID request. Please try again.');
        },
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
                                @error="onIdPhotoError"
                            />
                        </div>
                        <span class="text-xs text-muted-foreground">Photo will be auto-resized to 2×2 in. passport size.</span>
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
                        <p v-if="errors.id_photo" class="text-xs text-destructive">{{ errors.id_photo }}</p>
                        <div class="w-40 h-16 rounded border border-sidebar-border/70 bg-muted/30 flex items-center justify-center overflow-hidden">
                            <img
                                v-if="signatureSrc"
                                :src="signatureSrc"
                                alt="Signature"
                                class="max-w-full max-h-full object-contain"
                            />
                            <span v-else class="text-xs text-muted-foreground">Signature</span>
                        </div>
                        <span class="text-xs text-muted-foreground">Signature will be auto-resized to fit.</span>
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
                        <p v-if="errors.signature" class="text-xs text-destructive">{{ errors.signature }}</p>
                        <div class="text-center">
                            <p class="font-semibold text-lg">{{ fullName || '—' }}</p>
                            <p class="text-sm text-muted-foreground">Email: {{ displayEmail || '—' }}</p>
                            <p class="text-sm text-muted-foreground">Role: {{ displayRole || '—' }}</p>
                            <p class="text-sm text-muted-foreground">Department ID: {{ displayDeptId || '—' }}</p>
                            <p class="text-sm text-muted-foreground">HR ID: {{ displayHrid || '—' }}</p>
                        </div>
                    </div>

                    <!-- Right: ID only required info, card option, request action -->
                    <div class="lg:col-span-2">
                        <h2 class="mb-2 text-lg font-medium">ID Card Request</h2>

                        <div class="mb-4 rounded-md border border-sidebar-border/70 bg-muted/20 p-3">
                            <p class="mb-2 text-sm font-semibold text-foreground">ID only Required Information</p>
                            <p class="mb-3 text-xs text-muted-foreground">In case of emergency, please contact (11 digits, e.g. 0945-330-7325):</p>
                            <input
                                :value="emergencyContact"
                                type="text"
                                inputmode="numeric"
                                maxlength="14"
                                placeholder="0945-330-7325"
                                class="mb-2 w-full rounded border border-input bg-background px-3 py-2 text-sm"
                                @input="emergencyContact = formatPhilippineMobile(($event.target as HTMLInputElement).value)"
                            >
                            <p v-if="errors.emergency_contact" class="mb-2 text-xs text-destructive">{{ errors.emergency_contact }}</p>

                            <p class="mb-2 text-xs font-semibold text-muted-foreground uppercase">Other Information</p>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs text-muted-foreground">STATION NO.</label>
                                    <input
                                        v-model="stationNo"
                                        type="text"
                                        placeholder="ST-01"
                                        class="w-full rounded border border-input bg-background px-3 py-2 text-sm"
                                    >
                                    <p v-if="errors.station_no" class="mt-1 text-xs text-destructive">{{ errors.station_no }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs text-muted-foreground">TIN</label>
                                    <input
                                        :value="tin"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="15"
                                        placeholder="123-456-789-000"
                                        class="w-full rounded border border-input bg-background px-3 py-2 text-sm"
                                        @input="tin = formatTin(($event.target as HTMLInputElement).value)"
                                    >
                                    <p v-if="errors.tin" class="mt-1 text-xs text-destructive">{{ errors.tin }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs text-muted-foreground">GSIS</label>
                                    <input
                                        :value="gsis"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="14"
                                        placeholder="1234-567890-12"
                                        class="w-full rounded border border-input bg-background px-3 py-2 text-sm"
                                        @input="gsis = formatId12(($event.target as HTMLInputElement).value)"
                                    >
                                    <p v-if="errors.gsis" class="mt-1 text-xs text-destructive">{{ errors.gsis }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs text-muted-foreground">PAG-IBIG</label>
                                    <input
                                        :value="pagIbig"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="14"
                                        placeholder="1234-5678-9012"
                                        class="w-full rounded border border-input bg-background px-3 py-2 text-sm"
                                        @input="pagIbig = formatId12(($event.target as HTMLInputElement).value)"
                                    >
                                    <p v-if="errors.pag_ibig" class="mt-1 text-xs text-destructive">{{ errors.pag_ibig }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs text-muted-foreground">PHILHEALTH</label>
                                    <input
                                        :value="philhealth"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="14"
                                        placeholder="1234-5678-9012"
                                        class="w-full rounded border border-input bg-background px-3 py-2 text-sm"
                                        @input="philhealth = formatId12(($event.target as HTMLInputElement).value)"
                                    >
                                    <p v-if="errors.philhealth" class="mt-1 text-xs text-destructive">{{ errors.philhealth }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs text-muted-foreground">BIRTHDATE</label>
                                    <input
                                        v-model="birthDate"
                                        type="date"
                                        class="w-full rounded border border-input bg-background px-3 py-2 text-sm"
                                    >
                                    <p v-if="errors.birth_date" class="mt-1 text-xs text-destructive">{{ errors.birth_date }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs text-muted-foreground">BLOOD TYPE</label>
                                    <select
                                        v-model="bloodType"
                                        class="w-full rounded border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">Select blood type</option>
                                        <option
                                            v-for="bt in BLOOD_TYPE_OPTIONS"
                                            :key="bt"
                                            :value="bt"
                                        >
                                            {{ bt }}
                                        </option>
                                    </select>
                                    <p v-if="errors.blood_type" class="mt-1 text-xs text-destructive">{{ errors.blood_type }}</p>
                                </div>
                            </div>
                        </div>

                        <form class="space-y-4" @submit.prevent="applyChanges">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                <label
                                    v-for="option in cardOptions"
                                    :key="option.value"
                                    class="flex h-full cursor-pointer items-start gap-3 rounded-md border p-3 transition"
                                    :class="selectedCardOptionValue === option.value ? 'border-primary bg-primary/5' : 'border-input bg-background hover:bg-muted/20'"
                                >
                                    <input
                                        v-model="selectedCardOptionValue"
                                        type="radio"
                                        name="card_option"
                                        :value="option.value"
                                        class="mt-0.5"
                                    >
                                    <div>
                                        <img
                                            :src="option.sampleImage"
                                            :alt="`${option.label} sample preview`"
                                            class="mb-2 h-32 w-full max-w-[280px] rounded border border-border bg-white object-contain"
                                        >
                                        <p class="text-sm font-semibold text-foreground">{{ option.label }}</p>
                                        <p class="text-xs text-muted-foreground">{{ option.description }}</p>
                                    </div>
                                </label>
                            </div>

                            <p v-if="errors.card_option" class="text-sm text-destructive">{{ errors.card_option }}</p>
                            <p v-if="errors.message" class="text-sm text-destructive">{{ errors.message }}</p>

                            <div class="flex justify-end gap-3">
                                <button
                                    type="button"
                                    class="rounded border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-muted/50"
                                    :disabled="processing"
                                    @click="syncCardOptionFromProps"
                                >
                                    Reset
                                </button>
                                <button
                                    type="submit"
                                    :disabled="!canSubmitRequest"
                                    class="rounded bg-destructive px-4 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90 disabled:pointer-events-none disabled:opacity-50"
                                >
                                    {{ processing ? 'Submitting...' : 'Submit Request' }}
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
