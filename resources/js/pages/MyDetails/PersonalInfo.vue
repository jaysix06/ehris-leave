<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return 'N/A';
    const s = String(v).trim();
    return s === '' ? 'N/A' : s;
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
            <div class="ehris-official-info-grid">
                <dl class="ehris-official-info-col">
                    <div class="ehris-details-row"><dt>Surname</dt><dd>{{ val(officialInfo?.lastname ?? profile?.lastname) }}</dd></div>
                    <div class="ehris-details-row"><dt>First name</dt><dd>{{ val(officialInfo?.firstname ?? profile?.firstname) }}</dd></div>
                    <div class="ehris-details-row"><dt>Middle name</dt><dd>{{ val(officialInfo?.middlename ?? profile?.middlename) }}</dd></div>
                    <div class="ehris-details-row"><dt>Name extension (Jr., Sr.)</dt><dd>{{ val(officialInfo?.extension ?? profile?.extname) }}</dd></div>
                    <div class="ehris-details-row"><dt>Date of birth</dt><dd>{{ val(personalInfo?.dob) }}</dd></div>
                    <div class="ehris-details-row"><dt>Place of birth</dt><dd>{{ val(personalInfo?.pob) }}</dd></div>
                    <div class="ehris-details-row"><dt>Gender</dt><dd>{{ val(personalInfo?.gender) }}</dd></div>
                    <div class="ehris-details-row"><dt>Civil status</dt><dd>{{ val(personalInfo?.civil_stat) }}</dd></div>
                    <div class="ehris-details-row"><dt>Height (m)</dt><dd>{{ val(personalInfo?.height) }}</dd></div>
                    <div class="ehris-details-row"><dt>Weight (kg)</dt><dd>{{ val(personalInfo?.weight) }}</dd></div>
                    <div class="ehris-details-row"><dt>Blood type</dt><dd>{{ val(personalInfo?.blood_type) }}</dd></div>
                </dl>
                <dl class="ehris-official-info-col">
                    <div class="ehris-details-row"><dt>UMID ID No.</dt><dd>N/A</dd></div>
                    <div class="ehris-details-row"><dt>PAG-IBIG ID No.</dt><dd>{{ val(personalInfo?.pag_ibig) }}</dd></div>
                    <div class="ehris-details-row"><dt>PhilHealth No.</dt><dd>{{ val(personalInfo?.philhealth) }}</dd></div>
                    <div class="ehris-details-row"><dt>PhilSys Number (PSN)</dt><dd>N/A</dd></div>
                    <div class="ehris-details-row"><dt>TIN No.</dt><dd>{{ val(personalInfo?.tin) }}</dd></div>
                    <div class="ehris-details-row"><dt>Agency employee No.</dt><dd>{{ val(personalInfo?.agency_emp_num) }}</dd></div>
                    <div class="ehris-details-row"><dt>Citizenship</dt><dd>
                        {{ val(personalInfo?.citizenship) }}
                        <span v-if="personalInfo?.dual_citizenship"> / Dual ({{ val(personalInfo?.dual_citizenship) }})</span>
                        <span v-if="personalInfo?.country"> — {{ val(personalInfo?.country) }}</span>
                    </dd></div>
                </dl>
                <dl class="ehris-official-info-col">
                    <div class="ehris-details-row"><dt>Residential — House/Block/Lot</dt><dd>{{ val(contactInfo?.house_block_lotnum) }}</dd></div>
                    <div class="ehris-details-row"><dt>Residential — Street</dt><dd>{{ val(contactInfo?.street_add) }}</dd></div>
                    <div class="ehris-details-row"><dt>Residential — Subdivision/Village</dt><dd>{{ val(contactInfo?.subdivision_village) }}</dd></div>
                    <div class="ehris-details-row"><dt>Residential — Barangay</dt><dd>{{ val(contactInfo?.residential_barangay_name ?? contactInfo?.barangay) }}</dd></div>
                    <div class="ehris-details-row"><dt>Residential — City/Municipality</dt><dd>{{ val(contactInfo?.city_municipality) }}</dd></div>
                    <div class="ehris-details-row"><dt>Residential — Province</dt><dd>{{ val(contactInfo?.residential_province_name ?? contactInfo?.province) }}</dd></div>
                    <div class="ehris-details-row"><dt>Residential — ZIP Code</dt><dd>{{ val(contactInfo?.zip_code) }}</dd></div>
                    <div class="ehris-details-row"><dt>Permanent — House/Block/Lot</dt><dd>{{ val(contactInfo?.house_block_lotnum1) }}</dd></div>
                    <div class="ehris-details-row"><dt>Permanent — Street</dt><dd>{{ val(contactInfo?.street_add1) }}</dd></div>
                    <div class="ehris-details-row"><dt>Permanent — Subdivision/Village</dt><dd>{{ val(contactInfo?.subdivision_village1) }}</dd></div>
                    <div class="ehris-details-row"><dt>Permanent — Barangay</dt><dd>{{ val(contactInfo?.permanent_barangay_name ?? contactInfo?.barangay1) }}</dd></div>
                    <div class="ehris-details-row"><dt>Permanent — City/Municipality</dt><dd>{{ val(contactInfo?.city_municipality1) }}</dd></div>
                    <div class="ehris-details-row"><dt>Permanent — Province</dt><dd>{{ val(contactInfo?.permanent_province_name ?? contactInfo?.province1) }}</dd></div>
                    <div class="ehris-details-row"><dt>Permanent — ZIP Code</dt><dd>{{ val(contactInfo?.zip_code1) }}</dd></div>
                    <div class="ehris-details-row"><dt>Telephone No.</dt><dd>{{ val(contactInfo?.phone_num) }}</dd></div>
                    <div class="ehris-details-row"><dt>Mobile No.</dt><dd>{{ val(contactInfo?.mobile_num) }}</dd></div>
                    <div class="ehris-details-row"><dt>E-mail address</dt><dd>
                        <a
                            v-if="contactInfo?.email"
                            :href="`mailto:${contactInfo.email}`"
                            class="ehris-email-link"
                        >{{ val(contactInfo.email) }}</a>
                        <span v-else>N/A</span>
                    </dd></div>
                </dl>
            </div>

            <h4 v-if="personalInfo" class="ehris-gov-id-header">Government identification</h4>
            <div v-if="personalInfo" class="ehris-official-info-grid">
                <dl class="ehris-official-info-col">
                    <div class="ehris-details-row"><dt>PRC No.</dt><dd>{{ val(personalInfo?.prc_no) }}</dd></div>
                    <div class="ehris-details-row"><dt>TIN</dt><dd>{{ val(personalInfo?.tin) }}</dd></div>
                    <div class="ehris-details-row"><dt>GSIS BP No.</dt><dd>{{ val(personalInfo?.gsis_bp) }}</dd></div>
                    <div class="ehris-details-row"><dt>PAG-IBIG No.</dt><dd>{{ val(personalInfo?.pag_ibig) }}</dd></div>
                </dl>
                <dl class="ehris-official-info-col">
                    <div class="ehris-details-row"><dt>SSS No.</dt><dd>{{ val(personalInfo?.sss) }}</dd></div>
                    <div class="ehris-details-row"><dt>Philhealth No.</dt><dd>{{ val(personalInfo?.philhealth) }}</dd></div>
                    <div class="ehris-details-row"><dt>GSIS No.</dt><dd>{{ val(personalInfo?.gsis) }}</dd></div>
                </dl>
            </div>
        </template>
        <p v-else class="ehris-muted">No personal information on file.</p>
    </section>
</template>

<style scoped>
.ehris-gov-id-header {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: hsl(var(--foreground));
}
</style>
