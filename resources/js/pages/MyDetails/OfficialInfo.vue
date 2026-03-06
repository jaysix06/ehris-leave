<script setup lang="ts">
function val(v: unknown): string {
    if (v == null || v === '') return 'N/A';
    const s = String(v).trim();
    return s === '' ? 'N/A' : s;
}

defineProps<{
    officialInfo?: Record<string, unknown> | null;
}>();
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>Official information</h3>
        </div>
        <div v-if="officialInfo" class="ehris-pds-official-form">
            <div class="ehris-pds-official-section">
                <div class="ehris-pds-official-section-title">
                    <span class="ehris-pds-official-roman">I.</span>
                    <span>EMPLOYEE IDENTIFICATION</span>
                </div>
                <dl class="ehris-pds-official-content">
                    <div class="ehris-pds-official-row">
                        <dt>EMPLOYEE NO.</dt>
                        <dd>{{ val(officialInfo.employee_id) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>HR ID</dt>
                        <dd>{{ val(officialInfo.hrid) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>PREFIX NAME</dt>
                        <dd>{{ val(officialInfo.prefix_name) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>FIRST NAME</dt>
                        <dd>{{ val(officialInfo.firstname) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>MIDDLE NAME</dt>
                        <dd>{{ val(officialInfo.middlename) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>LAST NAME</dt>
                        <dd>{{ val(officialInfo.lastname) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>NAME EXTENSION (JR., SR.)</dt>
                        <dd>{{ val(officialInfo.extension) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>DEPED EMAIL</dt>
                        <dd>
                            <a
                                v-if="officialInfo.email && String(officialInfo.email).trim() !== ''"
                                :href="`mailto:${officialInfo.email}`"
                                class="ehris-email-link"
                            >{{ val(officialInfo.email) }}</a>
                            <span v-else>N/A</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="ehris-pds-official-grid">
                <div class="ehris-pds-official-section">
                    <div class="ehris-pds-official-section-title">
                        <span class="ehris-pds-official-roman">II.</span>
                        <span>POSITION AND EMPLOYMENT</span>
                    </div>
                    <dl class="ehris-pds-official-content">
                        <div class="ehris-pds-official-row">
                            <dt>ITEM NO.</dt>
                            <dd>{{ val(officialInfo.item_no) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>PLANTILLA ASSIGNMENT</dt>
                            <dd>{{ val(officialInfo.plantilla) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>POSITION</dt>
                            <dd>{{ val(officialInfo.job_title) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>EMPLOYMENT STATUS</dt>
                            <dd>{{ val(officialInfo.employ_status) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>SALARY GRADE</dt>
                            <dd>{{ val(officialInfo.salary_grade) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>STEP</dt>
                            <dd>{{ val(officialInfo.step) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>DATE OF JOINING</dt>
                            <dd>{{ val(officialInfo.date_of_joining) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>LAST DATE OF PROMOTION</dt>
                            <dd>{{ val(officialInfo.date_of_promotion) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>YEARS OF EXPERIENCE</dt>
                            <dd>{{ val(officialInfo.year_experience) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="ehris-pds-official-section">
                    <div class="ehris-pds-official-section-title">
                        <span class="ehris-pds-official-roman">III.</span>
                        <span>ORGANIZATION ASSIGNMENT</span>
                    </div>
                    <dl class="ehris-pds-official-content">
                        <div class="ehris-pds-official-row">
                            <dt>ROLE</dt>
                            <dd>{{ val(officialInfo.role) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>DIVISION OFFICE</dt>
                            <dd>{{ val(officialInfo.division_code) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>BUSINESS UNIT</dt>
                            <dd>{{ val(officialInfo.business_id) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>DEPARTMENT</dt>
                            <dd>{{ val(officialInfo.office) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>REPORTING MANAGER</dt>
                            <dd>{{ val(officialInfo.reporting_manager) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
        <p v-else class="ehris-muted">No official information on file.</p>
    </section>
</template>

<style scoped>
.ehris-pds-official-form {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: start;
}

.ehris-pds-official-grid {
    display: contents;
}

.ehris-pds-official-section-title {
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

.ehris-pds-official-roman {
    width: 2.25rem;
    text-align: right;
    flex: 0 0 2.25rem;
}

.ehris-pds-official-content {
    margin: 0;
}

.ehris-pds-official-row {
    display: grid;
    grid-template-columns: 240px 1fr;
    border-left: 1px solid hsl(var(--border));
    border-right: 1px solid hsl(var(--border));
    border-bottom: 1px solid hsl(var(--border));
}

.ehris-pds-official-row dt {
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.5rem 0.75rem;
    border-right: 1px solid hsl(var(--border));
    text-transform: uppercase;
}

.ehris-pds-official-row dd {
    margin: 0;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    display: flex;
    align-items: center;
    min-height: 2.25rem;
}

@media (max-width: 1280px) {
    .ehris-pds-official-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .ehris-pds-official-form {
        grid-template-columns: 1fr;
    }

    .ehris-pds-official-row {
        grid-template-columns: 1fr;
    }

    .ehris-pds-official-row dt {
        border-right: none;
        border-bottom: 1px solid hsl(var(--border));
    }
}
</style>
