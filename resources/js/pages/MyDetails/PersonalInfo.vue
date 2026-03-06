<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return 'N/A';
    const s = String(v).trim();
    return s === '' ? 'N/A' : s;
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

defineProps<{
    personalInfo?: Record<string, unknown> | null;
    officialInfo?: Record<string, unknown> | null;
    contactInfo?: Record<string, unknown> | null;
    profile?: Record<string, unknown> | null;
}>();
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>Personal information</h3>
            <button
                type="button"
                class="ehris-btn-grade-subject"
                aria-label="Edit personal information"
            >
                <Pencil class="size-4" />
                <span>Edit</span>
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
                        <div class="ehris-pds-personal-row"><dt>SEX AT BIRTH</dt><dd>{{ val(personalInfo?.gender) }}</dd></div>
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

                <div class="ehris-pds-personal-section">
                    <div class="ehris-pds-personal-title">
                        <span class="ehris-pds-personal-roman">III.</span>
                        <span>CONTACT AND ADDRESS INFORMATION</span>
                    </div>
                    <dl class="ehris-pds-personal-content">
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - HOUSE/BLOCK/LOT</dt><dd>{{ val(contactInfo?.house_block_lotnum) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - STREET</dt><dd>{{ val(contactInfo?.street_add) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - SUBDIVISION/VILLAGE</dt><dd>{{ val(contactInfo?.subdivision_village) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - BARANGAY</dt><dd>{{ val(contactInfo?.residential_barangay_name ?? contactInfo?.barangay) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - CITY/MUNICIPALITY</dt><dd>{{ val(contactInfo?.city_municipality) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - PROVINCE</dt><dd>{{ val(contactInfo?.residential_province_name ?? contactInfo?.province) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>RESIDENTIAL - ZIP CODE</dt><dd>{{ val(contactInfo?.zip_code) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - HOUSE/BLOCK/LOT</dt><dd>{{ val(contactInfo?.house_block_lotnum1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - STREET</dt><dd>{{ val(contactInfo?.street_add1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - SUBDIVISION/VILLAGE</dt><dd>{{ val(contactInfo?.subdivision_village1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - BARANGAY</dt><dd>{{ val(contactInfo?.permanent_barangay_name ?? contactInfo?.barangay1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - CITY/MUNICIPALITY</dt><dd>{{ val(contactInfo?.city_municipality1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - PROVINCE</dt><dd>{{ val(contactInfo?.permanent_province_name ?? contactInfo?.province1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>PERMANENT - ZIP CODE</dt><dd>{{ val(contactInfo?.zip_code1) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>TELEPHONE NO.</dt><dd>{{ val(contactInfo?.phone_num) }}</dd></div>
                        <div class="ehris-pds-personal-row"><dt>MOBILE NO.</dt><dd>{{ val(contactInfo?.mobile_num) }}</dd></div>
                        <div class="ehris-pds-personal-row">
                            <dt>E-MAIL ADDRESS</dt>
                            <dd>
                                <a
                                    v-if="contactInfo?.email && String(contactInfo.email).trim() !== ''"
                                    :href="`mailto:${contactInfo.email}`"
                                    class="ehris-email-link"
                                >{{ val(contactInfo.email) }}</a>
                                <span v-else>N/A</span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </template>
        <p v-else class="ehris-muted">No personal information on file.</p>
    </section>
</template>

<style scoped>
.ehris-pds-personal-form {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: start;
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

@media (max-width: 1280px) {
    .ehris-pds-personal-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .ehris-pds-personal-form {
        grid-template-columns: 1fr;
    }

    .ehris-pds-personal-row {
        grid-template-columns: 1fr;
    }

    .ehris-pds-personal-row dt {
        border-right: none;
        border-bottom: 1px solid hsl(var(--border));
    }
}
</style>
