<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type VoluntaryWorkRow = {
    name_address_org: string;
    inclusive_date_from: string;
    inclusive_date_to: string;
    number_hours: string;
    position_nature_of_work: string;
};

type VoluntaryWorkItem = Record<string, unknown>;

const props = defineProps<{
    voluntaryWork?: VoluntaryWorkItem[];
    voluntaryWorkUpdateUrl?: string;
}>();

const canEdit = computed(() => Boolean(props.voluntaryWorkUpdateUrl));
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const formRows = ref<VoluntaryWorkRow[]>([]);

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

function pick(item: VoluntaryWorkItem, keys: string[]): unknown {
    for (const key of keys) {
        const value = item[key];
        if (value != null && String(value).trim() !== '') {
            return value;
        }
    }

    return null;
}

function emptyRow(): VoluntaryWorkRow {
    return {
        name_address_org: '',
        inclusive_date_from: '',
        inclusive_date_to: '',
        number_hours: '',
        position_nature_of_work: '',
    };
}

function isRowFilled(row: VoluntaryWorkRow): boolean {
    return (
        row.name_address_org.trim() !== ''
        || row.inclusive_date_from.trim() !== ''
        || row.inclusive_date_to.trim() !== ''
        || row.number_hours.trim() !== ''
        || row.position_nature_of_work.trim() !== ''
    );
}

function syncRows(): void {
    const rows = (props.voluntaryWork ?? []).map((item) => ({
        name_address_org: val(pick(item, ['name_address_org', 'organization', 'org_name', 'name_of_organization', 'affiliation'])),
        inclusive_date_from: val(pick(item, ['inclusive_date_from', 'date_from', 'start_date'])),
        inclusive_date_to: val(pick(item, ['inclusive_date_to', 'date_to', 'end_date'])),
        number_hours: val(pick(item, ['number_hours', 'hours', 'no_of_hours'])),
        position_nature_of_work: val(pick(item, ['position_nature_of_work', 'position', 'nature_of_work', 'position_title'])),
    }));

    if (rows.length === 0 && canEdit.value) {
        formRows.value = [emptyRow()];
        return;
    }

    formRows.value = rows;
}

watch(
    () => [props.voluntaryWork, canEdit.value] as const,
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
    if (!props.voluntaryWorkUpdateUrl) {
        return;
    }

    processing.value = true;
    errors.value = {};

    const payload = formRows.value
        .filter((row) => isRowFilled(row))
        .map((row) => ({ ...row }));

    router.post(
        props.voluntaryWorkUpdateUrl,
        { voluntaryWork: payload } as { voluntaryWork: VoluntaryWorkRow[] },
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
            <h3>Voluntary Work / Civic Involvement</h3>
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
                                <th>Organization</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Hours</th>
                                <th>Position / Nature of work</th>
                                <th v-if="canEdit" class="w-[68px] text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in formRows" :key="`voluntary-work-row-${i}`">
                                <td>
                                    <Input v-if="canEdit" v-model="item.name_address_org" />
                                    <span v-else>{{ displayVal(item.name_address_org) }}</span>
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
                                    <Input v-if="canEdit" v-model="item.number_hours" />
                                    <span v-else>{{ displayVal(item.number_hours) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEdit" v-model="item.position_nature_of_work" />
                                    <span v-else>{{ displayVal(item.position_nature_of_work) }}</span>
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
                    :key="`voluntary-work-mobile-${i}`"
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
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Organization</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.name_address_org" />
                                <span v-else class="wrap-break-word text-sm text-foreground">{{ displayVal(item.name_address_org) }}</span>
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
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Hours</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.number_hours" />
                                <span v-else class="wrap-break-word text-sm text-foreground">{{ displayVal(item.number_hours) }}</span>
                            </dd>
                        </div>
                        <div class="grid grid-cols-[108px_1fr] items-center gap-2">
                            <dt class="text-[11px] font-semibold uppercase text-muted-foreground">Position / Nature</dt>
                            <dd>
                                <Input v-if="canEdit" v-model="item.position_nature_of_work" />
                                <span v-else class="wrap-break-word text-sm text-foreground">{{ displayVal(item.position_nature_of_work) }}</span>
                            </dd>
                        </div>
                    </dl>
                </article>
            </div>
        </div>
        <p v-else class="ehris-muted">No voluntary work records on file.</p>
    </section>
</template>
