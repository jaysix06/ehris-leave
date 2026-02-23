<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

defineProps<{
    officialInfo?: Record<string, unknown> | null;
}>();
</script>

<template>
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
