<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { digitsOnly, formatPhilippineLandline } from '@/utils/phPhone';

type FamilyRow = Record<string, unknown>;

type SpouseFields = {
    lastname: string;
    firstname: string;
    middlename: string;
    extension: string;
    dob: string;
    occupation: string;
    employer_name: string;
    business_add: string;
    tel_num: string;
    deceased: string;
};

type ParentFields = {
    lastname: string;
    firstname: string;
    middlename: string;
    extension: string;
    deceased: string;
};

type ChildRow = {
    lastname: string;
    firstname: string;
    middlename: string;
    extension: string;
    dob: string;
    deceased: string;
};

function val(v: unknown): string {
    if (v == null || v === '') {
        return '';
    }
    return String(v).trim();
}

function displayVal(v: unknown): string {
    const s = val(v);
    return s === '' ? '—' : s;
}

function displayDate(v: unknown): string {
    const raw = val(v);
    if (raw === '') {
        return '—';
    }

    const dash = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (dash) {
        return `${dash[3]}/${dash[2]}/${dash[1]}`;
    }

    const slash = raw.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
    if (slash) {
        return `${slash[3]}/${slash[2]}/${slash[1]}`;
    }

    return raw;
}

function normalizeDateToIso(v: unknown): string {
    const raw = val(v);
    if (raw === '') {
        return '';
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return raw;
    }

    const ymdSlash = raw.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
    if (ymdSlash) {
        return `${ymdSlash[1]}-${ymdSlash[2]}-${ymdSlash[3]}`;
    }

    const dmySlash = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (dmySlash) {
        return `${dmySlash[3]}-${dmySlash[2]}-${dmySlash[1]}`;
    }

    const dmyDash = raw.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (dmyDash) {
        return `${dmyDash[3]}-${dmyDash[2]}-${dmyDash[1]}`;
    }

    return '';
}

function relationKey(relationship: unknown): 'spouse' | 'child' | 'father' | 'mother' | null {
    const normalized = val(relationship).toLowerCase();
    if (normalized === '') {
        return null;
    }
    if (normalized.includes('spouse') || normalized.includes('wife') || normalized.includes('husband')) {
        return 'spouse';
    }
    if (normalized.includes('child')) {
        return 'child';
    }
    if (normalized.includes('father')) {
        return 'father';
    }
    if (normalized.includes('mother')) {
        return 'mother';
    }
    return null;
}

function fullNameFromRow(row: FamilyRow): string {
    return [row.firstname, row.middlename, row.lastname, row.extension]
        .map((item) => val(item))
        .filter((item) => item !== '')
        .join(' ');
}

function spouseScore(row: FamilyRow): number {
    const fields = [
        row.lastname,
        row.firstname,
        row.middlename,
        row.extension,
        row.occupation,
        row.employer_name,
        row.business_add,
        row.tel_num,
    ];
    return fields.reduce<number>((score, field) => score + (val(field) !== '' ? 1 : 0), 0);
}

const props = defineProps<{
    family?: FamilyRow[];
    familyUpdateUrl?: string;
}>();

const familyRows = computed<FamilyRow[]>(() => props.family ?? []);

const spouseEntry = computed<FamilyRow | null>(() => {
    const spouseRows = familyRows.value.filter((row) => relationKey(row.relationship) === 'spouse');
    const fallbackRows = familyRows.value.filter((row) => relationKey(row.relationship) === null);
    const candidates = [...spouseRows, ...fallbackRows];

    let best: FamilyRow | null = null;
    let bestScore = -1;
    for (const row of candidates) {
        const score = spouseScore(row);
        if (score > bestScore) {
            bestScore = score;
            best = row;
        }
    }

    return bestScore > 0 ? best : null;
});

const childrenEntries = computed<Array<{ fullName: string; dob: string }>>(() => {
    return familyRows.value
        .filter((row) => relationKey(row.relationship) === 'child')
        .map((row) => ({
            fullName: fullNameFromRow(row),
            dob: val(row.dob),
        }));
});

const fatherEntry = computed<FamilyRow | null>(() => {
    return familyRows.value.find((row) => relationKey(row.relationship) === 'father') ?? null;
});

const motherEntry = computed<FamilyRow | null>(() => {
    return familyRows.value.find((row) => relationKey(row.relationship) === 'mother') ?? null;
});

const spouseDetailRows = computed(() => {
    return [
        { label: 'FIRST NAME', value: spouseEntry.value?.firstname },
        { label: 'MIDDLE NAME', value: spouseEntry.value?.middlename },
        { label: 'OCCUPATION', value: spouseEntry.value?.occupation },
        { label: 'EMPLOYER/BUSINESS NAME', value: spouseEntry.value?.employer_name },
        { label: 'BUSINESS ADDRESS', value: spouseEntry.value?.business_add },
        { label: 'TELEPHONE NO.', value: formatPhilippineLandline(spouseEntry.value?.tel_num) },
    ];
});

const spouseAndChildrenRows = computed(() => {
    const maxRows = Math.max(spouseDetailRows.value.length, Math.max(0, childrenEntries.value.length - 1));
    return Array.from({ length: maxRows }, (_, idx) => ({
        spouseLabel: spouseDetailRows.value[idx]?.label ?? '',
        spouseValue: spouseDetailRows.value[idx]?.value ?? '',
        childName: childrenEntries.value[idx + 1]?.fullName ?? '',
        childDob: childrenEntries.value[idx + 1]?.dob ?? '',
    }));
});

const hasFamilyData = computed(() => {
    return Boolean(
        spouseEntry.value
        || childrenEntries.value.length > 0
        || fatherEntry.value
        || motherEntry.value
    );
});

const emptySpouse = (): SpouseFields => ({
    lastname: '',
    firstname: '',
    middlename: '',
    extension: '',
    dob: '',
    occupation: '',
    employer_name: '',
    business_add: '',
    tel_num: '',
    deceased: '',
});

const emptyParent = (): ParentFields => ({
    lastname: '',
    firstname: '',
    middlename: '',
    extension: '',
    deceased: '',
});

const editModalOpen = ref(false);
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const spouseForm = ref<SpouseFields>(emptySpouse());
const fatherForm = ref<ParentFields>(emptyParent());
const motherForm = ref<ParentFields>(emptyParent());
const childrenForm = ref<ChildRow[]>([{ lastname: '', firstname: '', middlename: '', extension: '', dob: '', deceased: '' }]);

const canEdit = computed(() => Boolean(props.familyUpdateUrl));

const nameExtensionOptions = [
    'JR.',
    'SR.',
    'I',
    'II',
    'III',
    'IV',
    'V',
    'VI',
    'VII',
    'VIII',
    'IX',
    'X',
];

function openEdit(): void {
    spouseForm.value = {
        lastname: val(spouseEntry.value?.lastname),
        firstname: val(spouseEntry.value?.firstname),
        middlename: val(spouseEntry.value?.middlename),
        extension: val(spouseEntry.value?.extension),
        dob: normalizeDateToIso(spouseEntry.value?.dob),
        occupation: val(spouseEntry.value?.occupation),
        employer_name: val(spouseEntry.value?.employer_name),
        business_add: val(spouseEntry.value?.business_add),
        tel_num: digitsOnly(spouseEntry.value?.tel_num).slice(0, 10),
        deceased: val(spouseEntry.value?.deceased),
    };
    fatherForm.value = {
        lastname: val(fatherEntry.value?.lastname),
        firstname: val(fatherEntry.value?.firstname),
        middlename: val(fatherEntry.value?.middlename),
        extension: val(fatherEntry.value?.extension),
        deceased: val(fatherEntry.value?.deceased),
    };
    motherForm.value = {
        lastname: val(motherEntry.value?.lastname),
        firstname: val(motherEntry.value?.firstname),
        middlename: val(motherEntry.value?.middlename),
        extension: val(motherEntry.value?.extension),
        deceased: val(motherEntry.value?.deceased),
    };
    const rawChildren = familyRows.value.filter((row) => relationKey(row.relationship) === 'child');
    childrenForm.value = rawChildren.length > 0
        ? rawChildren.map((row) => ({
            lastname: val(row.lastname),
            firstname: val(row.firstname),
            middlename: val(row.middlename),
            extension: val(row.extension),
            dob: normalizeDateToIso(row.dob),
            deceased: val(row.deceased),
        }))
        : [{ lastname: '', firstname: '', middlename: '', extension: '', dob: '', deceased: '' }];
    errors.value = {};
    editModalOpen.value = true;
}

function addChild(): void {
    childrenForm.value = [...childrenForm.value, { lastname: '', firstname: '', middlename: '', extension: '', dob: '', deceased: '' }];
}

function removeChild(index: number): void {
    if (childrenForm.value.length <= 1) return;
    childrenForm.value = childrenForm.value.filter((_, idx) => idx !== index);
}

function buildFamilyPayload(): FamilyRow[] {
    const payload: FamilyRow[] = [];

    const s = spouseForm.value;
    if ([s.lastname, s.firstname, s.middlename, s.extension, s.dob, s.occupation, s.employer_name, s.business_add, s.tel_num, s.deceased].some((item) => val(item) !== '')) {
        payload.push({
            relationship: 'Spouse',
            lastname: val(s.lastname) || null,
            firstname: val(s.firstname) || null,
            middlename: val(s.middlename) || null,
            extension: val(s.extension) || null,
            occupation: val(s.occupation) || null,
            employer_name: val(s.employer_name) || null,
            business_add: val(s.business_add) || null,
            tel_num: val(s.tel_num) || null,
            dob: val(s.dob) || null,
            deceased: val(s.deceased) || null,
        });
    }

    for (const child of childrenForm.value) {
        if (
            val(child.lastname) === ''
            && val(child.firstname) === ''
            && val(child.middlename) === ''
            && val(child.extension) === ''
            && val(child.dob) === ''
            && val(child.deceased) === ''
        ) {
            continue;
        }
        payload.push({
            relationship: 'Child',
            lastname: val(child.lastname) || null,
            firstname: val(child.firstname) || null,
            middlename: val(child.middlename) || null,
            extension: val(child.extension) || null,
            occupation: null,
            employer_name: null,
            business_add: null,
            tel_num: null,
            dob: val(child.dob) || null,
            deceased: val(child.deceased) || null,
        });
    }

    const f = fatherForm.value;
    if ([f.lastname, f.firstname, f.middlename, f.extension, f.deceased].some((item) => val(item) !== '')) {
        payload.push({
            relationship: 'Father',
            lastname: val(f.lastname) || null,
            firstname: val(f.firstname) || null,
            middlename: val(f.middlename) || null,
            extension: val(f.extension) || null,
            occupation: null,
            employer_name: null,
            business_add: null,
            tel_num: null,
            dob: null,
            deceased: val(f.deceased) || null,
        });
    }

    const m = motherForm.value;
    if ([m.lastname, m.firstname, m.middlename, m.extension, m.deceased].some((item) => val(item) !== '')) {
        payload.push({
            relationship: 'Mother',
            lastname: val(m.lastname) || null,
            firstname: val(m.firstname) || null,
            middlename: val(m.middlename) || null,
            extension: val(m.extension) || null,
            occupation: null,
            employer_name: null,
            business_add: null,
            tel_num: null,
            dob: null,
            deceased: val(m.deceased) || null,
        });
    }

    return payload;
}

function submit(): void {
    if (!props.familyUpdateUrl) return;
    processing.value = true;
    errors.value = {};

    router.post(
        props.familyUpdateUrl,
        { family: buildFamilyPayload() } as Parameters<typeof router.post>[1],
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
            onSuccess: () => {
                editModalOpen.value = false;
            },
            onError: (errs) => {
                errors.value = (errs as Record<string, string>) || {};
            },
        },
    );
}
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>Family information</h3>
            <button
                type="button"
                class="ehris-btn-grade-subject"
                aria-label="Edit family information"
                :disabled="!canEdit"
                @click="openEdit"
            >
                <Pencil class="size-4" />
                <span>Edit</span>
            </button>
        </div>

        <template v-if="hasFamilyData">
            <div class="ehris-pds-family-sheet">
                <table class="ehris-pds-family-table">
                    <tbody>
                        <tr class="ehris-pds-head-row">
                            <th colspan="2">
                                <span class="ehris-pds-no">22.</span>
                                <span>SPOUSE'S SURNAME</span>
                            </th>
                            <th>
                                <span class="ehris-pds-no">23.</span>
                                <span>NAME OF CHILDREN (Write full name and list all)</span>
                            </th>
                            <th>DATE OF BIRTH (dd/mm/yyyy)</th>
                        </tr>
                        <tr>
                            <td class="ehris-pds-label">SPOUSE'S SURNAME</td>
                            <td class="ehris-pds-value is-strong">{{ displayVal(spouseEntry?.lastname) }}</td>
                            <td class="ehris-pds-value">{{ displayVal(childrenEntries[0]?.fullName) }}</td>
                            <td class="ehris-pds-value">{{ displayDate(childrenEntries[0]?.dob) }}</td>
                        </tr>
                        <tr v-for="(row, idx) in spouseAndChildrenRows" :key="idx">
                            <td class="ehris-pds-label">{{ row.spouseLabel || ' ' }}</td>
                            <td class="ehris-pds-value">
                                <template v-if="row.spouseLabel === 'FIRST NAME'">
                                    <span class="is-strong">{{ displayVal(row.spouseValue) }}</span>
                                    <span class="ehris-pds-inline-label">NAME EXTENSION (JR., SR.)</span>
                                    <span>{{ displayVal(spouseEntry?.extension || 'N/A') }}</span>
                                </template>
                                <template v-else>{{ displayVal(row.spouseValue) }}</template>
                            </td>
                            <td class="ehris-pds-value">{{ displayVal(row.childName) }}</td>
                            <td class="ehris-pds-value">{{ displayDate(row.childDob) }}</td>
                        </tr>
                        <tr class="ehris-pds-head-row">
                            <th colspan="2">
                                <span class="ehris-pds-no">24.</span>
                                <span>FATHER'S SURNAME</span>
                            </th>
                            <th colspan="2">
                                <span class="ehris-pds-no">25.</span>
                                <span>MOTHER'S MAIDEN NAME</span>
                            </th>
                        </tr>
                        <tr>
                            <td class="ehris-pds-label">FATHER'S SURNAME</td>
                            <td class="ehris-pds-value is-strong">{{ displayVal(fatherEntry?.lastname) }}</td>
                            <td class="ehris-pds-label">SURNAME</td>
                            <td class="ehris-pds-value is-strong">{{ displayVal(motherEntry?.lastname) }}</td>
                        </tr>
                        <tr>
                            <td class="ehris-pds-label">FIRST NAME</td>
                            <td class="ehris-pds-value">
                                <span class="is-strong">{{ displayVal(fatherEntry?.firstname) }}</span>
                                <span class="ehris-pds-inline-label">NAME EXTENSION (JR., SR.)</span>
                                <span>{{ displayVal(fatherEntry?.extension || 'N/A') }}</span>
                            </td>
                            <td class="ehris-pds-label">FIRST NAME</td>
                            <td class="ehris-pds-value">{{ displayVal(motherEntry?.firstname) }}</td>
                        </tr>
                        <tr>
                            <td class="ehris-pds-label">MIDDLE NAME</td>
                            <td class="ehris-pds-value is-strong">{{ displayVal(fatherEntry?.middlename) }}</td>
                            <td class="ehris-pds-label">MIDDLE NAME</td>
                            <td class="ehris-pds-value is-strong">{{ displayVal(motherEntry?.middlename) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
        <p v-else class="ehris-muted">No family information on file.</p>

        <Dialog :open="editModalOpen" @update:open="(v) => { editModalOpen = v; }">
            <DialogContent class="sm:max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                <DialogHeader>
                    <DialogTitle>Edit Family Background</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submit" class="flex flex-col min-h-0 flex-1">
                    <div v-if="Object.keys(errors).length" class="ehris-family-errors shrink-0">
                        <p v-for="(msg, key) in errors" :key="key">{{ msg }}</p>
                    </div>

                    <div class="flex-1 min-h-0 overflow-y-auto pr-2 space-y-4">
                        <div class="ehris-family-section">
                            <div class="ehris-family-section-title">Spouse</div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="space-y-1">
                                    <label for="spouse-lastname" class="ehris-field-label">Surname</label>
                                    <Input id="spouse-lastname" v-model="spouseForm.lastname" type="text" placeholder="Surname" />
                                </div>
                                <div class="space-y-1">
                                    <label for="spouse-firstname" class="ehris-field-label">First name</label>
                                    <Input id="spouse-firstname" v-model="spouseForm.firstname" type="text" placeholder="First name" />
                                </div>
                                <div class="space-y-1">
                                    <label for="spouse-middlename" class="ehris-field-label">Middle name</label>
                                    <Input id="spouse-middlename" v-model="spouseForm.middlename" type="text" placeholder="Middle name" />
                                </div>
                                <div class="space-y-1">
                                    <label for="spouse-extension" class="ehris-field-label">Name extension</label>
                                    <select id="spouse-extension" v-model="spouseForm.extension" class="ehris-select w-full">
                                        <option value="" disabled>Name extension</option>
                                        <option v-for="opt in nameExtensionOptions" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label for="spouse-dob" class="ehris-field-label">Date of birth</label>
                                    <Input id="spouse-dob" v-model="spouseForm.dob" type="date" placeholder="Date of birth" />
                                </div>
                                <div class="space-y-1">
                                    <label for="spouse-deceased" class="ehris-field-label">Deceased</label>
                                    <select id="spouse-deceased" v-model="spouseForm.deceased" class="ehris-select w-full">
                                        <option value="" disabled>Deceased</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label for="spouse-occupation" class="ehris-field-label">Occupation</label>
                                    <Input id="spouse-occupation" v-model="spouseForm.occupation" type="text" placeholder="Occupation" />
                                </div>
                                <div class="space-y-1">
                                    <label for="spouse-employer-name" class="ehris-field-label">Employer/Business name</label>
                                    <Input id="spouse-employer-name" v-model="spouseForm.employer_name" type="text" placeholder="Employer/Business name" />
                                </div>
                                <div class="space-y-1 md:col-span-2">
                                    <label for="spouse-business-add" class="ehris-field-label">Business address</label>
                                    <Input id="spouse-business-add" v-model="spouseForm.business_add" type="text" placeholder="Business address" />
                                </div>
                                <div class="space-y-1">
                                    <label for="spouse-tel-num" class="ehris-field-label">Telephone no.</label>
                                    <Input
                                        id="spouse-tel-num"
                                        :model-value="formatPhilippineLandline(spouseForm.tel_num)"
                                        inputmode="numeric"
                                        pattern="[0-9-]*"
                                        maxlength="12"
                                        placeholder="Telephone no."
                                        @update:modelValue="(v) => { spouseForm.tel_num = digitsOnly(v).slice(0, 10); }"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="ehris-family-section">
                            <div class="ehris-family-section-title">Children</div>
                            <div class="space-y-3">
                                <div
                                    v-for="(child, idx) in childrenForm"
                                    :key="idx"
                                    class="ehris-child-row"
                                >
                                    <div class="grid gap-3 md:grid-cols-2">
                                        <div class="space-y-1">
                                            <label :for="`child-${idx}-lastname`" class="ehris-field-label">Surname</label>
                                            <Input :id="`child-${idx}-lastname`" v-model="child.lastname" type="text" placeholder="Surname" />
                                        </div>
                                        <div class="space-y-1">
                                            <label :for="`child-${idx}-firstname`" class="ehris-field-label">First name</label>
                                            <Input :id="`child-${idx}-firstname`" v-model="child.firstname" type="text" placeholder="First name" />
                                        </div>
                                        <div class="space-y-1">
                                            <label :for="`child-${idx}-middlename`" class="ehris-field-label">Middle name</label>
                                            <Input :id="`child-${idx}-middlename`" v-model="child.middlename" type="text" placeholder="Middle name" />
                                        </div>
                                        <div class="space-y-1">
                                            <label :for="`child-${idx}-extension`" class="ehris-field-label">Name extension</label>
                                            <select :id="`child-${idx}-extension`" v-model="child.extension" class="ehris-select w-full">
                                                <option value="" disabled>Name extension</option>
                                                <option v-for="opt in nameExtensionOptions" :key="opt" :value="opt">{{ opt }}</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label :for="`child-${idx}-dob`" class="ehris-field-label">Date of birth</label>
                                            <Input :id="`child-${idx}-dob`" v-model="child.dob" type="date" placeholder="Date of birth" />
                                        </div>
                                        <div class="space-y-1">
                                            <label :for="`child-${idx}-deceased`" class="ehris-field-label">Deceased</label>
                                            <select :id="`child-${idx}-deceased`" v-model="child.deceased" class="ehris-select w-full">
                                                <option value="" disabled>Deceased</option>
                                                <option value="Yes">Yes</option>
                                                <option value="No">No</option>
                                            </select>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="ehris-child-remove"
                                        :disabled="childrenForm.length <= 1"
                                        @click="removeChild(idx)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="addChild">
                                    <Plus class="size-4 mr-1" />
                                    Add child
                                </Button>
                            </div>
                        </div>

                        <div class="ehris-family-section">
                            <div class="ehris-family-section-title">Father</div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="space-y-1">
                                    <label for="father-lastname" class="ehris-field-label">Surname</label>
                                    <Input id="father-lastname" v-model="fatherForm.lastname" type="text" placeholder="Surname" />
                                </div>
                                <div class="space-y-1">
                                    <label for="father-firstname" class="ehris-field-label">First name</label>
                                    <Input id="father-firstname" v-model="fatherForm.firstname" type="text" placeholder="First name" />
                                </div>
                                <div class="space-y-1">
                                    <label for="father-middlename" class="ehris-field-label">Middle name</label>
                                    <Input id="father-middlename" v-model="fatherForm.middlename" type="text" placeholder="Middle name" />
                                </div>
                                <div class="space-y-1">
                                    <label for="father-extension" class="ehris-field-label">Name extension</label>
                                    <select id="father-extension" v-model="fatherForm.extension" class="ehris-select w-full">
                                        <option value="" disabled>Name extension</option>
                                        <option v-for="opt in nameExtensionOptions" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label for="father-deceased" class="ehris-field-label">Deceased</label>
                                    <select id="father-deceased" v-model="fatherForm.deceased" class="ehris-select w-full">
                                        <option value="" disabled>Deceased</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="ehris-family-section">
                            <div class="ehris-family-section-title">Mother (Maiden Name)</div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="space-y-1">
                                    <label for="mother-lastname" class="ehris-field-label">Surname</label>
                                    <Input id="mother-lastname" v-model="motherForm.lastname" type="text" placeholder="Surname" />
                                </div>
                                <div class="space-y-1">
                                    <label for="mother-firstname" class="ehris-field-label">First name</label>
                                    <Input id="mother-firstname" v-model="motherForm.firstname" type="text" placeholder="First name" />
                                </div>
                                <div class="space-y-1">
                                    <label for="mother-middlename" class="ehris-field-label">Middle name</label>
                                    <Input id="mother-middlename" v-model="motherForm.middlename" type="text" placeholder="Middle name" />
                                </div>
                                <div class="space-y-1">
                                    <label for="mother-extension" class="ehris-field-label">Name extension</label>
                                    <select id="mother-extension" v-model="motherForm.extension" class="ehris-select w-full">
                                        <option value="" disabled>Name extension</option>
                                        <option v-for="opt in nameExtensionOptions" :key="opt" :value="opt">{{ opt }}</option>
                                    </select>
                                </div>
                                <div class="space-y-1">
                                    <label for="mother-deceased" class="ehris-field-label">Deceased</label>
                                    <select id="mother-deceased" v-model="motherForm.deceased" class="ehris-select w-full">
                                        <option value="" disabled>Deceased</option>
                                        <option value="Yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="mt-4 shrink-0">
                        <DialogClose as-child>
                            <Button type="button" variant="ghost" :disabled="processing">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">
                            <Spinner v-if="processing" class="size-4 mr-1" />
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </section>
</template>

<style scoped>
.ehris-pds-family-sheet {
    overflow-x: auto;
}

.ehris-pds-family-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 0.875rem;
}

.ehris-pds-family-table th,
.ehris-pds-family-table td {
    border: 1px solid hsl(var(--border));
    padding: 0.5rem 0.625rem;
    vertical-align: top;
}

.ehris-pds-family-table th {
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    font-size: 0.75rem;
    font-weight: 700;
    text-align: left;
}

.ehris-pds-head-row th {
    text-transform: uppercase;
}

.ehris-pds-no {
    display: inline-block;
    min-width: 2rem;
}

.ehris-pds-label {
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.ehris-pds-value {
    color: hsl(var(--foreground));
    line-height: 1.35;
}

.is-strong {
    font-weight: 600;
}

.ehris-pds-inline-label {
    margin-left: 0.75rem;
    margin-right: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
}

.ehris-family-errors {
    margin-bottom: 0.75rem;
    padding: 0.625rem 0.875rem;
    border-radius: 0.375rem;
    border: 1px solid hsl(var(--destructive));
    background: hsl(var(--destructive) / 0.08);
    color: hsl(var(--destructive));
    font-size: 0.875rem;
}

.ehris-select {
    border: 1px solid hsl(var(--border));
    border-radius: 0.375rem;
    padding: 0.25rem 0.75rem;
    height: 2.25rem;
    background: hsl(var(--card));
    color: hsl(var(--foreground));
    font-size: 0.875rem;
}

.ehris-field-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
}

.ehris-family-section {
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    padding: 0.75rem;
    background: hsl(var(--background));
}

.ehris-family-section-title {
    font-weight: 700;
    font-size: 0.9rem;
    margin-bottom: 0.6rem;
}

.ehris-child-row {
    position: relative;
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    padding: 0.75rem;
    padding-right: 3rem;
    background: hsl(var(--card));
}

.ehris-child-remove {
    position: absolute;
    top: 0.6rem;
    right: 0.6rem;
}
</style>
