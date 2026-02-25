<script setup lang="ts">
import { Calendar, GraduationCap, Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

defineProps<{
    education?: Record<string, unknown>[];
}>();
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Education Background</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit education">
                <Pencil class="size-4" />
            </button>
        </div>
        <ul class="ehris-timeline" v-if="education && education.length">
            <li v-for="(item, i) in education" :key="i">
                <span class="ehris-timeline-dot" aria-hidden="true"></span>
                <div>
                    <p class="ehris-degree"><GraduationCap class="size-4" /><span>{{ val(item.education_level) }} – {{ val(item.school_name) }}</span></p>
                    <p>{{ val(item.course) }}</p>
                    <p class="ehris-muted"><Calendar class="size-4" /><span>{{ val(item.from_year) }} – {{ val(item.to_year) }} ({{ val(item.year_graduated) }})</span></p>
                </div>
            </li>
        </ul>
        <p v-else class="ehris-muted">No education records on file.</p>
    </section>
</template>
