<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type EligibilityRow = {
    title: string;
    rating: string;
    date_exam: string;
    place_exam: string;
    license_no: string;
};

const props = defineProps<{
    eligibility?: Record<string, unknown>[];
    eligibilityUpdateUrl?: string;
}>();

const canEdit = computed(() => Boolean(props.eligibilityUpdateUrl));
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const formRows = ref<EligibilityRow[]>([]);

function val(v: unknown): string {
    if (v == null || String(v).trim() === '') {
        return '';
    }

    return String(v);
}

function displayVal(v: unknown): string {
    const value = val(v);
    return value === '' ? '—' : value;
}

function emptyRow(): EligibilityRow {
    return {
        title: '',
        rating: '',
        date_exam: '',
        place_exam: '',
        license_no: '',
    };
}

function isRowFilled(row: EligibilityRow): boolean {
    return (
        row.title.trim() !== ''
        || row.rating.trim() !== ''
        || row.date_exam.trim() !== ''
        || row.place_exam.trim() !== ''
        || row.license_no.trim() !== ''
    );
}

function syncRows(): void {
    const rows = (props.eligibility ?? []).map((item) => ({
        title: val(item.title),
        rating: val(item.rating),
        date_exam: val(item.date_exam),
        place_exam: val(item.place_exam),
        license_no: val(item.license_no),
    }));

    if (rows.length === 0 && canEdit.value) {
        formRows.value = [emptyRow()];
        return;
    }

    formRows.value = rows;
}

watch(
    () => [props.eligibility, canEdit.value] as const,
    () => {
        syncRows();
    },
    { immediate: true },
);

function addRow(): void {
    formRows.value = [...formRows.value, emptyRow()];
}

function removeRow(index: number): void {
    if (formRows.value.length <= 1) {
        formRows.value = [emptyRow()];
        return;
    }

    formRows.value = formRows.value.filter((_, idx) => idx !== index);
}

function submit(): void {
    if (!props.eligibilityUpdateUrl) {
        return;
    }

    processing.value = true;
    errors.value = {};

    const payload = formRows.value
        .filter((row) => isRowFilled(row))
        .map((row) => ({ ...row }));

    router.post(
        props.eligibilityUpdateUrl,
        { eligibility: payload } as { eligibility: EligibilityRow[] },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
            onError: (errs) => {
                errors.value = (errs as Record<string, string>) || {};
            },
        },
    );
}
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Eligibility (Civil service)</h3>
            <div v-if="canEdit" class="flex items-center gap-2">
                <Button type="button" size="sm" variant="outline" @click="addRow">
                    <Plus class="mr-1 size-4" />
                    Add row
                </Button>
                <Button type="button" size="sm" :disabled="processing" @click="submit">
                    <Spinner v-if="processing" class="mr-2 size-4" />
                    Save
                </Button>
            </div>
        </div>

        <p v-if="errors.message" class="ehris-form-error mb-3">{{ errors.message }}</p>

        <div v-if="formRows.length > 0">
            <div class="hidden md:block">
                <div class="ehris-table-wrap">
                    <table class="ehris-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Rating</th>
                                <th>Date of exam</th>
                                <th>Place</th>
                                <th>License no.</th>
                                <th v-if="canEdit" class="w-[68px] text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in formRows" :key="`eligibility-row-${i}`">
                                <td>
                                    <Input v-if="canEdit" v-model="item.title" />
                                    <span v-else>{{ displayVal(item.title) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEdit" v-model="item.rating" />
                                    <span v-else>{{ displayVal(item.rating) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEdit" v-model="item.date_exam" type="date" />
                                    <span v-else>{{ displayVal(item.date_exam) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEdit" v-model="item.place_exam" />
                                    <span v-else>{{ displayVal(item.place_exam) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEdit" v-model="item.license_no" />
                                    <span v-else>{{ displayVal(item.license_no) }}</span>
                                </td>
                                <td v-if="canEdit" class="text-center">
                                    <Button type="button" variant="ghost" size="icon" @click="removeRow(i)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid gap-3 md:hidden">
                <article
                    v-for="(item, i) in formRows"
                    :key="`eligibility-mobile-${i}`"
                    class="rounded-xl border border-border bg-card p-3"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            Record {{ i + 1 }}
                        </p>
                        <Button
                            v-if="canEdit"
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-7"
                            @click="removeRow(i)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                    <dl class="space-y-2">
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Title</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.title" />
                                <span v-else class="text-sm text-foreground wrap-break-word">{{ displayVal(item.title) }}</span>
                            </dd>
                        </div>
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Rating</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.rating" />
                                <span v-else class="text-sm text-foreground wrap-break-word">{{ displayVal(item.rating) }}</span>
                            </dd>
                        </div>
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Date of exam</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.date_exam" type="date" />
                                <span v-else class="text-sm text-foreground wrap-break-word">{{ displayVal(item.date_exam) }}</span>
                            </dd>
                        </div>
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Place</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.place_exam" />
                                <span v-else class="text-sm text-foreground wrap-break-word">{{ displayVal(item.place_exam) }}</span>
                            </dd>
                        </div>
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">License no.</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.license_no" />
                                <span v-else class="text-sm text-foreground wrap-break-word">{{ displayVal(item.license_no) }}</span>
                            </dd>
                        </div>
                    </dl>
                </article>
            </div>
        </div>
        <p v-else class="ehris-muted">No eligibility records on file.</p>
    </section>
</template>
