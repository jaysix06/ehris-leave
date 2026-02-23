<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

defineProps<{
    eligibility?: Record<string, unknown>[];
}>();
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Eligibility (Civil service)</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit eligibility">
                <Pencil class="size-4" />
            </button>
        </div>
        <div class="ehris-table-wrap" v-if="eligibility && eligibility.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Rating</th>
                        <th>Date of exam</th>
                        <th>Place</th>
                        <th>License no.</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in eligibility" :key="i">
                        <td>{{ val(item.title) }}</td>
                        <td>{{ val(item.rating) }}</td>
                        <td>{{ val(item.date_exam) }}</td>
                        <td>{{ val(item.place_exam) }}</td>
                        <td>{{ val(item.license_no) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="ehris-muted">No eligibility records on file.</p>
    </section>
</template>
