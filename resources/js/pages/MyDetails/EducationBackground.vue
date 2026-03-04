<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, GraduationCap, Pencil } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

const EDUCATION_LEVELS = [
    'ELEMENTARY',
    'SECONDARY',
    'VOCATIONAL / TRADE COURSE',
    'COLLEGE',
    'GRADUATE STUDIES',
] as const;

type EducationRow = {
    education_level: string;
    school_name: string;
    course: string;
    from_year: string;
    to_year: string;
    year_graduated: string;
    highest_grade: string;
    scholarship: string;
};

function val(v: unknown): string {
    if (v == null || v === '') return '';
    return String(v).trim();
}

function emptyRow(level: string): EducationRow {
    return {
        education_level: level,
        school_name: '',
        course: '',
        from_year: '',
        to_year: '',
        year_graduated: '',
        highest_grade: '',
        scholarship: '',
    };
}

function normalizeLevel(level: string): string {
    const l = level.toUpperCase();
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

const editModalOpen = ref(false);
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const rows = ref<EducationRow[]>(EDUCATION_LEVELS.map((l) => emptyRow(l)));

function parseEducation(edu: Record<string, unknown>[] | undefined): void {
    const initial = EDUCATION_LEVELS.map((level) => emptyRow(level));
    if (!edu || !edu.length) {
        rows.value = initial;
        return;
    }
    for (const item of edu) {
        const level = normalizeLevel(val(item.education_level));
        const index = EDUCATION_LEVELS.indexOf(level as (typeof EDUCATION_LEVELS)[number]);
        const target = index >= 0 ? index : EDUCATION_LEVELS.indexOf('COLLEGE');
        initial[target] = {
            education_level: EDUCATION_LEVELS[target],
            school_name: val(item.school_name),
            course: val(item.course),
            from_year: val(item.from_year),
            to_year: val(item.to_year),
            year_graduated: val(item.year_graduated),
            highest_grade: val(item.highest_grade),
            scholarship: val(item.scholarship),
        };
    }
    rows.value = initial;
}

function buildPayload(): EducationRow[] {
    return rows.value.map((r) => ({ ...r }));
}

function openEdit(): void {
    parseEducation(props.education);
    errors.value = {};
    editModalOpen.value = true;
}

function cancelEdit(): void {
    editModalOpen.value = false;
}

function submit(): void {
    if (!props.educationUpdateUrl) return;
    processing.value = true;
    errors.value = {};
    const education = buildPayload();
    router.post(props.educationUpdateUrl, { education } as { education: EducationRow[] }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            editModalOpen.value = false;
        },
        onError: (errs) => {
            errors.value = (errs as Record<string, string>) || {};
        },
    });
}

function displayVal(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

const canEdit = computed(() => Boolean(props.educationUpdateUrl));
const hasEducation = computed(() => props.education && props.education.length > 0);

watch(() => props.education, (next) => {
    if (!editModalOpen.value && next && next.length > 0) {
        parseEducation(next);
    }
}, { immediate: true });

onMounted(() => {
    if (props.education && props.education.length > 0) {
        parseEducation(props.education);
    }
});
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>III. Educational Background</h3>
            <button
                v-if="canEdit"
                type="button"
                class="ehris-btn-grade-subject"
                aria-label="Edit education background"
                @click="openEdit"
            >
                <Pencil class="size-4" />
                <span>Edit</span>
            </button>
        </div>

        <!-- View mode: PDS-style table -->
        <template v-if="!editModalOpen">
            <template v-if="hasEducation">
                <div class="ehris-edu-view">
                    <ul class="ehris-timeline">
                        <li v-for="(item, i) in education" :key="i">
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
        </template>

        <!-- Edit modal: proper form with one section per level -->
        <Dialog :open="editModalOpen" @update:open="(v) => { editModalOpen = v; if (!v) cancelEdit(); }">
            <DialogContent
                :show-close-button="true"
                class="ehris-education-dialog sm:max-w-2xl max-h-[90vh] overflow-hidden flex flex-col"
            >
                <DialogHeader>
                    <DialogTitle>III. Educational Background</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="ehris-education-form flex flex-col min-h-0 flex-1">
                    <div v-if="Object.keys(errors).length" class="ehris-family-errors shrink-0">
                        <p v-for="(msg, key) in errors" :key="key">{{ msg }}</p>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto pr-2 space-y-6">
                        <p class="ehris-muted text-sm">(Continue on separate sheet if necessary)</p>

                        <div
                            v-for="row in rows"
                            :key="row.education_level"
                            class="ehris-edu-section"
                        >
                            <h4 class="ehris-edu-section-title">26. {{ row.education_level }}</h4>
                            <div class="ehris-edu-fields">
                                <div class="ehris-edu-field ehris-edu-field-full">
                                    <label class="ehris-edu-label">Name of school (write in full)</label>
                                    <Input
                                        v-model="row.school_name"
                                        type="text"
                                        class="ehris-edu-input"
                                        placeholder="e.g. Ozamiz City National High School"
                                    />
                                </div>
                                <div class="ehris-edu-field ehris-edu-field-full">
                                    <label class="ehris-edu-label">Basic education / degree / course (write in full)</label>
                                    <Input
                                        v-model="row.course"
                                        type="text"
                                        class="ehris-edu-input"
                                        placeholder="e.g. Secondary, Bachelor of Science in Information Technology"
                                    />
                                </div>
                                <div class="ehris-edu-field-row">
                                    <div class="ehris-edu-field ehris-edu-field-period">
                                        <label class="ehris-edu-label">Period of attendance — From</label>
                                        <Input
                                            v-model="row.from_year"
                                            type="text"
                                            class="ehris-edu-input"
                                            placeholder="e.g. 2011"
                                        />
                                    </div>
                                    <div class="ehris-edu-field ehris-edu-field-period">
                                        <label class="ehris-edu-label">To</label>
                                        <Input
                                            v-model="row.to_year"
                                            type="text"
                                            class="ehris-edu-input"
                                            placeholder="e.g. 2015"
                                        />
                                    </div>
                                </div>
                                <div class="ehris-edu-field ehris-edu-field-full">
                                    <label class="ehris-edu-label">Highest level / units earned (if not graduated)</label>
                                    <Input
                                        v-model="row.highest_grade"
                                        type="text"
                                        class="ehris-edu-input"
                                        placeholder="e.g. 36 units, or leave blank if graduated"
                                    />
                                </div>
                                <div class="ehris-edu-field ehris-edu-field-inline">
                                    <label class="ehris-edu-label">Year graduated</label>
                                    <Input
                                        v-model="row.year_graduated"
                                        type="text"
                                        class="ehris-edu-input ehris-edu-input-year"
                                        placeholder="e.g. 2019"
                                    />
                                </div>
                                <div class="ehris-edu-field ehris-edu-field-full">
                                    <label class="ehris-edu-label">Scholarship / academic honors received</label>
                                    <Input
                                        v-model="row.scholarship"
                                        type="text"
                                        class="ehris-edu-input"
                                        placeholder="e.g. With Honors, Dean's Lister"
                                    />
                                </div>
                            </div>
                        </div>

                        <p class="ehris-muted text-sm">(Continue on separate sheet if necessary)</p>
                    </div>
                    <DialogFooter class="mt-6 shrink-0 border-t pt-4">
                        <DialogClose as-child>
                            <Button type="button" variant="ghost" :disabled="processing">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="processing">
                            <Spinner v-if="processing" class="size-4 mr-1" />
                            Save
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </section>
</template>

<style scoped>
.ehris-edu-view {
    width: 100%;
}
.ehris-education-form {
    width: 100%;
    max-width: 100%;
}
.ehris-family-errors {
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid hsl(var(--destructive));
    background: hsl(var(--destructive) / 0.08);
    color: hsl(var(--destructive));
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

/* Edit modal: section-per-level form */
.ehris-edu-section {
    padding: 1rem 1.25rem;
    background: hsl(var(--muted) / 0.35);
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
}
.ehris-edu-section-title {
    font-size: 0.9375rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
    color: hsl(var(--foreground));
}
.ehris-edu-fields {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.ehris-edu-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}
.ehris-edu-field-full {
    width: 100%;
}
.ehris-edu-field-row {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.ehris-edu-field-period {
    flex: 1;
    min-width: 8rem;
}
.ehris-edu-field-inline {
    width: 100%;
    max-width: 10rem;
}
.ehris-edu-input-year {
    max-width: 8rem;
}
.ehris-edu-label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: hsl(var(--foreground) / 0.9);
}
.ehris-edu-input {
    width: 100%;
    min-height: 2.25rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.9375rem;
}
</style>
