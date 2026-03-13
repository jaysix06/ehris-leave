<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type WorkExperienceRow = {
    company_name: string;
    position_title: string;
    inclusive_date_from: string;
    inclusive_date_to: string;
    employment_status: string;
};

const props = defineProps<{
    workExperience?: Record<string, unknown>[];
    workExperienceUpdateUrl?: string;
}>();

const canEdit = computed(() => Boolean(props.workExperienceUpdateUrl));
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const formRows = ref<WorkExperienceRow[]>([]);

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

function emptyRow(): WorkExperienceRow {
    return {
        company_name: '',
        position_title: '',
        inclusive_date_from: '',
        inclusive_date_to: '',
        employment_status: '',
    };
}

function isRowFilled(row: WorkExperienceRow): boolean {
    return (
        row.company_name.trim() !== ''
        || row.position_title.trim() !== ''
        || row.inclusive_date_from.trim() !== ''
        || row.inclusive_date_to.trim() !== ''
        || row.employment_status.trim() !== ''
    );
}

function syncRows(): void {
    const rows = (props.workExperience ?? []).map((item) => ({
        company_name: val(item.company_name),
        position_title: val(item.position_title),
        inclusive_date_from: val(item.inclusive_date_from),
        inclusive_date_to: val(item.inclusive_date_to),
        employment_status: val(item.employment_status),
    }));

    if (rows.length === 0 && canEdit.value) {
        formRows.value = [emptyRow()];
        return;
    }

    formRows.value = rows;
}

watch(
    () => [props.workExperience, canEdit.value] as const,
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
    if (!props.workExperienceUpdateUrl) {
        return;
    }

    processing.value = true;
    errors.value = {};

    const payload = formRows.value
        .filter((row) => isRowFilled(row))
        .map((row) => ({ ...row }));

    router.post(
        props.workExperienceUpdateUrl,
        { workExperience: payload } as { workExperience: WorkExperienceRow[] },
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
            <h3>Work experience</h3>
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

        <div v-if="formRows.length">
            <div class="hidden md:block">
                <div class="ehris-table-wrap">
                    <table class="ehris-table">
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Position</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Status</th>
                                <th v-if="canEdit" class="w-[68px] text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in formRows" :key="`work-exp-row-${i}`">
                                <td>
                                    <Input v-if="canEdit" v-model="item.company_name" />
                                    <span v-else>{{ displayVal(item.company_name) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEdit" v-model="item.position_title" />
                                    <span v-else>{{ displayVal(item.position_title) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEdit" v-model="item.inclusive_date_from" type="date" />
                                    <span v-else>{{ displayVal(item.inclusive_date_from) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEdit" v-model="item.inclusive_date_to" type="date" />
                                    <span v-else>{{ displayVal(item.inclusive_date_to) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEdit" v-model="item.employment_status" />
                                    <span v-else>{{ displayVal(item.employment_status) }}</span>
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
                    :key="`work-exp-mobile-${i}`"
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
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Company</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.company_name" />
                                <span v-else class="wrap-break-word text-sm text-foreground">{{ displayVal(item.company_name) }}</span>
                            </dd>
                        </div>
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Position</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.position_title" />
                                <span v-else class="wrap-break-word text-sm text-foreground">{{ displayVal(item.position_title) }}</span>
                            </dd>
                        </div>
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">From</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.inclusive_date_from" type="date" />
                                <span v-else class="wrap-break-word text-sm text-foreground">{{ displayVal(item.inclusive_date_from) }}</span>
                            </dd>
                        </div>
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">To</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.inclusive_date_to" type="date" />
                                <span v-else class="wrap-break-word text-sm text-foreground">{{ displayVal(item.inclusive_date_to) }}</span>
                            </dd>
                        </div>
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Status</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.employment_status" />
                                <span v-else class="wrap-break-word text-sm text-foreground">{{ displayVal(item.employment_status) }}</span>
                            </dd>
                        </div>
                    </dl>
                </article>
            </div>
        </div>
        <p v-else class="ehris-muted">No work experience on file.</p>
    </section>
</template>
