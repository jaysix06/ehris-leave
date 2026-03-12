<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import utilitiesRoutes from '@/routes/utilities';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
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
import { Check, Download, Pencil, RefreshCw, Trash2, UserPlus, X } from 'lucide-vue-next';
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
    personal_email?: string | null;
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

type DepartmentOption = {
    id: number;
    name: string;
};

const dataTableWrapperRef = ref<HTMLElement | null>(null);
const refreshTrigger = ref(0);

const state = reactive<{
    error: string | null;
    statusMessage: string | null;
    departments: DepartmentOption[];
    isDepartmentsLoading: boolean;
    isActionLoading: boolean;
}>({
    error: null,
    statusMessage: null,
    departments: [],
    isDepartmentsLoading: false,
    isActionLoading: false,
});

const getCookieValue = (name: string): string => {
    const all = typeof document !== 'undefined' ? document.cookie : '';
    const parts = all.split(';').map((p) => p.trim());
    const hit = parts.find((p) => p.startsWith(`${name}=`));
    if (!hit) return '';
    return hit.substring(name.length + 1);
};

const getCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    if (meta?.content) return meta.content;

    // Laravel sets XSRF-TOKEN cookie; it is URL-encoded
    const cookie = getCookieValue('XSRF-TOKEN');
    if (!cookie) return '';
    try {
        return decodeURIComponent(cookie);
    } catch {
        return cookie;
    }
};

const getXsrfTokenCookie = (): string => {
    const cookie = getCookieValue('XSRF-TOKEN');
    if (!cookie) return '';
    try {
        return decodeURIComponent(cookie);
    } catch {
        return cookie;
    }
};

const getStatusUpdateUrl = (userId: number): string => {
    const base = (utilitiesRoutes?.userList?.updateStatus?.(userId) as { url: string } | undefined)?.url;
    if (base) {
        try {
            const u = new URL(base, window.location.origin);
            return u.toString();
        } catch {
            return `${window.location.origin}/api/utilities/users/${userId}/status`;
        }
    }
    return `${window.location.origin}/api/utilities/users/${userId}/status`;
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
    if (parts.length > 0) return parts.join(' ');
    if (row.fullname && row.fullname.trim() !== '') return row.fullname;
    return row.email ?? '';
};

const displayName = (row: UserRow): string => buildNameFromParts(row);

const getAjaxParams = computed(() => () => ({
    _refresh: String(refreshTrigger.value),
}));

const userColumns: DataTableColumn[] = [
    {
        key: 'no',
        label: 'No.',
        data: null,
        orderable: false,
        searchable: false,
        width: '4rem',
        thClass: 'text-center',
        tdClass: 'text-center',
        render: (_data: unknown, type: string, _row: unknown, meta: { row?: number; settings?: { _iDisplayStart?: number } }) => {
            if (type !== 'display' && type !== 'type') return '';
            const start = meta?.settings?._iDisplayStart ?? 0;
            const row = meta?.row ?? 0;
            return String(start + row + 1);
        },
    },
    { key: 'hrid', label: 'HRID', width: '6rem', data: 'hrid', thClass: 'text-center', tdClass: 'text-center' },
    { key: 'email', label: 'Email', width: '12rem', data: 'email' },
    { key: 'name', label: 'Name', width: '14rem', data: 'name' },
    { key: 'role', label: 'Role', width: '8rem', data: 'role' },
    { key: 'office', label: 'Office/School', width: '12rem', data: 'office' },
    {
        key: 'active',
        label: 'Status',
        width: '8rem',
        data: 'active',
        thClass: 'text-center',
        tdClass: 'text-center',
        render: (data: unknown) => {
            const active = data === true || data === '1';
            const cls = active ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-200';
            const label = active ? 'Active' : 'Inactive';
            return `<span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium ${cls}">${label}</span>`;
        },
    },
    {
        key: 'actions',
        label: 'Actions',
        data: null,
        orderable: false,
        searchable: false,
        width: '12rem',
        thClass: 'text-center',
        tdClass: 'text-center',
        render: (_data: unknown, type: string, row: { _raw?: UserRow } & Record<string, unknown>) => {
            if (type !== 'display' && type !== 'type') return '';
            const r = (row._raw || row) as UserRow;
            const id = r.id;
            const active = r.active;
            const esc = (s: string | number) => String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
            const emailLabel = (r.personal_email ?? r.email ?? '').trim();
            return `
                <div class="ehris-user-list-actions flex flex-wrap items-center justify-end gap-1">
                    <button type="button" class="ehris-btn ehris-btn-edit inline-flex size-8 items-center justify-center rounded-md border border-input bg-background hover:bg-muted" data-user-list-action="edit" data-user-id="${esc(id)}" title="Edit"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg></button>
                    <button type="button" class="ehris-btn ehris-btn-reset-password inline-flex size-8 items-center justify-center rounded-md border border-input bg-background hover:bg-muted" data-user-list-action="resetPassword" data-user-id="${esc(id)}" data-user-email="${esc(emailLabel)}" title="Reset password"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></button>
                    ${!active ? `<button type="button" class="ehris-btn ehris-btn-activate inline-flex size-8 items-center justify-center rounded-md bg-emerald-600 text-white hover:bg-emerald-700" data-user-list-action="activate" data-user-id="${esc(id)}" title="Activate"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></button>` : `<button type="button" class="ehris-btn ehris-btn-deactivate inline-flex size-8 items-center justify-center rounded-md bg-amber-500 text-white hover:bg-amber-600" data-user-list-action="deactivate" data-user-id="${esc(id)}" title="Deactivate"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg></button>`}
                    <button type="button" class="ehris-btn ehris-btn-delete inline-flex size-8 items-center justify-center rounded-md border border-destructive/50 text-destructive hover:bg-destructive hover:text-destructive-foreground" data-user-list-action="delete" data-user-id="${esc(id)}" title="Delete"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg></button>
                </div>`;
        },
    },
];

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

const createState = reactive<{
    isOpen: boolean;
    isSaving: boolean;
    form: {
        personal_email: string;
        firstname: string;
        middlename: string;
        lastname: string;
        extname: string;
        role: string;
        job_title: string;
        department_id: number | null;
    };
}>({
    isOpen: false,
    isSaving: false,
    form: {
        personal_email: '',
        firstname: '',
        middlename: '',
        lastname: '',
        extname: '',
        role: 'Employee',
        job_title: '',
        department_id: null,
    },
});

const openCreateModal = () => {
    createState.isOpen = true;
};

const closeCreateModal = () => {
    if (createState.isSaving) return;
    createState.isOpen = false;
};

const getCreateUserUrl = (): string => {
    const base = (utilitiesRoutes?.userList?.store as ((...args: any[]) => { url: string }) | undefined)?.().url;
    if (base) {
        try {
            return new URL(base, window.location.origin).toString();
        } catch {
            // fall through
        }
    }
    return `${window.location.origin}/api/utilities/users`;
};

const saveNewUser = async () => {
    createState.isSaving = true;
    state.error = null;
    state.statusMessage = null;
    try {
        const url = getCreateUserUrl();
        const payload = {
            personal_email: createState.form.personal_email.trim(),
            firstname: createState.form.firstname.trim(),
            middlename: createState.form.middlename.trim() || null,
            lastname: createState.form.lastname.trim(),
            extname: createState.form.extname.trim() || null,
            role: createState.form.role.trim() || 'Employee',
            job_title: createState.form.job_title.trim() || null,
            department_id: createState.form.department_id,
        };
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getXsrfTokenCookie(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        if (!response.ok) {
            const maybeJson = (await response.json().catch(() => null)) as { message?: string } | null;
            throw new Error(maybeJson?.message ?? `Failed to create user (HTTP ${response.status})`);
        }
        createState.isOpen = false;
        state.statusMessage = 'New user created successfully.';
        refreshTrigger.value += 1;
        void Swal.fire({ icon: 'success', title: 'User created', text: 'New user has been created.' });
    } catch (error: unknown) {
        console.error(error);
        state.error = error instanceof Error ? error.message : 'Unable to create user.';
        void Swal.fire({ icon: 'error', title: 'Create failed', text: state.error ?? undefined });
    } finally {
        createState.isSaving = false;
    }
};

const getExportUsersExcelUrl = (): string => {
    const base = (utilitiesRoutes?.userList?.exportExcel as ((...args: any[]) => { url: string }) | undefined)?.().url;
    if (base) {
        try {
            return new URL(base, window.location.origin).toString();
        } catch {
            // fall through
        }
    }
    return `${window.location.origin}/api/utilities/users/export/excel`;
};

const exportUsersExcel = () => {
    // Use normal navigation so the browser downloads the file
    window.location.href = getExportUsersExcelUrl();
};

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

const fetchUserForEdit = async (userId: number) => {
    state.isActionLoading = true;
    state.error = null;
    try {
        const url =
            (utilitiesRoutes?.userList?.show?.(userId) as { url: string } | undefined)?.url ??
            `${window.location.origin}/api/utilities/users/${userId}`;
        const res = await fetch(url, {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) throw new Error(`Failed to load user (HTTP ${res.status})`);
        const row = (await res.json()) as UserRow;
        openEditModal(row);
    } catch (e) {
        console.error(e);
        state.error = 'Could not load user details.';
        void Swal.fire({ icon: 'error', title: 'Error', text: state.error });
    } finally {
        state.isActionLoading = false;
    }
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
            `${window.location.origin}/api/utilities/users/${editState.userId}`;
        const payload = {
            email: toNullIfBlank(editState.form.email),
            lastname: toNullIfBlank(editState.form.lastname),
            firstname: toNullIfBlank(editState.form.firstname),
            middlename: toNullIfBlank(editState.form.middlename),
            extname: toNullIfBlank(editState.form.extname),
            fullname: toNullIfBlank(buildNameFromParts({
                firstname: editState.form.firstname,
                middlename: editState.form.middlename,
                lastname: editState.form.lastname,
                extname: editState.form.extname,
                fullname: null,
                email: null,
            })),
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
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getXsrfTokenCookie(),
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        if (!response.ok) {
            const maybeJson = (await response.json().catch(() => null)) as { message?: string } | null;
            throw new Error(maybeJson?.message ?? `Failed to update user (HTTP ${response.status})`);
        }
        editState.isOpen = false;
        state.statusMessage = 'User details updated successfully.';
        refreshTrigger.value += 1;
        void Swal.fire({ icon: 'success', title: 'User updated', text: 'User details were updated successfully.' });
    } catch (error: unknown) {
        console.error(error);
        state.error = error instanceof Error ? error.message : 'Unable to update user details.';
        void Swal.fire({ icon: 'error', title: 'Update failed', text: state.error ?? undefined });
    } finally {
        editState.isSaving = false;
    }
};

const deleteUser = async (userId: number, displayNameLabel: string) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Delete user?',
        text: displayNameLabel,
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
    });
    if (!result.isConfirmed) return;
    state.isActionLoading = true;
    state.error = null;
    state.statusMessage = null;
    try {
        const url =
            (utilitiesRoutes?.userList?.destroy?.(userId) as { url: string } | undefined)?.url ??
            `${window.location.origin}/api/utilities/users/${userId}`;
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getXsrfTokenCookie(),
            },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error(`Failed to delete user (HTTP ${response.status})`);
        state.statusMessage = 'User deleted successfully.';
        refreshTrigger.value += 1;
        void Swal.fire({ icon: 'success', title: 'User deleted', text: 'The user has been deleted.' });
    } catch (error: unknown) {
        console.error(error);
        state.error = 'Unable to delete user.';
        void Swal.fire({ icon: 'error', title: 'Delete failed', text: state.error });
    } finally {
        state.isActionLoading = false;
    }
};

const updateStatus = async (userId: number, active: boolean, displayNameLabel: string) => {
    const result = await Swal.fire({
        icon: 'question',
        title: `${active ? 'Activate' : 'Deactivate'} user?`,
        text: displayNameLabel,
        showCancelButton: true,
        confirmButtonText: active ? 'Activate' : 'Deactivate',
        cancelButtonText: 'Cancel',
        confirmButtonColor: active ? '#059669' : '#f59e0b',
    });
    if (!result.isConfirmed) return;
    state.isActionLoading = true;
    state.error = null;
    state.statusMessage = null;
    try {
        const url = getStatusUpdateUrl(userId);
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getXsrfTokenCookie(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ active }),
        });
        if (!response.ok) {
            throw new Error(`Failed to update status (HTTP ${response.status})`);
        }
        state.statusMessage = active ? 'User activated successfully.' : 'User deactivated successfully.';
        refreshTrigger.value += 1;
        void Swal.fire({ icon: 'success', title: active ? 'User activated' : 'User deactivated', text: displayNameLabel });
    } catch (error: unknown) {
        console.error(error);
        state.error = 'Unable to update user status. Please try again.';
        void Swal.fire({ icon: 'error', title: 'Status update failed', text: state.error ?? undefined });
    } finally {
        state.isActionLoading = false;
    }
};

const DEFAULT_PASSWORD = '1q2w3e4r5t';

const getResetPasswordUrl = (userId: number): string => {
    const base = (utilitiesRoutes?.userList?.resetPassword?.(userId) as { url: string } | undefined)?.url;
    if (base) {
        try {
            return new URL(base, window.location.origin).toString();
        } catch {
            // fall through
        }
    }
    return `${window.location.origin}/api/utilities/users/${userId}/reset-password`;
};

const resetUserPassword = async (userId: number, displayEmail: string) => {
    const label = (displayEmail || `User #${userId}`).trim();
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Reset password?',
        html: `This is an <strong>admin reset</strong> (different from “Forgot password”).<br><br>You are going to reset <strong>${label}</strong>'s password.<br>Temporary password (<strong>${DEFAULT_PASSWORD}</strong>) will be sent to the user's personal email.`,
        showCancelButton: true,
        confirmButtonText: 'Yes, reset password',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0f766e',
    });
    if (!result.isConfirmed) return;

    state.isActionLoading = true;
    state.error = null;
    state.statusMessage = null;
    try {
        const url = getResetPasswordUrl(userId);
        const response = await fetch(url, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken(),
                'X-XSRF-TOKEN': getXsrfTokenCookie(),
            },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error(`Failed to reset password (HTTP ${response.status})`);
        state.statusMessage = 'Password reset successfully.';
        refreshTrigger.value += 1;
        void Swal.fire({ icon: 'success', title: 'Password Reset!', text: 'User password has been successfully reset!' });
    } catch (error: unknown) {
        console.error(error);
        state.error = 'Unable to reset password. Please try again.';
        void Swal.fire({ icon: 'error', title: 'Reset failed', text: state.error ?? undefined });
    } finally {
        state.isActionLoading = false;
    }
};

const refreshTable = () => {
    refreshTrigger.value += 1;
};

const handleTableAction = (e: Event) => {
    const target = e.target as HTMLElement;
    const btn = target.closest?.('[data-user-list-action]') as HTMLElement | null;
    if (!btn || state.isActionLoading) return;
    const action = btn.getAttribute('data-user-list-action');
    const userIdStr = btn.getAttribute('data-user-id');
    if (!action || !userIdStr) return;
    const userId = Number(userIdStr);
    if (!Number.isFinite(userId)) return;
    e.preventDefault();
    e.stopPropagation();
    if (action === 'edit') {
        void fetchUserForEdit(userId);
    } else if (action === 'resetPassword') {
        const email = btn.getAttribute('data-user-email') || `User #${userId}`;
        void resetUserPassword(userId, email);
    } else if (action === 'activate') {
        void updateStatus(userId, true, `User #${userId}`);
    } else if (action === 'deactivate') {
        void updateStatus(userId, false, `User #${userId}`);
    } else if (action === 'delete') {
        void deleteUser(userId, `User #${userId}`);
    }
};

const fetchDepartments = async () => {
    if (state.isDepartmentsLoading) return;
    state.isDepartmentsLoading = true;
    try {
        const response = await fetch(`${window.location.origin}/api/utilities/departments`, {
            method: 'GET',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error(`Failed to load departments (HTTP ${response.status})`);
        const data = (await response.json()) as DepartmentOption[];
        state.departments = Array.isArray(data) ? data : [];
    } catch (error: unknown) {
        console.error(error);
        state.departments = [];
    } finally {
        state.isDepartmentsLoading = false;
    }
};

onMounted(() => {
    fetchDepartments();
    nextTick(() => {
        dataTableWrapperRef.value?.addEventListener('click', handleTableAction);
    });
});

onBeforeUnmount(() => {
    dataTableWrapperRef.value?.removeEventListener('click', handleTableAction);
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
                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            class="bg-red-600 hover:bg-red-700 text-white"
                            size="sm"
                            :disabled="state.isActionLoading"
                            @click="exportUsersExcel"
                        >
                            <Download class="mr-2 size-4" />
                            Export users
                        </Button>
                        <Button
                            type="button"
                            class="bg-red-600 hover:bg-red-700 text-white"
                            size="sm"
                            :disabled="state.isActionLoading"
                            @click="openCreateModal"
                        >
                            <UserPlus class="mr-2 size-4" />
                            New user
                        </Button>
                        <TooltipProvider :delay-duration="0">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        :disabled="state.isActionLoading"
                                        @click="refreshTable"
                                    >
                                        <RefreshCw class="mr-2 size-4" />
                                        Refresh
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>Reload the user list</TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                        <p v-if="state.statusMessage" class="text-sm text-emerald-600 dark:text-emerald-400">
                            {{ state.statusMessage }}
                        </p>
                        <p v-if="state.error" class="text-sm text-destructive">
                            {{ state.error }}
                        </p>
                    </div>
                </header>

                <section ref="dataTableWrapperRef" class="rounded-md border border-border bg-card overflow-x-auto">
                    <DataTable
                        :columns="userColumns"
                        ajax-url="/api/utilities/users/datatables"
                        :get-ajax-params="getAjaxParams"
                        row-key="id"
                        :loading="state.isActionLoading"
                        empty-message="No users found."
                        :per-page-options="[10, 25, 50, 100]"
                        :default-order="[0, 'desc']"
                    />
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
                    Update the user's details. Changes apply immediately after saving.
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

    <Dialog
        :open="createState.isOpen"
        @update:open="(v) => (v ? (createState.isOpen = true) : closeCreateModal())"
    >
        <DialogContent class="sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>New user</DialogTitle>
                <DialogDescription>
                    Create a new user account. New users will appear as <span class="font-semibold">Inactive</span> until activated.
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1 sm:col-span-2">
                    <label class="text-sm text-muted-foreground">Personal email</label>
                    <input
                        v-model="createState.form.personal_email"
                        type="email"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="name@example.com"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Last name</label>
                    <input v-model="createState.form.lastname" type="text" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                </div>
                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">First name</label>
                    <input v-model="createState.form.firstname" type="text" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                </div>
                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Middle name</label>
                    <input v-model="createState.form.middlename" type="text" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                </div>
                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Extension</label>
                    <input
                        v-model="createState.form.extname"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Jr., Sr., III"
                    />
                </div>

                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Role</label>
                    <input v-model="createState.form.role" type="text" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                </div>
                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Job title</label>
                    <input v-model="createState.form.job_title" type="text" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                </div>

                <div class="space-y-1 sm:col-span-2">
                    <label class="text-sm text-muted-foreground">Office / School</label>
                    <select v-model="createState.form.department_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                        <option :value="null">—</option>
                        <option v-for="dept in state.departments" :key="dept.id" :value="dept.id">
                            {{ dept.name }}
                        </option>
                    </select>
                </div>
            </div>

            <DialogFooter class="mt-2 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <Button variant="outline" :disabled="createState.isSaving" @click="closeCreateModal">
                    Cancel
                </Button>
                <Button :disabled="createState.isSaving" @click="saveNewUser">
                    {{ createState.isSaving ? 'Saving…' : 'Create user' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
.ehris-user-list-actions :deep(.ehris-btn) {
    min-width: 2rem;
}

/* Data table alignment */
:deep(section .data-table-wrapper table.dataTable thead th.text-center),
:deep(section .data-table-wrapper table.dataTable tbody td.text-center) {
    text-align: center;
}

:deep(section .data-table-wrapper table.dataTable thead th),
:deep(section .data-table-wrapper table.dataTable tbody td) {
    vertical-align: middle;
}
</style>
