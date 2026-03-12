<script setup lang="ts">
import { Calendar, GraduationCap } from 'lucide-vue-next';
import { computed } from 'vue';

/** Display order: Secondary, Vocational, College, Graduate Studies, Elementary last. */
const DISPLAY_ORDER = [
    'SECONDARY',
    'VOCATIONAL / TRADE COURSE',
    'COLLEGE',
    'GRADUATE STUDIES',
    'ELEMENTARY',
] as const;

function normalizeLevel(level: string): string {
    const l = String(level ?? '').toUpperCase();
    if (l.includes('ELEMENTARY')) return 'ELEMENTARY';
    if (l.includes('SECONDARY') || l.includes('HIGH SCHOOL')) return 'SECONDARY';
    if (l.includes('VOCATIONAL') || l.includes('TRADE')) return 'VOCATIONAL / TRADE COURSE';
    if (l.includes('GRADUATE') || l.includes('MASTER') || l.includes('MASTERAL')) return 'GRADUATE STUDIES';
    if (l.includes('COLLEGE')) return 'COLLEGE';
    return l || 'COLLEGE';
}

const props = defineProps<{
    education?: Record<string, unknown>[];
    educationUpdateUrl?: string;
}>();

function displayVal(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

const hasEducation = computed(() => props.education && props.education.length > 0);

const sortedEducation = computed(() => {
    const edu = props.education;
    if (!edu || !edu.length) return [];
    const order = [...DISPLAY_ORDER];
    return [...edu].sort((a, b) => {
        const levelA = normalizeLevel(String(a?.education_level ?? ''));
        const levelB = normalizeLevel(String(b?.education_level ?? ''));
        const iA = order.indexOf(levelA as (typeof order)[number]);
        const iB = order.indexOf(levelB as (typeof order)[number]);
        const idxA = iA >= 0 ? iA : order.length;
        const idxB = iB >= 0 ? iB : order.length;
        return idxA - idxB;
    });
});
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>III. Educational Background</h3>
        </div>

        <template v-if="hasEducation">
            <div class="ehris-edu-view">
                <ul class="ehris-timeline">
                    <li v-for="(item, i) in sortedEducation" :key="i">
                        <span class="ehris-timeline-dot" aria-hidden="true" />
                        <div>
                            <p class="ehris-degree">
                                <GraduationCap class="size-4" />
                                <span>{{ displayVal(item.education_level) }} – {{ displayVal(item.school_name) }}</span>
                            </p>
                            <p>{{ displayVal(item.course) }}</p>
                            <p class="ehris-muted">
                                <Calendar class="size-4" />
                                <span>{{ displayVal(item.from_year) }} – {{ displayVal(item.to_year) }} ({{ displayVal(item.year_graduated) }})</span>
                            </p>
                            <p v-if="displayVal(item.scholarship) !== '—'" class="ehris-muted">
                                Scholarship / Honors: {{ displayVal(item.scholarship) }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </template>
        <p v-else class="ehris-muted">No education records on file.</p>
    </section>
</template>

<style scoped>
.ehris-edu-view {
    width: 100%;
}
.ehris-degree {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.ehris-muted {
    color: hsl(var(--muted-foreground));
    font-size: 0.875rem;
}
</style>
