<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, GraduationCap } from 'lucide-vue-next';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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

/** Display order: Graduate Studies, College, Vocational, Secondary, Elementary. */
const DISPLAY_ORDER = [
    'GRADUATE STUDIES',
    'COLLEGE',
    'VOCATIONAL / TRADE COURSE',
    'SECONDARY',
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

function parseYear(item: Record<string, unknown>): number {
    const y = item?.year_graduated ?? item?.to_year ?? item?.from_year;
    const n = typeof y === 'number' ? y : parseInt(String(y ?? ''), 10);
    return Number.isFinite(n) ? n : 0;
}

const props = defineProps<{
    education?: Record<string, unknown>[];
    educationUpdateUrl?: string;
}>();

function displayVal(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

function val(v: unknown): string {
    if (v == null || v === '') return '';
    return String(v).trim();
}

const hasEducation = computed(() => props.education && props.education.length > 0);
const canEdit = computed(() => Boolean(props.educationUpdateUrl));

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

function isRowFilled(row: EducationRow): boolean {
    return (
        val(row.school_name) !== ''
        || val(row.course) !== ''
        || val(row.from_year) !== ''
        || val(row.to_year) !== ''
        || val(row.year_graduated) !== ''
        || val(row.highest_grade) !== ''
        || val(row.scholarship) !== ''
    );
}

const editModalOpen = ref(false);
const processing = ref(false);
const errors = ref<Record<string, string>>({});

const elementaryForm = ref<EducationRow>(emptyRow('ELEMENTARY'));
const secondaryForm = ref<EducationRow>(emptyRow('SECONDARY'));
const vocationalForm = ref<EducationRow>(emptyRow('VOCATIONAL / TRADE COURSE'));
const collegeForm = ref<EducationRow[]>([emptyRow('COLLEGE')]);
const graduateForm = ref<EducationRow[]>([emptyRow('GRADUATE STUDIES')]);

function parseEducationToForms(edu: Record<string, unknown>[] | undefined): void {
    elementaryForm.value = emptyRow('ELEMENTARY');
    secondaryForm.value = emptyRow('SECONDARY');
    vocationalForm.value = emptyRow('VOCATIONAL / TRADE COURSE');
    collegeForm.value = [emptyRow('COLLEGE')];
    graduateForm.value = [emptyRow('GRADUATE STUDIES')];

    if (!edu || edu.length === 0) return;

    const colleges: EducationRow[] = [];
    const grads: EducationRow[] = [];

    for (const item of edu) {
        const level = normalizeLevel(val((item as any)?.education_level));
        const row: EducationRow = {
            education_level: level,
            school_name: val((item as any)?.school_name),
            course: val((item as any)?.course),
            from_year: val((item as any)?.from_year),
            to_year: val((item as any)?.to_year),
            year_graduated: val((item as any)?.year_graduated),
            highest_grade: val((item as any)?.highest_grade),
            scholarship: val((item as any)?.scholarship),
        };

        if (level === 'ELEMENTARY') {
            elementaryForm.value = row;
        } else if (level === 'SECONDARY') {
            secondaryForm.value = row;
        } else if (level === 'VOCATIONAL / TRADE COURSE') {
            vocationalForm.value = row;
        } else if (level === 'COLLEGE') {
            colleges.push({ ...row, education_level: 'COLLEGE' });
        } else if (level === 'GRADUATE STUDIES') {
            grads.push({ ...row, education_level: 'GRADUATE STUDIES' });
        }
    }

    if (colleges.length > 0) {
        collegeForm.value = colleges.sort((a, b) => parseYear(b) - parseYear(a));
    }
    if (grads.length > 0) {
        graduateForm.value = grads.sort((a, b) => parseYear(b) - parseYear(a));
    }
}

function openEdit(): void {
    parseEducationToForms(props.education);
    errors.value = {};
    editModalOpen.value = true;
}

function addCollege(): void {
    collegeForm.value = [...collegeForm.value, emptyRow('COLLEGE')];
}

function removeCollege(index: number): void {
    if (collegeForm.value.length <= 1) return;
    collegeForm.value = collegeForm.value.filter((_, idx) => idx !== index);
}

function addGraduate(): void {
    graduateForm.value = [...graduateForm.value, emptyRow('GRADUATE STUDIES')];
}

function removeGraduate(index: number): void {
    if (graduateForm.value.length <= 1) return;
    graduateForm.value = graduateForm.value.filter((_, idx) => idx !== index);
}

function buildPayload(): EducationRow[] {
    const payload: EducationRow[] = [];

    for (const r of graduateForm.value) {
        if (isRowFilled(r)) payload.push({ ...r });
    }
    for (const r of collegeForm.value) {
        if (isRowFilled(r)) payload.push({ ...r });
    }
    for (const r of [vocationalForm.value, secondaryForm.value, elementaryForm.value]) {
        if (isRowFilled(r)) payload.push({ ...r });
    }

    return payload;
}

function submit(): void {
    if (!props.educationUpdateUrl) return;
    processing.value = true;
    errors.value = {};

    router.post(
        props.educationUpdateUrl,
        { education: buildPayload() } as { education: EducationRow[] },
        {
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
        },
    );
}

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
        if (idxA !== idxB) return idxA - idxB;
        // Same level: for College (and others) put latest year first
        const yearA = parseYear(a as Record<string, unknown>);
        const yearB = parseYear(b as Record<string, unknown>);
        return yearB - yearA;
    });
});
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>III. Educational Background</h3>
            <button
                v-if="canEdit"
                type="button"
                class="ehris-edit-btn"
                aria-label="Edit education background"
                @click="openEdit"
            >
                <Pencil class="size-4" />
            </button>
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

        <Dialog :open="editModalOpen" @update:open="(v) => { editModalOpen = v; }">
            <DialogContent :show-close-button="true" class="ehris-education-dialog sm:max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                <DialogHeader>
                    <DialogTitle>III. Educational Background</DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submit" class="ehris-education-form flex flex-col min-h-0 flex-1">
                    <div v-if="Object.keys(errors).length" class="ehris-family-errors shrink-0">
                        <p v-for="(msg, key) in errors" :key="key">{{ msg }}</p>
                    </div>

                    <div class="ehris-education-scroll flex-1 min-h-0 overflow-y-auto pr-2 space-y-6">
                        <section class="ehris-edu-section">
                            <div class="ehris-edu-section-header">
                                <h4 class="ehris-edu-section-title">Graduate Studies</h4>
                                <Button type="button" variant="outline" size="sm" @click="addGraduate">
                                    <Plus class="size-4 mr-1" />
                                    Add
                                </Button>
                            </div>

                            <div v-for="(row, idx) in graduateForm" :key="`grad-${idx}`" class="ehris-edu-entry">
                                <div class="ehris-edu-entry-header">
                                    <span class="ehris-edu-entry-label">Entry {{ idx + 1 }}</span>
                                    <Button type="button" variant="ghost" size="sm" :disabled="graduateForm.length <= 1" @click="removeGraduate(idx)">
                                        <Trash2 class="size-4 mr-1" />
                                        Remove
                                    </Button>
                                </div>
                                <div class="ehris-edu-fields">
                                    <div class="ehris-edu-field">
                                        <label class="ehris-edu-label">School</label>
                                        <Input v-model="row.school_name" type="text" class="ehris-edu-input" />
                                    </div>
                                    <div class="ehris-edu-field">
                                        <label class="ehris-edu-label">Degree / Course</label>
                                        <Input v-model="row.course" type="text" class="ehris-edu-input" />
                                    </div>
                                    <div class="ehris-edu-field-row">
                                        <div class="ehris-edu-field ehris-edu-field-period">
                                            <label class="ehris-edu-label">From</label>
                                            <Input
                                                :model-value="row.from_year"
                                                type="text"
                                                class="ehris-edu-input"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                maxlength="4"
                                                @update:modelValue="(v) => { row.from_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                            />
                                        </div>
                                        <div class="ehris-edu-field ehris-edu-field-period">
                                            <label class="ehris-edu-label">To</label>
                                            <Input
                                                :model-value="row.to_year"
                                                type="text"
                                                class="ehris-edu-input"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                maxlength="4"
                                                @update:modelValue="(v) => { row.to_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                            />
                                        </div>
                                    </div>
                                    <div class="ehris-edu-field ehris-edu-field-inline">
                                        <label class="ehris-edu-label">Year Graduated</label>
                                        <Input
                                            :model-value="row.year_graduated"
                                            type="text"
                                            class="ehris-edu-input ehris-edu-input-year"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="4"
                                            @update:modelValue="(v) => { row.year_graduated = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                        />
                                    </div>
                                    <div class="ehris-edu-field">
                                        <label class="ehris-edu-label">Highest level / units</label>
                                        <Input v-model="row.highest_grade" type="text" class="ehris-edu-input" />
                                    </div>
                                    <div class="ehris-edu-field">
                                        <label class="ehris-edu-label">Scholarship / Honors</label>
                                        <Input v-model="row.scholarship" type="text" class="ehris-edu-input" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="ehris-edu-section">
                            <div class="ehris-edu-section-header">
                                <h4 class="ehris-edu-section-title">College</h4>
                                <Button type="button" variant="outline" size="sm" @click="addCollege">
                                    <Plus class="size-4 mr-1" />
                                    Add
                                </Button>
                            </div>

                            <div v-for="(row, idx) in collegeForm" :key="`college-${idx}`" class="ehris-edu-entry">
                                <div class="ehris-edu-entry-header">
                                    <span class="ehris-edu-entry-label">Entry {{ idx + 1 }}</span>
                                    <Button type="button" variant="ghost" size="sm" :disabled="collegeForm.length <= 1" @click="removeCollege(idx)">
                                        <Trash2 class="size-4 mr-1" />
                                        Remove
                                    </Button>
                                </div>
                                <div class="ehris-edu-fields">
                                    <div class="ehris-edu-field">
                                        <label class="ehris-edu-label">School</label>
                                        <Input v-model="row.school_name" type="text" class="ehris-edu-input" />
                                    </div>
                                    <div class="ehris-edu-field">
                                        <label class="ehris-edu-label">Degree / Course</label>
                                        <Input v-model="row.course" type="text" class="ehris-edu-input" />
                                    </div>
                                    <div class="ehris-edu-field-row">
                                        <div class="ehris-edu-field ehris-edu-field-period">
                                            <label class="ehris-edu-label">From</label>
                                            <Input
                                                :model-value="row.from_year"
                                                type="text"
                                                class="ehris-edu-input"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                maxlength="4"
                                                @update:modelValue="(v) => { row.from_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                            />
                                        </div>
                                        <div class="ehris-edu-field ehris-edu-field-period">
                                            <label class="ehris-edu-label">To</label>
                                            <Input
                                                :model-value="row.to_year"
                                                type="text"
                                                class="ehris-edu-input"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                maxlength="4"
                                                @update:modelValue="(v) => { row.to_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                            />
                                        </div>
                                    </div>
                                    <div class="ehris-edu-field ehris-edu-field-inline">
                                        <label class="ehris-edu-label">Year Graduated</label>
                                        <Input
                                            :model-value="row.year_graduated"
                                            type="text"
                                            class="ehris-edu-input ehris-edu-input-year"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="4"
                                            @update:modelValue="(v) => { row.year_graduated = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                        />
                                    </div>
                                    <div class="ehris-edu-field">
                                        <label class="ehris-edu-label">Highest level / units</label>
                                        <Input v-model="row.highest_grade" type="text" class="ehris-edu-input" />
                                    </div>
                                    <div class="ehris-edu-field">
                                        <label class="ehris-edu-label">Scholarship / Honors</label>
                                        <Input v-model="row.scholarship" type="text" class="ehris-edu-input" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="ehris-edu-section">
                            <h4 class="ehris-edu-section-title">Vocational / Trade Course</h4>
                            <div class="ehris-edu-fields">
                                <div class="ehris-edu-field">
                                    <label class="ehris-edu-label">School</label>
                                    <Input v-model="vocationalForm.school_name" type="text" class="ehris-edu-input" />
                                </div>
                                <div class="ehris-edu-field">
                                    <label class="ehris-edu-label">Course</label>
                                    <Input v-model="vocationalForm.course" type="text" class="ehris-edu-input" />
                                </div>
                                <div class="ehris-edu-field-row">
                                    <div class="ehris-edu-field ehris-edu-field-period">
                                        <label class="ehris-edu-label">From</label>
                                        <Input
                                            :model-value="vocationalForm.from_year"
                                            type="text"
                                            class="ehris-edu-input"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="4"
                                            @update:modelValue="(v) => { vocationalForm.from_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                        />
                                    </div>
                                    <div class="ehris-edu-field ehris-edu-field-period">
                                        <label class="ehris-edu-label">To</label>
                                        <Input
                                            :model-value="vocationalForm.to_year"
                                            type="text"
                                            class="ehris-edu-input"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="4"
                                            @update:modelValue="(v) => { vocationalForm.to_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                        />
                                    </div>
                                </div>
                                <div class="ehris-edu-field ehris-edu-field-inline">
                                    <label class="ehris-edu-label">Year Graduated</label>
                                    <Input
                                        :model-value="vocationalForm.year_graduated"
                                        type="text"
                                        class="ehris-edu-input ehris-edu-input-year"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="4"
                                        @update:modelValue="(v) => { vocationalForm.year_graduated = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="ehris-edu-section">
                            <h4 class="ehris-edu-section-title">Secondary</h4>
                            <div class="ehris-edu-fields">
                                <div class="ehris-edu-field">
                                    <label class="ehris-edu-label">School</label>
                                    <Input v-model="secondaryForm.school_name" type="text" class="ehris-edu-input" />
                                </div>
                                <div class="ehris-edu-field-row">
                                    <div class="ehris-edu-field ehris-edu-field-period">
                                        <label class="ehris-edu-label">From</label>
                                        <Input
                                            :model-value="secondaryForm.from_year"
                                            type="text"
                                            class="ehris-edu-input"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="4"
                                            @update:modelValue="(v) => { secondaryForm.from_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                        />
                                    </div>
                                    <div class="ehris-edu-field ehris-edu-field-period">
                                        <label class="ehris-edu-label">To</label>
                                        <Input
                                            :model-value="secondaryForm.to_year"
                                            type="text"
                                            class="ehris-edu-input"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="4"
                                            @update:modelValue="(v) => { secondaryForm.to_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                        />
                                    </div>
                                </div>
                                <div class="ehris-edu-field ehris-edu-field-inline">
                                    <label class="ehris-edu-label">Year Graduated</label>
                                    <Input
                                        :model-value="secondaryForm.year_graduated"
                                        type="text"
                                        class="ehris-edu-input ehris-edu-input-year"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="4"
                                        @update:modelValue="(v) => { secondaryForm.year_graduated = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="ehris-edu-section">
                            <h4 class="ehris-edu-section-title">Elementary</h4>
                            <div class="ehris-edu-fields">
                                <div class="ehris-edu-field">
                                    <label class="ehris-edu-label">School</label>
                                    <Input v-model="elementaryForm.school_name" type="text" class="ehris-edu-input" />
                                </div>
                                <div class="ehris-edu-field-row">
                                    <div class="ehris-edu-field ehris-edu-field-period">
                                        <label class="ehris-edu-label">From</label>
                                        <Input
                                            :model-value="elementaryForm.from_year"
                                            type="text"
                                            class="ehris-edu-input"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="4"
                                            @update:modelValue="(v) => { elementaryForm.from_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                        />
                                    </div>
                                    <div class="ehris-edu-field ehris-edu-field-period">
                                        <label class="ehris-edu-label">To</label>
                                        <Input
                                            :model-value="elementaryForm.to_year"
                                            type="text"
                                            class="ehris-edu-input"
                                            inputmode="numeric"
                                            pattern="[0-9]*"
                                            maxlength="4"
                                            @update:modelValue="(v) => { elementaryForm.to_year = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                        />
                                    </div>
                                </div>
                                <div class="ehris-edu-field ehris-edu-field-inline">
                                    <label class="ehris-edu-label">Year Graduated</label>
                                    <Input
                                        :model-value="elementaryForm.year_graduated"
                                        type="text"
                                        class="ehris-edu-input ehris-edu-input-year"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        maxlength="4"
                                        @update:modelValue="(v) => { elementaryForm.year_graduated = String(v ?? '').replace(/\\D+/g, '').slice(0, 4); }"
                                    />
                                </div>
                            </div>
                        </section>
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
.ehris-education-scroll {
    padding-right: 0.5rem;
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

.ehris-edu-section {
    padding: 1rem 1.25rem;
    background: hsl(var(--muted) / 0.35);
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
}
.ehris-edu-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.ehris-edu-section-title {
    font-size: 0.9375rem;
    font-weight: 600;
    margin: 0;
    color: hsl(var(--foreground));
}
.ehris-edu-entry {
    padding: 0.75rem;
    border: 1px dashed hsl(var(--border));
    border-radius: 0.5rem;
    background: hsl(var(--background));
    margin-top: 0.75rem;
}
.ehris-edu-entry-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}
.ehris-edu-entry-label {
    font-size: 0.875rem;
    font-weight: 600;
}
.ehris-edu-fields {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}
.ehris-edu-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
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

@media (max-width: 768px) {
    .ehris-education-dialog {
        width: calc(100vw - 1rem);
        max-width: calc(100vw - 1rem);
        max-height: calc(100dvh - 1rem);
        padding: 0.875rem;
    }

    .ehris-education-scroll {
        padding-right: 0;
    }

    .ehris-edu-section {
        padding: 0.625rem;
    }

    .ehris-edu-section-header,
    .ehris-edu-entry-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .ehris-edu-field-row {
        flex-direction: column;
        gap: 0.625rem;
    }

    .ehris-edu-field-period,
    .ehris-edu-field-inline,
    .ehris-edu-input-year {
        max-width: 100%;
        width: 100%;
    }
}
</style>
