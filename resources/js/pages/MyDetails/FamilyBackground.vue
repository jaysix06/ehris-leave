<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

function familyName(item: Record<string, unknown>): string {
    const parts = [item.firstname, item.middlename, item.lastname, item.extension].filter(Boolean);
    return parts.map(String).join(' ').trim() || '—';
}

type FamilyRow = {
    relationship: string;
    firstname: string;
    middlename: string;
    lastname: string;
    extension: string;
    dob: string;
    occupation: string;
    employer_name: string;
    business_add: string;
    tel_num: string;
};

function emptyRow(): FamilyRow {
    return {
        relationship: '',
        firstname: '',
        middlename: '',
        lastname: '',
        extension: '',
        dob: '',
        occupation: '',
        employer_name: '',
        business_add: '',
        tel_num: '',
    };
}

function normalizeRow(item: Record<string, unknown>): FamilyRow {
    return {
        relationship: val(item.relationship).replace('—', ''),
        firstname: val(item.firstname).replace('—', ''),
        middlename: val(item.middlename).replace('—', ''),
        lastname: val(item.lastname).replace('—', ''),
        extension: val(item.extension).replace('—', ''),
        dob: val(item.dob).replace('—', ''),
        occupation: val(item.occupation).replace('—', ''),
        employer_name: val(item.employer_name).replace('—', ''),
        business_add: val(item.business_add).replace('—', ''),
        tel_num: val(item.tel_num).replace('—', ''),
    };
}

const props = defineProps<{
    family?: Record<string, unknown>[];
    familyUpdateUrl?: string;
}>();

const isEditing = ref(false);
const formRows = ref<FamilyRow[]>([]);
const processing = ref(false);
const errors = ref<Record<string, string>>({});

function openEdit(): void {
    if (props.family && props.family.length > 0) {
        formRows.value = props.family.map((item) => normalizeRow(item));
    } else {
        formRows.value = [emptyRow()];
    }
    errors.value = {};
    isEditing.value = true;
}

function cancelEdit(): void {
    isEditing.value = false;
}

function addRow(): void {
    formRows.value = [...formRows.value, emptyRow()];
}

function removeRow(index: number): void {
    if (formRows.value.length <= 1) return;
    formRows.value = formRows.value.filter((_, i) => i !== index);
}

function submit(): void {
    if (!props.familyUpdateUrl) return;
    processing.value = true;
    errors.value = {};
    router.post(props.familyUpdateUrl, {
        family: formRows.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            isEditing.value = false;
        },
        onError: (errs) => {
            errors.value = (errs as Record<string, string>) || {};
        },
    });
}

watch(() => props.family, (next) => {
    if (!isEditing.value && next && next.length > 0) {
        formRows.value = next.map((item) => normalizeRow(item));
    }
}, { immediate: true });

const canEdit = computed(() => Boolean(props.familyUpdateUrl));
const hasFamily = computed(() => props.family && props.family.length > 0);
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Family Background</h3>
            <button
                v-if="canEdit && !isEditing"
                type="button"
                class="ehris-edit-btn"
                aria-label="Edit family"
                @click="openEdit"
            >
                <Pencil class="size-4" />
            </button>
        </div>

        <!-- View mode -->
        <template v-if="!isEditing">
            <div class="ehris-table-wrap" v-if="hasFamily">
                <table class="ehris-table">
                    <thead>
                        <tr>
                            <th>Relationship</th>
                            <th>Name</th>
                            <th>DOB</th>
                            <th>Occupation</th>
                            <th>Employer / Business</th>
                            <th>Telephone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, i) in family" :key="i">
                            <td>{{ val(item.relationship) }}</td>
                            <td>{{ familyName(item) }}</td>
                            <td>{{ val(item.dob) }}</td>
                            <td>{{ val(item.occupation) }}</td>
                            <td>{{ val(item.employer_name) }} <span v-if="item.business_add" class="ehris-muted"> – {{ val(item.business_add) }}</span></td>
                            <td>{{ val(item.tel_num) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="ehris-muted">No family information on file.</p>
        </template>

        <!-- Edit mode -->
        <template v-else>
            <form @submit.prevent="submit" class="ehris-family-edit-form">
                <div v-if="Object.keys(errors).length" class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300">
                    <p v-for="(msg, key) in errors" :key="key">{{ msg }}</p>
                </div>
                <div class="ehris-table-wrap">
                    <table class="ehris-table">
                        <thead>
                            <tr>
                                <th>Relationship</th>
                                <th>First name</th>
                                <th>Middle name</th>
                                <th>Last name</th>
                                <th>Ext.</th>
                                <th>DOB</th>
                                <th>Occupation</th>
                                <th>Employer</th>
                                <th>Business address</th>
                                <th>Tel. no.</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in formRows" :key="index">
                                <td>
                                    <Input
                                        v-model="row.relationship"
                                        type="text"
                                        :name="`family[${index}][relationship]`"
                                        placeholder="e.g. Spouse, Child"
                                        class="min-w-[100px]"
                                    />
                                </td>
                                <td>
                                    <Input
                                        v-model="row.firstname"
                                        type="text"
                                        :name="`family[${index}][firstname]`"
                                        placeholder="First name"
                                    />
                                </td>
                                <td>
                                    <Input
                                        v-model="row.middlename"
                                        type="text"
                                        :name="`family[${index}][middlename]`"
                                        placeholder="Middle name"
                                    />
                                </td>
                                <td>
                                    <Input
                                        v-model="row.lastname"
                                        type="text"
                                        :name="`family[${index}][lastname]`"
                                        placeholder="Last name"
                                    />
                                </td>
                                <td>
                                    <Input
                                        v-model="row.extension"
                                        type="text"
                                        :name="`family[${index}][extension]`"
                                        placeholder="Jr., Sr."
                                        class="w-16"
                                    />
                                </td>
                                <td>
                                    <Input
                                        v-model="row.dob"
                                        type="text"
                                        :name="`family[${index}][dob]`"
                                        placeholder="dd/mm/yyyy"
                                        class="min-w-[100px]"
                                    />
                                </td>
                                <td>
                                    <Input
                                        v-model="row.occupation"
                                        type="text"
                                        :name="`family[${index}][occupation]`"
                                        placeholder="Occupation"
                                    />
                                </td>
                                <td>
                                    <Input
                                        v-model="row.employer_name"
                                        type="text"
                                        :name="`family[${index}][employer_name]`"
                                        placeholder="Employer"
                                    />
                                </td>
                                <td>
                                    <Input
                                        v-model="row.business_add"
                                        type="text"
                                        :name="`family[${index}][business_add]`"
                                        placeholder="Business address"
                                    />
                                </td>
                                <td>
                                    <Input
                                        v-model="row.tel_num"
                                        type="text"
                                        :name="`family[${index}][tel_num]`"
                                        placeholder="Tel. no."
                                    />
                                </td>
                                <td class="w-10">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        aria-label="Remove row"
                                        :disabled="formRows.length <= 1"
                                        @click="removeRow(index)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="processing"
                        @click="addRow"
                    >
                        <Plus class="size-4 mr-1" />
                        Add row
                    </Button>
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" class="size-4 mr-1" />
                        Save
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        :disabled="processing"
                        @click="cancelEdit"
                    >
                        Cancel
                    </Button>
                </div>
            </form>
        </template>
    </section>
</template>

<style scoped>
.ehris-family-edit-form :deep(.ehris-table input) {
    width: 100%;
    min-width: 0;
}
</style>
