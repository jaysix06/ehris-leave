<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type TrainingRow = {
    training_title: string;
    training_venue: string;
    start_date: string;
    end_date: string;
    number_hours: string;
};

const props = defineProps<{
    training?: Record<string, unknown>[];
    trainingUpdateUrl?: string;
}>();

const canEdit = computed(() => Boolean(props.trainingUpdateUrl));
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const formRows = ref<TrainingRow[]>([]);

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

function emptyRow(): TrainingRow {
    return {
        training_title: '',
        training_venue: '',
        start_date: '',
        end_date: '',
        number_hours: '',
    };
}

function isRowFilled(row: TrainingRow): boolean {
    return (
        row.training_title.trim() !== ''
        || row.training_venue.trim() !== ''
        || row.start_date.trim() !== ''
        || row.end_date.trim() !== ''
        || row.number_hours.trim() !== ''
    );
}

function syncRows(): void {
    const rows = (props.training ?? []).map((item) => ({
        training_title: val(item.training_title),
        training_venue: val(item.training_venue),
        start_date: val(item.start_date ?? item.inclusive_date_from),
        end_date: val(item.end_date ?? item.inclusive_date_to),
        number_hours: val(item.number_hours ?? item.hours),
    }));

    if (rows.length === 0 && canEdit.value) {
        formRows.value = [emptyRow()];
        return;
    }

    formRows.value = rows;
}

watch(
    () => [props.training, canEdit.value] as const,
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
    if (!props.trainingUpdateUrl) {
        return;
    }

    processing.value = true;
    errors.value = {};

    const payload = formRows.value
        .filter((row) => isRowFilled(row))
        .map((row) => ({ ...row }));

    router.post(
        props.trainingUpdateUrl,
        { training: payload } as { training: TrainingRow[] },
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
            <h3>Training</h3>
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

        <div class="ehris-table-wrap" v-if="formRows.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Venue</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Hours</th>
                        <th v-if="canEdit" class="w-[68px] text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in formRows" :key="`training-row-${i}`">
                        <td>
                            <Input v-if="canEdit" v-model="item.training_title" />
                            <span v-else>{{ displayVal(item.training_title) }}</span>
                        </td>
                        <td>
                            <Input v-if="canEdit" v-model="item.training_venue" />
                            <span v-else>{{ displayVal(item.training_venue) }}</span>
                        </td>
                        <td>
                            <Input v-if="canEdit" v-model="item.start_date" type="date" />
                            <span v-else>{{ displayVal(item.start_date) }}</span>
                        </td>
                        <td>
                            <Input v-if="canEdit" v-model="item.end_date" type="date" />
                            <span v-else>{{ displayVal(item.end_date) }}</span>
                        </td>
                        <td>
                            <Input v-if="canEdit" v-model="item.number_hours" />
                            <span v-else>{{ displayVal(item.number_hours) }}</span>
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
        <p v-else class="ehris-muted">No training on file.</p>
    </section>
</template>
