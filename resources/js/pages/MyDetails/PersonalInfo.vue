<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil } from 'lucide-vue-next';
import { ref, watch } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { digitsOnly, formatPhilippineLandline, formatPhilippineMobile } from '@/utils/phPhone';

function val(v: unknown): string {
    if (v == null || v === '') return 'N/A';
    const s = String(v).trim();
    return s === '' ? 'N/A' : s;
}

function valInput(v: unknown): string {
    if (v == null) return '';
    return String(v);
}

function normalizeLookupFieldValue(v: unknown): string {
    const value = valInput(v).trim();

    return value === '0' ? '' : value;
}

function normalizeDateToIso(v: unknown): string {
    const raw = valInput(v).trim();
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

function pick(source: Record<string, unknown> | null | undefined, keys: string[]): unknown {
    if (!source) return null;

    for (const key of keys) {
        const value = source[key];
        if (value == null) continue;
        if (typeof value === 'string' && value.trim() === '') continue;
        return value;
    }

    return null;
}

const props = defineProps<{
    personalInfo?: Record<string, unknown> | null;
    officialInfo?: Record<string, unknown> | null;
    contactInfo?: Record<string, unknown> | null;
    profile?: Record<string, unknown> | null;
    personalUpdateUrl?: string;
    municipalitiesLookupUrl?: string;
    barangaysLookupUrl?: string;
    contactOptions?: {
        provinces?: { name: string; province_code: number | null }[];
        municipalities?: { name: string; municipal_code: number | null; province_code: number | null }[];
    };
}>();

const editModalOpen = ref(false);
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const residentialMunicipalities = ref<{ name: string; municipal_code: number | null }[]>([]);
const permanentMunicipalities = ref<{ name: string; municipal_code: number | null }[]>([]);
const residentialBarangays = ref<string[]>([]);
const permanentBarangays = ref<string[]>([]);
const hydratingAddressSelections = ref(false);
const form = ref({
    dob: '',
    pob: '',
    gender: '',
    civil_stat: '',
    height: '',
    weight: '',
    blood_type: '',
    citizenship: '',
    dual_citizenship: '',
    country: '',
    umid: '',
    pag_ibig: '',
    philhealth: '',
    philsys: '',
    tin: '',
    agency_emp_num: '',
    prc_no: '',
    sss: '',
    gsis: '',
    gsis_bp: '',
    house_block_lotnum: '',
    street_add: '',
    subdivision_village: '',
    barangay: '',
    city_municipality: '',
    province: '',
    zip_code: '',
    house_block_lotnum1: '',
    street_add1: '',
    subdivision_village1: '',
    barangay1: '',
    city_municipality1: '',
    province1: '',
    zip_code1: '',
    phone_num: '',
    mobile_num: '',
    email: '',
});

const genderOptions = ['Male', 'Female'];
const civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Annulled', 'Others'];
const bloodTypeOptions = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
const countryOptions = [
    'Afghanistan',
    'Albania',
    'Algeria',
    'Andorra',
    'Angola',
    'Antigua and Barbuda',
    'Argentina',
    'Armenia',
    'Australia',
    'Austria',
    'Azerbaijan',
    'Bahamas',
    'Bahrain',
    'Bangladesh',
    'Barbados',
    'Belarus',
    'Belgium',
    'Belize',
    'Benin',
    'Bhutan',
    'Bolivia',
    'Bosnia and Herzegovina',
    'Botswana',
    'Brazil',
    'Brunei',
    'Bulgaria',
    'Burkina Faso',
    'Burundi',
    'Cabo Verde',
    'Cambodia',
    'Cameroon',
    'Canada',
    'Central African Republic',
    'Chad',
    'Chile',
    'China',
    'Colombia',
    'Comoros',
    'Congo',
    'Costa Rica',
    "Cote d'Ivoire",
    'Croatia',
    'Cuba',
    'Cyprus',
    'Czechia',
    'Denmark',
    'Djibouti',
    'Dominica',
    'Dominican Republic',
    'Ecuador',
    'Egypt',
    'El Salvador',
    'Equatorial Guinea',
    'Eritrea',
    'Estonia',
    'Eswatini',
    'Ethiopia',
    'Fiji',
    'Finland',
    'France',
    'Gabon',
    'Gambia',
    'Georgia',
    'Germany',
    'Ghana',
    'Greece',
    'Grenada',
    'Guatemala',
    'Guinea',
    'Guinea-Bissau',
    'Guyana',
    'Haiti',
    'Honduras',
    'Hungary',
    'Iceland',
    'India',
    'Indonesia',
    'Iran',
    'Iraq',
    'Ireland',
    'Israel',
    'Italy',
    'Jamaica',
    'Japan',
    'Jordan',
    'Kazakhstan',
    'Kenya',
    'Kiribati',
    'Kuwait',
    'Kyrgyzstan',
    'Laos',
    'Latvia',
    'Lebanon',
    'Lesotho',
    'Liberia',
    'Libya',
    'Liechtenstein',
    'Lithuania',
    'Luxembourg',
    'Madagascar',
    'Malawi',
    'Malaysia',
    'Maldives',
    'Mali',
    'Malta',
    'Marshall Islands',
    'Mauritania',
    'Mauritius',
    'Mexico',
    'Micronesia',
    'Moldova',
    'Monaco',
    'Mongolia',
    'Montenegro',
    'Morocco',
    'Mozambique',
    'Myanmar',
    'Namibia',
    'Nauru',
    'Nepal',
    'Netherlands',
    'New Zealand',
    'Nicaragua',
    'Niger',
    'Nigeria',
    'North Korea',
    'North Macedonia',
    'Norway',
    'Oman',
    'Pakistan',
    'Palau',
    'Panama',
    'Papua New Guinea',
    'Paraguay',
    'Peru',
    'Philippines',
    'Poland',
    'Portugal',
    'Qatar',
    'Romania',
    'Russia',
    'Rwanda',
    'Saint Kitts and Nevis',
    'Saint Lucia',
    'Saint Vincent and the Grenadines',
    'Samoa',
    'San Marino',
    'Sao Tome and Principe',
    'Saudi Arabia',
    'Senegal',
    'Serbia',
    'Seychelles',
    'Sierra Leone',
    'Singapore',
    'Slovakia',
    'Slovenia',
    'Solomon Islands',
    'Somalia',
    'South Africa',
    'South Korea',
    'South Sudan',
    'Spain',
    'Sri Lanka',
    'Sudan',
    'Suriname',
    'Sweden',
    'Switzerland',
    'Syria',
    'Taiwan',
    'Tajikistan',
    'Tanzania',
    'Thailand',
    'Timor-Leste',
    'Togo',
    'Tonga',
    'Trinidad and Tobago',
    'Tunisia',
    'Turkey',
    'Turkmenistan',
    'Tuvalu',
    'Uganda',
    'Ukraine',
    'United Arab Emirates',
    'United Kingdom',
    'United States',
    'Uruguay',
    'Uzbekistan',
    'Vanuatu',
    'Vatican City',
    'Venezuela',
    'Vietnam',
    'Yemen',
    'Zambia',
    'Zimbabwe',
];

function optionsWithCurrent(options: string[], current: string): string[] {
    const trimmed = current.trim();
    if (trimmed === '' || options.includes(trimmed)) {
        return options;
    }
    return [trimmed, ...options];
}

function residentialBarangayOptions(): string[] {
    return residentialBarangays.value;
}

function permanentBarangayOptions(): string[] {
    return permanentBarangays.value;
}

function residentialMunicipalityOptions(): { name: string; municipal_code: number | null }[] {
    return residentialMunicipalities.value;
}

function permanentMunicipalityOptions(): { name: string; municipal_code: number | null }[] {
    return permanentMunicipalities.value;
}

function copyResidentialToPermanent(): void {
    hydratingAddressSelections.value = true;
    form.value = {
        ...form.value,
        house_block_lotnum1: form.value.house_block_lotnum,
        street_add1: form.value.street_add,
        subdivision_village1: form.value.subdivision_village,
        barangay1: form.value.barangay,
        city_municipality1: form.value.city_municipality,
        province1: form.value.province,
        zip_code1: form.value.zip_code,
    };

    void loadBarangays('permanent', form.value.city_municipality1, form.value.barangay1).finally(() => {
        hydratingAddressSelections.value = false;
    });
}

async function loadBarangays(
    type: 'residential' | 'permanent',
    municipalCode: string,
    currentBarangay = '',
): Promise<void> {
    const target = type === 'residential' ? residentialBarangays : permanentBarangays;
    const normalizedMunicipalCode = municipalCode.trim();
    const normalizedCurrentBarangay = normalizeLookupFieldValue(currentBarangay);

    if (normalizedMunicipalCode === '' || !props.barangaysLookupUrl) {
        target.value = normalizedCurrentBarangay !== '' ? [normalizedCurrentBarangay] : [];
        return;
    }

    try {
        const url = new URL(props.barangaysLookupUrl, window.location.origin);
        url.searchParams.set('municipal_code', normalizedMunicipalCode);

        const response = await fetch(url.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Failed to load barangays: ${response.status}`);
        }

        const payload = await response.json() as { barangays?: Array<{ name?: string | null }> };
        const fetchedBarangays = (payload.barangays ?? [])
            .map((barangay) => typeof barangay?.name === 'string' ? barangay.name.trim() : '')
            .filter((barangay): barangay is string => barangay !== '');

        target.value = fetchedBarangays;

        if (normalizedCurrentBarangay !== '' && !fetchedBarangays.includes(normalizedCurrentBarangay)) {
            target.value = [normalizedCurrentBarangay, ...fetchedBarangays];
        }
    } catch (error) {
        console.warn('[PersonalInfo] Failed to load barangays.', error);
        target.value = normalizedCurrentBarangay !== '' ? [normalizedCurrentBarangay] : [];
    }
}

async function loadMunicipalities(
    type: 'residential' | 'permanent',
    provinceCode: string,
    currentMunicipality = '',
): Promise<void> {
    const target = type === 'residential' ? residentialMunicipalities : permanentMunicipalities;
    const normalizedProvinceCode = provinceCode.trim();
    const normalizedCurrentMunicipality = normalizeLookupFieldValue(currentMunicipality);

    if (normalizedProvinceCode === '' || !props.municipalitiesLookupUrl) {
        target.value = normalizedCurrentMunicipality !== ''
            ? [{ name: normalizedCurrentMunicipality, municipal_code: ctypeNumber(normalizedCurrentMunicipality) }]
            : [];
        return;
    }

    try {
        const url = new URL(props.municipalitiesLookupUrl, window.location.origin);
        url.searchParams.set('province_code', normalizedProvinceCode);

        const response = await fetch(url.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Failed to load municipalities: ${response.status}`);
        }

        const payload = await response.json() as { municipalities?: Array<{ name?: string | null; municipal_code?: number | null }> };
        const fetchedMunicipalities = (payload.municipalities ?? [])
            .map((municipality) => ({
                name: typeof municipality?.name === 'string' ? municipality.name.trim() : '',
                municipal_code: typeof municipality?.municipal_code === 'number' ? municipality.municipal_code : null,
            }))
            .filter((municipality): municipality is { name: string; municipal_code: number | null } => municipality.name !== '');

        target.value = fetchedMunicipalities;

        if (
            normalizedCurrentMunicipality !== ''
            && !fetchedMunicipalities.some((municipality) => String(municipality.municipal_code ?? '') === normalizedCurrentMunicipality)
        ) {
            target.value = [
                { name: normalizedCurrentMunicipality, municipal_code: ctypeNumber(normalizedCurrentMunicipality) },
                ...fetchedMunicipalities,
            ];
        }
    } catch (error) {
        console.warn('[PersonalInfo] Failed to load municipalities.', error);
        target.value = normalizedCurrentMunicipality !== ''
            ? [{ name: normalizedCurrentMunicipality, municipal_code: ctypeNumber(normalizedCurrentMunicipality) }]
            : [];
    }
}

function ctypeNumber(value: string): number | null {
    return /^\d+$/.test(value) ? Number(value) : null;
}

function onTelephoneKeydown(e: KeyboardEvent): void {
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (e.key.length === 1 && !/\d/.test(e.key)) {
        e.preventDefault();
    }
}

function onTelephonePaste(e: ClipboardEvent): void {
    e.preventDefault();
    form.value.phone_num = digitsOnly(e.clipboardData?.getData('text') ?? '').slice(0, 10);
}

function onMobileKeydown(e: KeyboardEvent): void {
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (e.key.length === 1 && !/\d/.test(e.key)) {
        e.preventDefault();
    }
}

function onMobilePaste(e: ClipboardEvent): void {
    e.preventDefault();
    form.value.mobile_num = digitsOnly(e.clipboardData?.getData('text') ?? '').slice(0, 11);
}

function openEdit(): void {
    const p = props.personalInfo ?? {};
    const c = props.contactInfo ?? {};
    hydratingAddressSelections.value = true;
    form.value = {
        dob: normalizeDateToIso(p.dob),
        pob: valInput(p.pob),
        gender: valInput(p.gender),
        civil_stat: valInput(p.civil_stat),
        height: valInput(p.height),
        weight: valInput(p.weight),
        blood_type: valInput(p.blood_type),
        citizenship: valInput(p.citizenship),
        dual_citizenship: valInput(p.dual_citizenship),
        country: valInput(p.country),
        umid: valInput(p.umid ?? p.umid_no ?? p.umid_num),
        pag_ibig: valInput(p.pag_ibig),
        philhealth: valInput(p.philhealth),
        philsys: valInput(p.philsys ?? p.philsys_no ?? p.philsys_num ?? p.psn),
        tin: valInput(p.tin ?? p.tin_no),
        agency_emp_num: valInput(p.agency_emp_num ?? p.agency_employee_no ?? p.agency_emp_no),
        prc_no: valInput(p.prc_no),
        sss: valInput(p.sss),
        gsis: valInput(p.gsis),
        gsis_bp: valInput(p.gsis_bp),
        house_block_lotnum: valInput(c.house_block_lotnum),
        street_add: valInput(c.street_add),
        subdivision_village: valInput(c.subdivision_village),
        barangay: normalizeLookupFieldValue(c.residential_barangay_name ?? c.barangay),
        city_municipality: normalizeLookupFieldValue(c.city_municipality),
        province: normalizeLookupFieldValue(c.province),
        zip_code: valInput(c.zip_code),
        house_block_lotnum1: valInput(c.house_block_lotnum1),
        street_add1: valInput(c.street_add1),
        subdivision_village1: valInput(c.subdivision_village1),
        barangay1: normalizeLookupFieldValue(c.permanent_barangay_name ?? c.barangay1),
        city_municipality1: normalizeLookupFieldValue(c.city_municipality1),
        province1: normalizeLookupFieldValue(c.province1),
        zip_code1: valInput(c.zip_code1),
        phone_num: digitsOnly(c.phone_num).slice(0, 10),
        mobile_num: digitsOnly(c.mobile_num).slice(0, 11),
        email: valInput(props.profile?.personal_email ?? c.email),
    };
    void Promise.all([
        loadMunicipalities('residential', form.value.province, form.value.city_municipality),
        loadMunicipalities('permanent', form.value.province1, form.value.city_municipality1),
        loadBarangays('residential', form.value.city_municipality, form.value.barangay),
        loadBarangays('permanent', form.value.city_municipality1, form.value.barangay1),
    ]).finally(() => {
        hydratingAddressSelections.value = false;
    });
    errors.value = {};
    editModalOpen.value = true;
}

function submit(): void {
    if (! props.personalUpdateUrl) return;
    processing.value = true;
    errors.value = {};
    router.post(props.personalUpdateUrl, { ...form.value }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            editModalOpen.value = false;
        },
        onError: (e) => {
            errors.value = e as Record<string, string>;
            const firstError = Object.values(errors.value).find((message) => typeof message === 'string' && message.trim() !== '');
            toast.error(firstError ?? 'Unable to update personal information.');
        },
    });
}

watch(
    () => form.value.province,
    (provinceCode, previousProvinceCode) => {
        const provinceChanged = provinceCode !== previousProvinceCode;
        if (provinceChanged && !hydratingAddressSelections.value) {
            form.value.city_municipality = '';
            form.value.barangay = '';
            residentialBarangays.value = [];
        }

        void loadMunicipalities('residential', provinceCode, hydratingAddressSelections.value ? form.value.city_municipality : '');
    },
);

watch(
    () => form.value.province1,
    (provinceCode, previousProvinceCode) => {
        const provinceChanged = provinceCode !== previousProvinceCode;
        if (provinceChanged && !hydratingAddressSelections.value) {
            form.value.city_municipality1 = '';
            form.value.barangay1 = '';
            permanentBarangays.value = [];
        }

        void loadMunicipalities('permanent', provinceCode, hydratingAddressSelections.value ? form.value.city_municipality1 : '');
    },
);

watch(
    () => form.value.city_municipality,
    (municipalCode, previousMunicipalCode) => {
        const municipalityChanged = municipalCode !== previousMunicipalCode;
        if (municipalityChanged && !hydratingAddressSelections.value) {
            form.value.barangay = '';
        }

        void loadBarangays('residential', municipalCode, hydratingAddressSelections.value ? form.value.barangay : '');
    },
);

watch(
    () => form.value.city_municipality1,
    (municipalCode, previousMunicipalCode) => {
        const municipalityChanged = municipalCode !== previousMunicipalCode;
        if (municipalityChanged && !hydratingAddressSelections.value) {
            form.value.barangay1 = '';
        }

        void loadBarangays('permanent', municipalCode, hydratingAddressSelections.value ? form.value.barangay1 : '');
    },
);
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>Personal information</h3>
            <button
                type="button"
                class="ehris-edit-btn"
                aria-label="Edit personal information"
                @click="openEdit"
            >
                <Pencil class="size-4" />
            </button>
        </div>

        <template v-if="profile || personalInfo || officialInfo || contactInfo">
            <div class="ehris-pds-personal-form">
                <div class="ehris-pds-personal-section">
                    <div class="ehris-pds-personal-title">
                        <span class="ehris-pds-personal-roman">I.</span>
                        <span>BASIC PERSONAL DETAILS</span>
                    </div>
                    <dl class="ehris-pds-personal-content">
                        <div class="ehris-pds-personal-row"><dt>SURNAME</dt><dd>{{ val(officialInfo?.lastname ?? profile?.lastname) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>FIRST NAME</dt><dd>{{ val(officialInfo?.firstname ?? profile?.firstname) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>MIDDLE NAME</dt><dd>{{ val(officialInfo?.middlename ?? profile?.middlename) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>NAME EXTENSION (JR., SR.)</dt><dd>{{ val(officialInfo?.extension ?? profile?.extname) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>DATE OF BIRTH</dt><dd>{{ val(personalInfo?.dob) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PLACE OF BIRTH</dt><dd>{{ val(personalInfo?.pob) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>GENDER</dt><dd>{{ val(personalInfo?.gender) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>CIVIL STATUS</dt><dd>{{ val(personalInfo?.civil_stat) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>HEIGHT (m)</dt><dd>{{ val(personalInfo?.height) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>WEIGHT (kg)</dt><dd>{{ val(personalInfo?.weight) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>BLOOD TYPE</dt><dd>{{ val(personalInfo?.blood_type) }}</dd></div>
                    </dl>
                </div>

                <div class="ehris-pds-personal-section">
                    <div class="ehris-pds-personal-title">
                        <span class="ehris-pds-personal-roman">II.</span>
                        <span>CITIZENSHIP AND GOVERNMENT IDS</span>
                    </div>
                    <dl class="ehris-pds-personal-content">
                        <div class="ehris-pds-personal-row">
                            <dt>CITIZENSHIP</dt>
                            <dd>
                                {{ val(personalInfo?.citizenship) }}
                                <span v-if="personalInfo?.dual_citizenship"> / Dual ({{ val(personalInfo?.dual_citizenship) }})</span>
                                <span v-if="personalInfo?.country"> — {{ val(personalInfo?.country) }}</span>
                            </dd>
                        </div>
                        <div class="ehris-pds-personal-row"><dt>UMID ID NO.</dt><dd>{{ val(pick(personalInfo, ['umid', 'umid_no', 'umid_num'])) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PAG-IBIG ID NO.</dt><dd>{{ val(personalInfo?.pag_ibig) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PHILHEALTH NO.</dt><dd>{{ val(personalInfo?.philhealth) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PHILSYS NUMBER (PSN)</dt><dd>{{ val(pick(personalInfo, ['philsys', 'philsys_no', 'philsys_num', 'psn'])) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>TIN NO.</dt><dd>{{ val(personalInfo?.tin) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>AGENCY EMPLOYEE NO.</dt><dd>{{ val(pick(personalInfo, ['agency_emp_num', 'agency_employee_no', 'agency_emp_no'])) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PRC NO.</dt><dd>{{ val(personalInfo?.prc_no) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>SSS NO.</dt><dd>{{ val(personalInfo?.sss) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>GSIS NO.</dt><dd>{{ val(personalInfo?.gsis) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>GSIS BP NO.</dt><dd>{{ val(personalInfo?.gsis_bp) }}</dd></div>
                    </dl>
                </div>

                <div class="ehris-pds-personal-section ehris-pds-personal-section--full">
                    <div class="ehris-pds-personal-title">
                        <span class="ehris-pds-personal-roman">III.</span>
                        <span>CONTACT AND ADDRESS INFORMATION</span>
                    </div>
                    <dl class="ehris-pds-personal-content ehris-pds-personal-content-horizontal">
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - HOUSE/BLOCK/LOT</dt><dd>{{ val(contactInfo?.house_block_lotnum) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - STREET</dt><dd>{{ val(contactInfo?.street_add) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - SUBDIVISION/VILLAGE</dt><dd>{{ val(contactInfo?.subdivision_village) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - BARANGAY</dt><dd>{{ val(contactInfo?.residential_barangay_name ?? contactInfo?.barangay) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - CITY/MUNICIPALITY</dt><dd>{{ val(contactInfo?.residential_city_name ?? contactInfo?.city_municipality) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - PROVINCE</dt><dd>{{ val(contactInfo?.residential_province_name ?? contactInfo?.province) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - ZIP CODE</dt><dd>{{ val(contactInfo?.zip_code) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - HOUSE/BLOCK/LOT</dt><dd>{{ val(contactInfo?.house_block_lotnum1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - STREET</dt><dd>{{ val(contactInfo?.street_add1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - SUBDIVISION/VILLAGE</dt><dd>{{ val(contactInfo?.subdivision_village1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - BARANGAY</dt><dd>{{ val(contactInfo?.permanent_barangay_name ?? contactInfo?.barangay1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - CITY/MUNICIPALITY</dt><dd>{{ val(contactInfo?.permanent_city_name ?? contactInfo?.city_municipality1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - PROVINCE</dt><dd>{{ val(contactInfo?.permanent_province_name ?? contactInfo?.province1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - ZIP CODE</dt><dd>{{ val(contactInfo?.zip_code1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>TELEPHONE NO.</dt><dd>{{ val(formatPhilippineLandline(contactInfo?.phone_num)) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>MOBILE NO.</dt><dd>{{ val(formatPhilippineMobile(contactInfo?.mobile_num)) }}</dd></div>
                        <div class="ehris-pds-personal-row">
                            <dt>E-MAIL ADDRESS</dt>
                            <dd>
                                <a
                                    v-if="(props.profile?.personal_email ?? contactInfo?.email) && String(props.profile?.personal_email ?? contactInfo?.email).trim() !== ''"
                                    :href="`mailto:${props.profile?.personal_email ?? contactInfo?.email}`"
                                    class="ehris-email-link"
                                >{{ val(props.profile?.personal_email ?? contactInfo?.email) }}</a>
                                <span v-else>N/A</span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </template>
        <p v-else class="ehris-muted">No personal information on file.</p>

        <Dialog :open="editModalOpen" @update:open="(v) => { editModalOpen = v; }">
            <DialogContent class="ehris-edit-dialog-content sm:max-w-5xl max-h-[90vh] overflow-hidden flex flex-col">
                <DialogHeader>
                    <DialogTitle>Edit Personal Information</DialogTitle>
                </DialogHeader>

                <div class="ehris-modal-scroll">
                    <div class="ehris-modal-section">
                        <h4>Basic Personal Details</h4>
                        <div class="ehris-modal-grid">
                            <label class="ehris-modal-field">
                                <span>Date of Birth</span>
                                <Input v-model="form.dob" type="date" />
                            </label>
                            <label class="ehris-modal-field"><span>Place of Birth</span><Input v-model="form.pob" /></label>
                            <label class="ehris-modal-field">
                                <span>Gender</span>
                                <div class="ehris-radio-group">
                                    <label v-for="option in genderOptions" :key="option" class="ehris-radio-option">
                                        <input v-model="form.gender" type="radio" name="gender" :value="option">
                                        <span>{{ option }}</span>
                                    </label>
                                </div>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Civil Status</span>
                                <select v-model="form.civil_stat" class="ehris-select">
                                    <option value="" disabled>Select civil status</option>
                                    <option v-for="option in optionsWithCurrent(civilStatusOptions, form.civil_stat)" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Height (m)</span>
                                <Input v-model="form.height" type="number" inputmode="decimal" step="0.01" min="0" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Weight (kg)</span>
                                <Input v-model="form.weight" type="number" inputmode="decimal" step="0.1" min="0" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Blood Type</span>
                                <select v-model="form.blood_type" class="ehris-select">
                                    <option value="" disabled>Select blood type</option>
                                    <option v-for="option in optionsWithCurrent(bloodTypeOptions, form.blood_type)" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="ehris-modal-section">
                        <h4>Citizenship and IDs</h4>
                        <div class="ehris-modal-grid">
                            <label class="ehris-modal-field"><span>Citizenship</span><Input v-model="form.citizenship" /></label>
                            <label class="ehris-modal-field"><span>Dual Citizenship</span><Input v-model="form.dual_citizenship" /></label>
                            <label class="ehris-modal-field">
                                <span>Country</span>
                                <select v-model="form.country" class="ehris-select">
                                    <option value="" disabled>Select country</option>
                                    <option v-for="option in optionsWithCurrent(countryOptions, form.country)" :key="option" :value="option">{{ option }}</option>
                                </select>
                            </label>
                            <label class="ehris-modal-field"><span>UMID</span><Input v-model="form.umid" /></label>
                            <label class="ehris-modal-field"><span>Pag-IBIG</span><Input v-model="form.pag_ibig" /></label>
                            <label class="ehris-modal-field"><span>PhilHealth</span><Input v-model="form.philhealth" /></label>
                            <label class="ehris-modal-field"><span>PhilSys (PSN)</span><Input v-model="form.philsys" /></label>
                            <label class="ehris-modal-field"><span>TIN</span><Input v-model="form.tin" /></label>
                            <label class="ehris-modal-field"><span>Agency Employee No.</span><Input v-model="form.agency_emp_num" /></label>
                            <label class="ehris-modal-field"><span>PRC No.</span><Input v-model="form.prc_no" /></label>
                            <label class="ehris-modal-field"><span>SSS No.</span><Input v-model="form.sss" /></label>
                            <label class="ehris-modal-field"><span>GSIS No.</span><Input v-model="form.gsis" /></label>
                            <label class="ehris-modal-field"><span>GSIS BP No.</span><Input v-model="form.gsis_bp" /></label>
                        </div>
                    </div>

                    <div class="ehris-modal-section">
                        <h4>Residential Address</h4>
                        <div class="ehris-modal-grid">
                            <label class="ehris-modal-field"><span>House/Block/Lot</span><Input v-model="form.house_block_lotnum" /></label>
                            <label class="ehris-modal-field"><span>Street</span><Input v-model="form.street_add" /></label>
                            <label class="ehris-modal-field"><span>Subdivision/Village</span><Input v-model="form.subdivision_village" /></label>
                            <label class="ehris-modal-field">
                                <span>Province</span>
                                <select v-model="form.province" class="ehris-select">
                                    <option value="" disabled>Select province</option>
                                    <option
                                        v-for="p in props.contactOptions?.provinces ?? []"
                                        :key="String(p.province_code ?? p.name)"
                                        :value="String(p.province_code)"
                                    >
                                        {{ p.name }}
                                    </option>
                                </select>
                                <small class="ehris-modal-hint">Select province first, then city/municipality, then barangay.</small>
                            </label>
                            <label class="ehris-modal-field">
                                <span>City/Municipality</span>
                                <select v-model="form.city_municipality" class="ehris-select" :disabled="!form.province">
                                    <option value="" disabled>{{ form.province ? 'Select city/municipality' : 'Select province first' }}</option>
                                    <option
                                        v-for="m in residentialMunicipalityOptions()"
                                        :key="String(m.municipal_code ?? m.name)"
                                        :value="String(m.municipal_code)"
                                    >
                                        {{ m.name }}
                                    </option>
                                </select>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Barangay</span>
                                <select v-model="form.barangay" class="ehris-select" :disabled="!form.city_municipality">
                                    <option value="" disabled>{{ form.city_municipality ? 'Select barangay' : 'Select city/municipality first' }}</option>
                                    <option
                                        v-for="option in optionsWithCurrent(residentialBarangayOptions(), form.barangay)"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ option }}
                                    </option>
                                </select>
                            </label>
                            <label class="ehris-modal-field"><span>Zip Code</span><Input v-model="form.zip_code" /></label>
                        </div>
                    </div>

                    <div class="ehris-modal-section">
                        <h4>Permanent Address</h4>
                        <span
                            class="ehris-copy-link"
                            role="button"
                            tabindex="0"
                            @click="copyResidentialToPermanent"
                            @keydown.enter.prevent="copyResidentialToPermanent"
                            @keydown.space.prevent="copyResidentialToPermanent"
                        >Copy residential address</span>
                        <div class="ehris-modal-grid">
                            <label class="ehris-modal-field"><span>House/Block/Lot</span><Input v-model="form.house_block_lotnum1" /></label>
                            <label class="ehris-modal-field"><span>Street</span><Input v-model="form.street_add1" /></label>
                            <label class="ehris-modal-field"><span>Subdivision/Village</span><Input v-model="form.subdivision_village1" /></label>
                            <label class="ehris-modal-field">
                                <span>Province</span>
                                <select v-model="form.province1" class="ehris-select">
                                    <option value="" disabled>Select province</option>
                                    <option
                                        v-for="p in props.contactOptions?.provinces ?? []"
                                        :key="String(p.province_code ?? p.name)"
                                        :value="String(p.province_code)"
                                    >
                                        {{ p.name }}
                                    </option>
                                </select>
                                <small class="ehris-modal-hint">Select province first, then city/municipality, then barangay.</small>
                            </label>
                            <label class="ehris-modal-field">
                                <span>City/Municipality</span>
                                <select v-model="form.city_municipality1" class="ehris-select" :disabled="!form.province1">
                                    <option value="" disabled>{{ form.province1 ? 'Select city/municipality' : 'Select province first' }}</option>
                                    <option
                                        v-for="m in permanentMunicipalityOptions()"
                                        :key="String(m.municipal_code ?? m.name)"
                                        :value="String(m.municipal_code)"
                                    >
                                        {{ m.name }}
                                    </option>
                                </select>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Barangay</span>
                                <select v-model="form.barangay1" class="ehris-select" :disabled="!form.city_municipality1">
                                    <option value="" disabled>{{ form.city_municipality1 ? 'Select barangay' : 'Select city/municipality first' }}</option>
                                    <option
                                        v-for="option in optionsWithCurrent(permanentBarangayOptions(), form.barangay1)"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ option }}
                                    </option>
                                </select>
                            </label>
                            <label class="ehris-modal-field"><span>Zip Code</span><Input v-model="form.zip_code1" /></label>
                        </div>
                    </div>

                    <div class="ehris-modal-section">
                        <h4>Contact</h4>
                        <div class="ehris-modal-grid">
                            <label class="ehris-modal-field">
                                <span>Telephone No.</span>
                                <Input
                                    :model-value="formatPhilippineLandline(form.phone_num)"
                                    type="tel"
                                    inputmode="numeric"
                                    pattern="[-0-9]*"
                                    maxlength="12"
                                    @update:modelValue="(v) => { form.phone_num = digitsOnly(v).slice(0, 10); }"
                                    @keydown="onTelephoneKeydown"
                                    @paste="onTelephonePaste"
                                />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Mobile No.</span>
                                <Input
                                    :model-value="formatPhilippineMobile(form.mobile_num)"
                                    type="tel"
                                    inputmode="numeric"
                                    pattern="[-0-9]*"
                                    maxlength="13"
                                    @update:modelValue="(v) => { form.mobile_num = digitsOnly(v).slice(0, 11); }"
                                    @keydown="onMobileKeydown"
                                    @paste="onMobilePaste"
                                />
                            </label>
                            <label class="ehris-modal-field"><span>Email Address</span><Input v-model="form.email" type="email" /></label>
                        </div>
                    </div>

                    <p v-if="errors.message" class="ehris-form-error">{{ errors.message }}</p>
                </div>

                <DialogFooter class="mt-4 shrink-0 border-t pt-4">
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button type="button" :disabled="processing" @click="submit">
                        <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </section>
</template>

<style scoped>
.ehris-pds-personal-form {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
}

.ehris-pds-personal-section--full {
    grid-column: 1 / -1;
}

.ehris-pds-personal-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    border-bottom: none;
}

.ehris-pds-personal-roman {
    width: 2.25rem;
    text-align: right;
    flex: 0 0 2.25rem;
}

.ehris-pds-personal-content {
    margin: 0;
}

.ehris-pds-personal-content-horizontal {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
    align-items: start;
}

.ehris-pds-personal-row {
    display: grid;
    grid-template-columns: 220px 1fr;
    border-left: 1px solid hsl(var(--border));
    border-right: 1px solid hsl(var(--border));
    border-bottom: 1px solid hsl(var(--border));
}

.ehris-pds-personal-row dt {
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.5rem 0.75rem;
    border-right: 1px solid hsl(var(--border));
    text-transform: uppercase;
}

.ehris-pds-personal-row dd {
    margin: 0;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    display: flex;
    align-items: center;
    min-height: 2.25rem;
}

.ehris-pds-personal-content-horizontal .ehris-pds-personal-row {
    display: grid;
    grid-template-columns: 165px 1fr;
    border: 1px solid hsl(var(--border));
    align-items: start;
}

.ehris-pds-personal-content-horizontal .ehris-pds-personal-row dt {
    display: flex;
    align-items: flex-start;
    border-right: 1px solid hsl(var(--border));
    border-bottom: none;
    line-height: 1.3;
}

.ehris-modal-scroll {
    overflow-y: auto;
    padding-right: 0.25rem;
}

.ehris-modal-section {
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    background: hsl(var(--background));
}

.ehris-modal-section h4 {
    font-weight: 700;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.ehris-modal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.ehris-modal-field {
    display: grid;
    gap: 0.35rem;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.ehris-modal-field span {
    font-weight: 600;
    color: hsl(var(--foreground));
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

.ehris-radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.ehris-radio-option {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
}

.ehris-radio-option input[type='radio'] {
    accent-color: hsl(var(--primary));
}

.ehris-copy-link {
    display: inline-flex;
    margin-bottom: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: hsl(var(--primary));
    text-decoration: underline;
    text-underline-offset: 3px;
    cursor: pointer;
}

.ehris-copy-link:hover {
    color: hsl(var(--primary) / 0.85);
}

.ehris-form-error {
    color: hsl(var(--destructive));
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .ehris-edit-dialog-content {
        width: calc(100vw - 1rem);
        max-width: calc(100vw - 1rem);
        max-height: calc(100dvh - 1rem);
        padding: 0.875rem;
    }

    .ehris-modal-scroll {
        padding-right: 0;
    }

    .ehris-modal-section {
        padding: 0.625rem;
        margin-bottom: 0.625rem;
    }

    .ehris-modal-grid {
        grid-template-columns: 1fr;
        gap: 0.625rem;
    }
}

.ehris-pds-personal-content-horizontal .ehris-pds-personal-row dd {
    display: flex;
    align-items: flex-start;
    line-height: 1.3;
    text-align: left;
}

@media (max-width: 1280px) {
    .ehris-pds-personal-content-horizontal {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 1024px) {
    .ehris-pds-personal-content-horizontal {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .ehris-pds-personal-form {
        grid-template-columns: 1fr;
    }

    .ehris-pds-personal-content-horizontal {
        grid-template-columns: 1fr;
    }

    .ehris-pds-personal-row,
    .ehris-pds-personal-content-horizontal .ehris-pds-personal-row {
        grid-template-columns: minmax(122px, 44%) minmax(0, 1fr);
        align-items: stretch;
    }

    .ehris-pds-personal-row dt {
        display: flex;
        align-items: center;
        border-right: 1px solid hsl(var(--border));
        border-bottom: none;
        padding: 0.5rem 0.625rem;
        line-height: 1.2;
    }

    .ehris-pds-personal-row dd {
        padding: 0.5rem 0.625rem;
        min-height: 2.25rem;
        align-items: flex-start;
        line-height: 1.3;
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
}
</style>
