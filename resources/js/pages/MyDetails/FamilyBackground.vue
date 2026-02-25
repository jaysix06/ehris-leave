<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';
import { computed } from 'vue';

function val(v: unknown): string {
    if (v == null || v === '') return 'N/A';
    const s = String(v).trim();
    return s === '' ? 'N/A' : s;
}

function fullName(item: Record<string, unknown>): string {
    const parts = [item.firstname, item.middlename, item.lastname, item.extension].filter(Boolean);
    return parts.map(String).join(' ').trim() || 'N/A';
}

const props = defineProps<{
    family?: Record<string, unknown>[];
}>();

const familyList = computed(() => props.family ?? []);

const spouse = computed(() =>
    familyList.value.find((r) => String(r?.relationship ?? '').toLowerCase() === 'spouse') as Record<string, unknown> | undefined
);

const children = computed(() =>
    familyList.value.filter((r) => {
        const rel = String(r?.relationship ?? '').toLowerCase();
        return rel === 'child' || rel === 'children';
    })
);

const father = computed(() =>
    familyList.value.find((r) => String(r?.relationship ?? '').toLowerCase() === 'father') as Record<string, unknown> | undefined
);

const mother = computed(() =>
    familyList.value.find((r) => String(r?.relationship ?? '').toLowerCase() === 'mother') as Record<string, unknown> | undefined
);

const hasAnyFamily = computed(() => familyList.value.length > 0);
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>Family information</h3>
            <button
                type="button"
                class="ehris-btn-grade-subject"
                aria-label="Edit family information"
            >
                <Pencil class="size-4" />
                <span>Edit</span>
            </button>
        </div>

        <template v-if="hasAnyFamily">
            <div class="ehris-family-section">
                <h4 class="ehris-family-subtitle">Spouse</h4>
                <div class="ehris-official-info-grid">
                    <dl class="ehris-official-info-col">
                        <div class="ehris-details-row"><dt>Surname</dt><dd>{{ spouse ? val(spouse.lastname) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>First name</dt><dd>{{ spouse ? val(spouse.firstname) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>Middle name</dt><dd>{{ spouse ? val(spouse.middlename) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>Extension</dt><dd>{{ spouse ? val(spouse.extension) : 'N/A' }}</dd></div>
                    </dl>
                    <dl class="ehris-official-info-col">
                        <div class="ehris-details-row"><dt>Occupation</dt><dd>{{ spouse ? val(spouse.occupation) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>Employer</dt><dd>{{ spouse ? val(spouse.employer_name) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>Business address</dt><dd>{{ spouse ? val(spouse.business_add) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>Telephone</dt><dd>{{ spouse ? val(spouse.tel_num) : 'N/A' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="ehris-family-section">
                <h4 class="ehris-family-subtitle">Children</h4>
                <div class="ehris-table-wrap">
                    <table class="ehris-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Date of birth</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="children.length === 0">
                                <td>N/A</td>
                                <td>N/A</td>
                            </tr>
                            <tr v-for="(child, i) in children" :key="i">
                                <td>{{ fullName(child) }}</td>
                                <td>{{ val(child.dob) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="ehris-family-section">
                <h4 class="ehris-family-subtitle">Father</h4>
                <div class="ehris-official-info-grid">
                    <dl class="ehris-official-info-col">
                        <div class="ehris-details-row"><dt>Surname</dt><dd>{{ father ? val(father.lastname) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>First name</dt><dd>{{ father ? val(father.firstname) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>Middle name</dt><dd>{{ father ? val(father.middlename) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>Extension</dt><dd>{{ father ? val(father.extension) : 'N/A' }}</dd></div>
                    </dl>
                </div>
            </div>

            <div class="ehris-family-section">
                <h4 class="ehris-family-subtitle">Mother</h4>
                <div class="ehris-official-info-grid">
                    <dl class="ehris-official-info-col">
                        <div class="ehris-details-row"><dt>Surname</dt><dd>{{ mother ? val(mother.lastname) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>First name</dt><dd>{{ mother ? val(mother.firstname) : 'N/A' }}</dd></div>
                        <div class="ehris-details-row"><dt>Middle name</dt><dd>{{ mother ? val(mother.middlename) : 'N/A' }}</dd></div>
                    </dl>
                </div>
            </div>
        </template>

        <p v-else class="ehris-muted">No family information on file.</p>
    </section>
</template>

<style scoped>
.ehris-family-section {
    margin-bottom: 1.5rem;
}
.ehris-family-section:last-of-type {
    margin-bottom: 0;
}
.ehris-family-subtitle {
    font-size: 0.875rem;
    font-weight: 600;
    color: hsl(var(--foreground));
    margin: 0 0 0.5rem 0;
}
</style>
