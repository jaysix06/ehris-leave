<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import utilitiesRoutes from '@/routes/utilities';

const pageTitle = 'Utilities - User List';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
    },
];

type UserRow = {
    id: number;
    hrid: number | null;
    email: string | null;
    lastname: string | null;
    firstname: string | null;
    middlename: string | null;
    extname: string | null;
    fullname: string | null;
    job_title: string | null;
    role: string | null;
    active: boolean;
    office: string | null;
    department_id: number | null;
};

type PaginatedResponse<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const state = reactive<{
    table: PaginatedResponse<UserRow> | null;
    isLoading: boolean;
    search: string;
    perPage: number;
    error: string | null;
    statusMessage: string | null;
}>({
    table: null,
    isLoading: false,
    search: '',
    perPage: 10,
    error: null,
    statusMessage: null,
});

const hasData = computed(() => (state.table?.data?.length ?? 0) > 0);

const apiBase = computed(() => {
    const base = (utilitiesRoutes?.userListApi?.() as { url: string } | undefined)?.url;
    return base ?? '/api/utilities/users';
});

const csrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    return meta?.content ?? '';
};

const fetchUsers = async (pageUrl?: string | null) => {
    state.isLoading = true;
    state.error = null;

    try {
        let url: string;

        if (pageUrl) {
            const tmp = new URL(pageUrl, window.location.origin);
            tmp.pathname = apiBase.value;
            url = tmp.toString();
        } else {
            const params = new URLSearchParams();
            if (state.search.trim() !== '') {
                params.append('search', state.search.trim());
            }
            params.append('per_page', String(state.perPage));
            url = `${apiBase.value}?${params.toString()}`;
        }

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Failed to load users (HTTP ${response.status})`);
        }

        const data = (await response.json()) as PaginatedResponse<UserRow>;
        state.table = data;
    } catch (error: unknown) {
        console.error(error);
        state.error = 'Unable to load users. Please try again.';
    } finally {
        state.isLoading = false;
    }
};

const refresh = () => fetchUsers();

const onSearch = () => {
    fetchUsers();
};

const onChangePerPage = (perPage: number) => {
    state.perPage = perPage;
    fetchUsers();
};

const onChangePage = (link: { url: string | null; label: string; active: boolean }) => {
    if (!link.url || !state.table || link.active) return;
    const cleaned = link.label.replace(/&laquo;|&raquo;|&lsaquo;|&rsaquo;/g, '').trim().toLowerCase();
    if (cleaned === '') return;
    fetchUsers(link.url);
};

const displayName = (row: UserRow): string => {
    if (row.fullname && row.fullname.trim() !== '') {
        return row.fullname;
    }
    const parts = [row.firstname, row.middlename, row.lastname, row.extname]
        .filter((p) => typeof p === 'string' && p.trim() !== '')
        .map((p) => (p ?? '').trim());
    if (parts.length > 0) {
        return parts.join(' ');
    }
    return row.email ?? '';
};

const statusLabel = (row: UserRow): string => (row.active ? 'Active' : 'Inactive');
const statusClass = (row: UserRow): string =>
    row.active ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200';

const updateStatus = async (row: UserRow, active: boolean) => {
    if (row.active === active) return;

    const action = active ? 'activate' : 'deactivate';
    if (!confirm(`Are you sure you want to ${action} this user?`)) {
        return;
    }

    state.isLoading = true;
    state.error = null;
    state.statusMessage = null;

    try {
        const url =
            (utilitiesRoutes?.userListUpdateStatus?.(row.id) as { url: string } | undefined)?.url ??
            `/api/utilities/users/${row.id}/status`;

        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ active }),
        });

        if (!response.ok) {
            throw new Error(`Failed to update status (HTTP ${response.status})`);
        }

        const data = (await response.json()) as { id: number; active: boolean; hrid?: number | null };

        if (state.table) {
            state.table.data = state.table.data.map((u) =>
                u.id === data.id
                    ? ({
                          ...u,
                          active: data.active,
                          hrid: data.hrid ?? u.hrid,
                      } as UserRow)
                    : u,
            );
        }

        state.statusMessage = active ? 'User activated successfully.' : 'User deactivated successfully.';
    } catch (error: unknown) {
        console.error(error);
        state.error = 'Unable to update user status. Please try again.';
    } finally {
        state.isLoading = false;
    }
};

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="rounded-lg border border-sidebar-border/70 bg-background p-6 space-y-6">
                <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            View all registered EHRIS accounts. New registrations appear here as
                            <span class="font-semibold">Inactive</span> until an administrator activates them.
                        </p>
                    </div>
                </header>

                <section class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-3">
                        <label class="text-sm text-muted-foreground">Show</label>
                        <select
                            v-model.number="state.perPage"
                            class="rounded-md border border-input bg-background px-2 py-1 text-sm"
                            @change="onChangePerPage(state.perPage)"
                        >
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                        <span class="text-sm text-muted-foreground">entries</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm text-muted-foreground" for="user-search">Search:</label>
                        <input
                            id="user-search"
                            v-model="state.search"
                            type="search"
                            class="w-56 rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                            placeholder="Name, email, role, office"
                            @keyup.enter="onSearch"
                        />
                        <button
                            type="button"
                            class="rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                            @click="onSearch"
                        >
                            Go
                        </button>
                        <button
                            type="button"
                            class="rounded-md border border-input bg-background px-3 py-1.5 text-sm"
                            @click="
                                () => {
                                    state.search = '';
                                    onSearch();
                                }
                            "
                        >
                            Clear
                        </button>
                    </div>
                </section>

                <section class="space-y-3">
                    <div v-if="state.error" class="rounded-md border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive">
                        {{ state.error }}
                    </div>
                    <div
                        v-if="state.statusMessage"
                        class="rounded-md border border-emerald-500/40 bg-emerald-50 px-3 py-2 text-sm text-emerald-700"
                    >
                        {{ state.statusMessage }}
                    </div>

                    <div class="relative overflow-x-auto rounded-md border border-border bg-card">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-2 border-b border-border">No.</th>
                                    <th class="px-3 py-2 border-b border-border">HRID</th>
                                    <th class="px-3 py-2 border-b border-border">Email</th>
                                    <th class="px-3 py-2 border-b border-border">Name</th>
                                    <th class="px-3 py-2 border-b border-border">Role</th>
                                    <th class="px-3 py-2 border-b border-border">Office/School</th>
                                    <th class="px-3 py-2 border-b border-border">Status</th>
                                    <th class="px-3 py-2 border-b border-border text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="state.isLoading && !hasData">
                                    <td colspan="8" class="px-4 py-6 text-center text-muted-foreground">
                                        Loading users...
                                    </td>
                                </tr>
                                <tr v-else-if="!hasData">
                                    <td colspan="8" class="px-4 py-6 text-center text-muted-foreground">
                                        No users found.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(row, index) in state.table?.data ?? []"
                                    :key="row.id"
                                    class="hover:bg-muted/40 transition-colors"
                                >
                                    <td class="px-3 py-2 border-b border-border align-middle">
                                        {{ (state.table?.from ?? 0) + index }}
                                    </td>
                                    <td class="px-3 py-2 border-b border-border align-middle">
                                        {{ row.hrid ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 border-b border-border align-middle">
                                        <span class="font-medium">{{ row.email ?? '—' }}</span>
                                    </td>
                                    <td class="px-3 py-2 border-b border-border align-middle">
                                        {{ displayName(row) }}
                                        <div v-if="row.job_title" class="text-xs text-muted-foreground">
                                            {{ row.job_title }}
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 border-b border-border align-middle">
                                        {{ row.role ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 border-b border-border align-middle">
                                        {{ row.office ?? '—' }}
                                    </td>
                                    <td class="px-3 py-2 border-b border-border align-middle">
                                        <span
                                            class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium"
                                            :class="statusClass(row)"
                                        >
                                            {{ statusLabel(row) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 border-b border-border align-middle">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs text-slate-700 hover:bg-slate-50"
                                                :disabled="state.isLoading"
                                                @click="refresh"
                                            >
                                                Refresh
                                            </button>
                                            <button
                                                v-if="!row.active"
                                                type="button"
                                                class="rounded-md bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60"
                                                :disabled="state.isLoading"
                                                @click="updateStatus(row, true)"
                                            >
                                                Activate
                                            </button>
                                            <button
                                                v-else
                                                type="button"
                                                class="rounded-md bg-amber-500 px-2 py-1 text-xs font-semibold text-white hover:bg-amber-600 disabled:opacity-60"
                                                :disabled="state.isLoading"
                                                @click="updateStatus(row, false)"
                                            >
                                                Deactivate
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div
                            v-if="state.isLoading && hasData"
                            class="absolute inset-x-0 bottom-0 flex justify-center pb-2 text-xs text-muted-foreground"
                        >
                            Updating...
                        </div>
                    </div>

                    <div
                        v-if="state.table"
                        class="flex flex-col gap-3 border-t border-border pt-3 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between"
                    >
                        <div>
                            Showing
                            <span class="font-semibold">{{ state.table.from ?? 0 }}</span>
                            to
                            <span class="font-semibold">{{ state.table.to ?? 0 }}</span>
                            of
                            <span class="font-semibold">{{ state.table.total }}</span>
                            entries
                        </div>

                        <nav class="flex flex-wrap items-center gap-1">
                            <button
                                v-for="link in state.table.links"
                                :key="link.label + (link.url ?? '')"
                                type="button"
                                class="min-w-[2.25rem] rounded-md border px-2 py-1 text-xs"
                                :class="[
                                    link.active
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border bg-background text-foreground hover:bg-muted',
                                    !link.url ? 'opacity-50 cursor-default' : '',
                                ]"
                                :disabled="!link.url || link.active || state.isLoading"
                                @click="onChangePage(link)"
                                v-html="link.label"
                            />
                        </nav>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
