<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

defineProps<{
    workExperience?: Record<string, unknown>[];
}>();
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Work experience</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit work experience">
                <Pencil class="size-4" />
            </button>
        </div>
        <div class="ehris-table-wrap" v-if="workExperience && workExperience.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Position</th>
                        <th>Inclusive dates</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in workExperience" :key="i">
                        <td>{{ val(item.company_name) }}</td>
                        <td>{{ val(item.position_title) }}</td>
                        <td>{{ val(item.inclusive_date_from) }} – {{ val(item.inclusive_date_to) }}</td>
                        <td>{{ val(item.employment_status) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="ehris-muted">No work experience on file.</p>
    </section>
</template>
