<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

defineProps<{
    affiliation?: Record<string, unknown>[];
}>();
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Affiliation</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit affiliation">
                <Pencil class="size-4" />
            </button>
        </div>
        <ul class="ehris-stacked-list" v-if="affiliation && affiliation.length">
            <li v-for="(item, i) in affiliation" :key="i">
                {{ val(item.affiliation) }}
            </li>
        </ul>
        <p v-else class="ehris-muted">No affiliation on file.</p>
    </section>
</template>
