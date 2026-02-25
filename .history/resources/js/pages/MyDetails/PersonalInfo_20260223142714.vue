<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

defineProps<{
    personalInfo?: Record<string, unknown> | null;
}>();
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Personal  information</h3>
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

<style scoped>
.ehris-gov-id-header {
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #dc2626;
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
}
</style>
