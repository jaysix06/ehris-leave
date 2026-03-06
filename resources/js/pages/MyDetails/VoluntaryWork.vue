<script setup lang="ts">
import { Pencil } from 'lucide-vue-next';

type VoluntaryWorkItem = Record<string, unknown>;

function val(v: unknown): string {
    if (v == null || String(v).trim() === '') return '—';
    return String(v);
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

function dateRange(item: VoluntaryWorkItem): string {
    const from = val(pick(item, ['inclusive_date_from', 'date_from', 'start_date']));
    const to = val(pick(item, ['inclusive_date_to', 'date_to', 'end_date']));
    return `${from} – ${to}`;
}

defineProps<{
    voluntaryWork?: VoluntaryWorkItem[];
}>();
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Voluntary Work / Civic Involvement</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit voluntary work">
                <Pencil class="size-4" />
            </button>
        </div>
        <div class="ehris-table-wrap" v-if="voluntaryWork && voluntaryWork.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>Organization</th>
                        <th>Inclusive dates</th>
                        <th>Hours</th>
                        <th>Position / Nature of work</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in voluntaryWork" :key="i">
                        <td>{{ val(pick(item, ['name_address_org', 'organization', 'org_name', 'name_of_organization', 'affiliation'])) }}</td>
                        <td>{{ dateRange(item) }}</td>
                        <td>{{ val(pick(item, ['number_hours', 'hours', 'no_of_hours'])) }}</td>
                        <td>{{ val(pick(item, ['position_nature_of_work', 'position', 'nature_of_work', 'position_title'])) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="ehris-muted">No voluntary work records on file.</p>
    </section>
</template>
