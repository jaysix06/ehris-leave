<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import utilitiesRoutes from '@/routes/utilities';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Check, Pencil, RefreshCw, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';

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

type DepartmentOption = {
    id: number;
    name: string;
};

const state = reactive<{
    table: PaginatedResponse<UserRow> | null;
    isLoading: boolean;
    search: string;
    perPage: number;
    page: number;
    error: string | null;
    statusMessage: string | null;
    departments: DepartmentOption[];
    isDepartmentsLoading: boolean;
}>({
    table: null,
    isLoading: false,
    search: '',
    perPage: 10,
    page: 1,
    error: null,
    statusMessage: null,
    departments: [],
    isDepartmentsLoading: false,
});

const hasData = computed(() => (state.table?.data?.length ?? 0) > 0);

const apiBase = computed(() => {
    const base = (utilitiesRoutes?.userList?.api?.() as { url: string } | undefined)?.url;
    return base ?? '/api/utilities/users';
});

const departmentsApiUrl = computed(() => '/api/utilities/departments');

const csrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    return meta?.content ?? '';
};

const normalizeNullableText = (value: unknown): string => {
    if (value === null || value === undefined) return '';
    if (typeof value !== 'string') return String(value);
    return value;
};

const toNullIfBlank = (value: string): string | null => {
    const trimmed = value.trim();
    return trimmed === '' ? null : trimmed;
};

const fetchUsers = async (pageUrl?: string | null) => {
    state.isLoading = true;
    state.error = null;

    try {
        let url: string;

        if (pageUrl) {
            const tmp = new URL(pageUrl, window.location.origin);
            tmp.pathname = apiBase.value;
            state.page = Number(tmp.searchParams.get('page') ?? '1') || 1;
            url = tmp.toString();
        } else {
            const params = new URLSearchParams();
            if (state.search.trim() !== '') {
                params.append('search', state.search.trim());
            }
            params.append('per_page', String(state.perPage));
            params.append('page', String(state.page));
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
        void Swal.fire({
            icon: 'error',
            title: 'Failed to load users',
            text: state.error ?? undefined,
        });
    } finally {
        state.isLoading = false;
    }
};

const refresh = () => fetchUsers();

const onSearch = () => {
    state.page = 1;
    fetchUsers();
};

const onChangePerPage = (perPage: number) => {
    state.perPage = perPage;
    state.page = 1;
    fetchUsers();
};

const onChangePage = (link: { url: string | null; label: string; active: boolean }) => {
    if (!link.url || !state.table || link.active) return;
    const cleaned = link.label.replace(/&laquo;|&raquo;|&lsaquo;|&rsaquo;/g, '').trim().toLowerCase();
    if (cleaned === '') return;
    fetchUsers(link.url);
};

const buildNameFromParts = (row: {
    firstname: string | null;
    middlename: string | null;
    lastname: string | null;
    extname: string | null;
    fullname?: string | null;
    email?: string | null;
}): string => {
    const parts = [row.firstname, row.middlename, row.lastname, row.extname]
        .filter((p) => typeof p === 'string' && p.trim() !== '')
        .map((p) => (p ?? '').trim());
    if (parts.length > 0) {
        return parts.join(' ');
    }
    if (row.fullname && row.fullname.trim() !== '') {
        return row.fullname;
    }
    return row.email ?? '';
};

const displayName = (row: UserRow): string => buildNameFromParts(row);

const statusLabel = (row: UserRow): string => (row.active ? 'Active' : 'Inactive');
const statusClass = (row: UserRow): string =>
    row.active ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200';

const editState = reactive<{
    isOpen: boolean;
    isSaving: boolean;
    userId: number | null;
    originalRow: UserRow | null;
    form: {
        hrId: string;
        email: string;
        lastname: string;
        firstname: string;
        middlename: string;
        extname: string;
        job_title: string;
        role: string;
        department_id: number | null;
    };
}>({
    isOpen: false,
    isSaving: false,
    userId: null,
    originalRow: null,
    form: {
        hrId: '',
        email: '',
        lastname: '',
        firstname: '',
        middlename: '',
        extname: '',
        job_title: '',
        role: '',
        department_id: null,
    },
});

const openEditModal = (row: UserRow) => {
    editState.userId = row.id;
    editState.originalRow = row;
    editState.form.hrId = row.hrid === null ? '' : String(row.hrid);
    editState.form.email = normalizeNullableText(row.email);
    editState.form.lastname = normalizeNullableText(row.lastname);
    editState.form.firstname = normalizeNullableText(row.firstname);
    editState.form.middlename = normalizeNullableText(row.middlename);
    editState.form.extname = normalizeNullableText(row.extname);
    editState.form.job_title = normalizeNullableText(row.job_title);
    editState.form.role = normalizeNullableText(row.role);
    editState.form.department_id = row.department_id ?? null;
    editState.isOpen = true;
};

const closeEditModal = () => {
    if (editState.isSaving) return;
    editState.isOpen = false;
};

const saveUserEdits = async () => {
    if (!editState.userId) return;

    editState.isSaving = true;
    state.error = null;
    state.statusMessage = null;

    try {
        const url =
            (utilitiesRoutes?.userList?.update?.(editState.userId) as { url: string } | undefined)?.url ??
            `/api/utilities/users/${editState.userId}`;

        const payload = {
            email: toNullIfBlank(editState.form.email),
            lastname: toNullIfBlank(editState.form.lastname),
            firstname: toNullIfBlank(editState.form.firstname),
            middlename: toNullIfBlank(editState.form.middlename),
            extname: toNullIfBlank(editState.form.extname),
            fullname: toNullIfBlank(
                buildNameFromParts({
                    firstname: editState.form.firstname,
                    middlename: editState.form.middlename,
                    lastname: editState.form.lastname,
                    extname: editState.form.extname,
                    fullname: null,
                    email: null,
                }),
            ),
            role: toNullIfBlank(editState.form.role),
            job_title: toNullIfBlank(editState.form.job_title),
            department_id: editState.form.department_id,
        };

        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const maybeJson = (await response.json().catch(() => null)) as unknown;
            const message =
                maybeJson &&
                typeof maybeJson === 'object' &&
                'message' in maybeJson &&
                typeof (maybeJson as { message?: unknown }).message === 'string'
                    ? (maybeJson as { message: string }).message
                    : `Failed to update user (HTTP ${response.status})`;
            throw new Error(message);
        }

        const data = (await response.json()) as Partial<UserRow> & { id: number; office?: string | null; hrid?: number | null };

        if (state.table) {
            const officeFromDept =
                state.departments.find((d) => d.id === (data.department_id ?? editState.form.department_id ?? -1))?.name ?? null;

            state.table.data = state.table.data.map((u) =>
                u.id === data.id
                    ? ({
                          ...u,
                          hrid: data.hrid ?? u.hrid,
                          email: data.email ?? u.email,
                          lastname: data.lastname ?? u.lastname,
                          firstname: data.firstname ?? u.firstname,
                          middlename: data.middlename ?? u.middlename,
                          extname: data.extname ?? u.extname,
                          fullname: data.fullname ?? u.fullname,
                          job_title: data.job_title ?? u.job_title,
                          role: data.role ?? u.role,
                          department_id: data.department_id ?? u.department_id,
                          office: data.office ?? officeFromDept ?? u.office,
                      } as UserRow)
                    : u,
            );
        }

        editState.isOpen = false;
        state.statusMessage = 'User details updated successfully.';
        void Swal.fire({
            icon: 'success',
            title: 'User updated',
            text: 'User details were updated successfully.',
        });
    } catch (error: unknown) {
        console.error(error);
        state.error = error instanceof Error ? error.message : 'Unable to update user details. Please try again.';
        void Swal.fire({
            icon: 'error',
            title: 'Update failed',
            text: state.error ?? undefined,
        });
    } finally {
        editState.isSaving = false;
    }
};

const deleteUser = async (row: UserRow) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Delete user?',
        text: `${displayName(row)}${row.email ? ` (${row.email})` : ''}`,
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
    });
    if (!result.isConfirmed) return;

    state.isLoading = true;
    state.error = null;
    state.statusMessage = null;

    try {
        const url =
            (utilitiesRoutes?.userList?.destroy?.(row.id) as { url: string } | undefined)?.url ??
            `/api/utilities/users/${row.id}`;

        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Failed to delete user (HTTP ${response.status})`);
        }

        // After deletion, refresh the table to keep pagination accurate.
        await fetchUsers();
        state.statusMessage = 'User deleted successfully.';
        void Swal.fire({
            icon: 'success',
            title: 'User deleted',
            text: 'The user has been deleted.',
        });
    } catch (error: unknown) {
        console.error(error);
        state.error = 'Unable to delete user. Please try again.';
        void Swal.fire({
            icon: 'error',
            title: 'Delete failed',
            text: state.error ?? undefined,
        });
    } finally {
        state.isLoading = false;
    }
};

const updateStatus = async (row: UserRow, active: boolean) => {
    if (row.active === active) return;

    const action = active ? 'activate' : 'deactivate';
    const result = await Swal.fire({
        icon: 'question',
        title: `${active ? 'Activate' : 'Deactivate'} user?`,
        text: displayName(row),
        showCancelButton: true,
        confirmButtonText: active ? 'Activate' : 'Deactivate',
        cancelButtonText: 'Cancel',
        confirmButtonColor: active ? '#059669' : '#f59e0b',
    });
    if (!result.isConfirmed) return;

    state.isLoading = true;
    state.error = null;
    state.statusMessage = null;

    try {
        const url =
            (utilitiesRoutes?.userList?.updateStatus?.(row.id) as { url: string } | undefined)?.url ??
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
        void Swal.fire({
            icon: 'success',
            title: active ? 'User activated' : 'User deactivated',
            text: displayName(row),
        });
    } catch (error: unknown) {
        console.error(error);
        state.error = 'Unable to update user status. Please try again.';
        void Swal.fire({
            icon: 'error',
            title: 'Status update failed',
            text: state.error ?? undefined,
        });
    } finally {
        state.isLoading = false;
    }
};

const fetchDepartments = async () => {
    if (state.isDepartmentsLoading) return;
    state.isDepartmentsLoading = true;

    try {
        const response = await fetch(departmentsApiUrl.value, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`Failed to load departments (HTTP ${response.status})`);
        }

        const data = (await response.json()) as DepartmentOption[];
        state.departments = Array.isArray(data) ? data : [];
    } catch (error: unknown) {
        console.error(error);
        // Non-blocking: user list still works without departments.
        state.departments = [];
    } finally {
        state.isDepartmentsLoading = false;
    }
};

let autoRefreshHandle: number | null = null;

onMounted(() => {
    fetchUsers();
    fetchDepartments();

    // Lightweight "real-time" refresh: keep table in sync without manual reload.
    autoRefreshHandle = window.setInterval(() => {
        if (!state.isLoading) {
            fetchUsers();
        }
    }, 30000); // every 30 seconds
});

onBeforeUnmount(() => {
    if (autoRefreshHandle !== null) {
        window.clearInterval(autoRefreshHandle);
        autoRefreshHandle = null;
    }
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
                    <div class="relative overflow-x-auto rounded-md border border-border bg-card">
                        <TooltipProvider :delay-duration="0">
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
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        size="icon-sm"
                                                        :disabled="state.isLoading"
                                                        @click="openEditModal(row)"
                                                    >
                                                        <Pencil class="size-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Edit</TooltipContent>
                                            </Tooltip>

                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        variant="outline"
                                                        size="icon-sm"
                                                        :disabled="state.isLoading"
                                                        @click="refresh"
                                                    >
                                                        <RefreshCw class="size-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Refresh</TooltipContent>
                                            </Tooltip>

                                            <Tooltip v-if="!row.active">
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        variant="default"
                                                        size="icon-sm"
                                                        class="bg-emerald-600 hover:bg-emerald-700"
                                                        :disabled="state.isLoading"
                                                        @click="updateStatus(row, true)"
                                                    >
                                                        <Check class="size-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Activate</TooltipContent>
                                            </Tooltip>

                                            <Tooltip v-else>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        variant="secondary"
                                                        size="icon-sm"
                                                        class="bg-amber-500 text-white hover:bg-amber-600"
                                                        :disabled="state.isLoading"
                                                        @click="updateStatus(row, false)"
                                                    >
                                                        <X class="size-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Deactivate</TooltipContent>
                                            </Tooltip>

                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        variant="destructive"
                                                        size="icon-sm"
                                                        :disabled="state.isLoading"
                                                        @click="deleteUser(row)"
                                                    >
                                                        <Trash2 class="size-4" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Delete</TooltipContent>
                                            </Tooltip>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </TooltipProvider>

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

    <Dialog
        :open="editState.isOpen"
        @update:open="(v) => (v ? (editState.isOpen = true) : closeEditModal())"
    >
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Edit user</DialogTitle>
                <DialogDescription>
                    Update the user’s details. Changes apply immediately after saving.
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">HRID</label>
                    <input
                        v-model="editState.form.hrId"
                        type="text"
                        readonly
                        class="w-full rounded-md border border-input bg-muted px-3 py-2 text-sm text-muted-foreground"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Email</label>
                    <input
                        v-model="editState.form.email"
                        type="email"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="name@example.com"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Last name</label>
                    <input
                        v-model="editState.form.lastname"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">First name</label>
                    <input
                        v-model="editState.form.firstname"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Middle name</label>
                    <input
                        v-model="editState.form.middlename"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Extension</label>
                    <input
                        v-model="editState.form.extname"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Jr., Sr., III"
                    />
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="text-sm text-muted-foreground">Full name</label>
                    <input
                        :value="
                            buildNameFromParts({
                                firstname: editState.form.firstname,
                                middlename: editState.form.middlename,
                                lastname: editState.form.lastname,
                                extname: editState.form.extname,
                                fullname: null,
                                email: null,
                            })
                        "
                        type="text"
                        readonly
                        class="w-full rounded-md border border-input bg-muted px-3 py-2 text-sm text-muted-foreground"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Role</label>
                    <input
                        v-model="editState.form.role"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Job title</label>
                    <input
                        v-model="editState.form.job_title"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="text-sm text-muted-foreground">Office / School</label>
                    <select
                        v-model="editState.form.department_id"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option :value="null">—</option>
                        <option
                            v-for="dept in state.departments"
                            :key="dept.id"
                            :value="dept.id"
                        >
                            {{ dept.name }}
                        </option>
                    </select>
                    <p v-if="state.isDepartmentsLoading" class="text-xs text-muted-foreground">
                        Loading offices...
                    </p>
                    <p v-else-if="state.departments.length === 0" class="text-xs text-muted-foreground">
                        Offices list not available (you can still edit other fields).
                    </p>
                </div>
            </div>

            <DialogFooter class="mt-2 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <Button variant="outline" :disabled="editState.isSaving" @click="closeEditModal">
                    Cancel
                </Button>
                <Button :disabled="editState.isSaving" @click="saveUserEdits">
                    {{ editState.isSaving ? 'Saving…' : 'Save changes' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
