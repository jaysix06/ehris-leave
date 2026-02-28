<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Pencil, Printer, Trash2 } from 'lucide-vue-next';

const pageTitle = 'REQUESTED ID';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home' },
    { title: 'Requested ID' },
];

type RequestedIdRow = {
    id: number;
    hrid: number | null;
    user_id: number | null;
    fullname: string;
    email: string | null;
    status: string;
    updated_at: string | null;
};

const props = defineProps<{
    requests: RequestedIdRow[];
}>();

const search = ref('');
const pageSize = ref(10);

const filtered = computed(() => {
    let list = props.requests ?? [];
    const q = search.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (r) =>
                (r.fullname && r.fullname.toLowerCase().includes(q)) ||
                (r.email && r.email.toLowerCase().includes(q))
        );
    }
    return list;
});

const paginated = computed(() => {
    const size = Math.max(1, pageSize.value);
    return filtered.value.slice(0, size);
});
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 w-full">
            <h1 class="text-2xl font-semibold text-foreground mb-4">REQUESTED ID</h1>

            <div class="rounded-lg border border-sidebar-border/70 bg-white shadow-sm overflow-hidden">
                <div class="p-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <label class="text-sm text-muted-foreground">
                            Show
                            <select
                                v-model.number="pageSize"
                                class="mx-1 rounded border border-input bg-background px-2 py-1 text-sm"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                            entries
                        </label>
                        <label class="text-sm text-muted-foreground">
                            Search:
                            <input
                                v-model="search"
                                type="search"
                                class="ml-1 rounded border border-input bg-background px-2 py-1 text-sm min-w-[180px]"
                                placeholder="Filter by name or email..."
                            />
                        </label>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-sidebar-border/70 bg-muted/30">
                                <th class="text-left font-medium p-3">NAME OF EMPLOYEES</th>
                                <th class="text-left font-medium p-3">Status</th>
                                <th class="text-left font-medium p-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in paginated"
                                :key="row.id"
                                class="border-b border-sidebar-border/50 hover:bg-muted/20"
                            >
                                <td class="p-3">{{ row.fullname }}</td>
                                <td class="p-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            row.status === 'For Release'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-amber-100 text-amber-800'
                                        "
                                    >
                                        {{ row.status }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a
                                            :href="`/employee-management/id-card-printing/${row.id}/eodb-id`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 rounded border border-input bg-background px-2 py-1 text-xs font-medium hover:bg-muted/50"
                                            title="EODB ID (opens in new tab)"
                                        >
                                            <Printer class="size-3.5" />
                                            EODB ID
                                        </a>
                                        <a
                                            :href="`/employee-management/id-card-printing/${row.id}/eodb-id-bb`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 rounded border border-input bg-background px-2 py-1 text-xs font-medium hover:bg-muted/50"
                                            title="EODB ID BB (opens in new tab)"
                                        >
                                            <Printer class="size-3.5" />
                                            EODB ID BB
                                        </a>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded border border-input bg-background px-2 py-1 text-xs font-medium hover:bg-muted/50"
                                            title="Pocket ID"
                                        >
                                            <Printer class="size-3.5" />
                                            Pocket ID
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded border border-input bg-background px-2 py-1 text-xs font-medium hover:bg-muted/50"
                                            title="Edit"
                                        >
                                            <Pencil class="size-3.5" />
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded border border-destructive/50 bg-destructive/10 px-2 py-1 text-xs font-medium text-destructive hover:bg-destructive/20"
                                            title="Delete"
                                        >
                                            <Trash2 class="size-3.5" />
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="paginated.length === 0">
                                <td colspan="3" class="p-6 text-center text-muted-foreground">
                                    No requested IDs. Employees will appear here after they use Self-Service → ID Card and click "Apply Changes".
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 text-sm text-muted-foreground border-t border-sidebar-border/70">
                    Showing {{ paginated.length }} of {{ filtered.length }} entries
                    <span v-if="search"> (filtered from {{ requests.length }} total)</span>.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
