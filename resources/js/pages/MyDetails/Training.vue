<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

defineProps<{
    training?: Record<string, unknown>[];
}>();
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Training</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit training">
                <Pencil class="size-4" />
            </button>
        </div>
        <div class="ehris-table-wrap" v-if="training && training.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Venue</th>
                        <th>Start – End</th>
                        <th>Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in training" :key="i">
                        <td>{{ val(item.training_title) }}</td>
                        <td>{{ val(item.training_venue) }}</td>
                        <td>{{ val(item.start_date) }} – {{ val(item.end_date) }}</td>
                        <td>{{ val(item.number_hours) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="ehris-muted">No training on file.</p>
    </section>
</template>
