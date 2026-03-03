<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
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
import Swal from 'sweetalert2';

const pageTitle = 'Utilities - Reporting Manager';

const breadcrumbs: BreadcrumbItem[] = [{ title: pageTitle }];

type ManagerOption = {
    hrid: number;
    name: string;
    office: string | null;
};

type EmployeeRow = {
    hrid: number;
    employee_id: string | null;
    firstname: string | null;
    middlename: string | null;
    lastname: string | null;
    extension: string | null;
    job_title: string | null;
    role: string | null;
    office: string | null;
    reporting_manager: string | null;
    department_name: string | null;
};

const tableKey = ref(0);
const isManagersLoading = ref(false);
const isAutoAssigning = ref(false);
const managers = ref<ManagerOption[]>([]);

const assignDialog = reactive<{
    isOpen: boolean;
    isSaving: boolean;
    selectedHrid: number | null;
    selectedEmployeeName: string;
    selectedManager: string;
}>({
    isOpen: false,
    isSaving: false,
    selectedHrid: null,
    selectedEmployeeName: '',
    selectedManager: '',
});

const customManagerInput = ref('');

const effectiveManager = computed(() => {
    if (assignDialog.selectedManager === '__custom__') return customManagerInput.value.trim();
    return assignDialog.selectedManager;
});

const csrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
    return meta?.content ?? '';
};

const buildName = (row: { firstname: string | null; middlename: string | null; lastname: string | null; extension: string | null }): string => {
    return [row.firstname, row.middlename, row.lastname, row.extension]
        .filter((p) => typeof p === 'string' && p.trim() !== '')
        .map((p) => (p ?? '').trim())
        .join(' ') || '-';
};

const escapeHtml = (value: unknown): string => {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
};

const columns: DataTableColumn[] = [
    { key: 'row_num', label: 'No.', data: 'row_num', width: '6rem' },
    { key: 'hrid', label: 'HRID', data: 'hrid', width: '8rem' },
    { key: 'employee_name', label: 'Employee Name', data: 'lastname', width: '18rem', slot: 'employee_name', orderable: false },
    { key: 'job_title', label: 'Position', data: 'job_title', width: '14rem' },
    { key: 'office', label: 'Office/School', data: 'department_name', width: '14rem' },
    { key: 'reporting_manager', label: 'Reporting Manager', data: 'reporting_manager', width: '14rem', slot: 'reporting_manager', orderable: false },
    { key: 'actions', label: 'Actions', data: 'hrid', width: '12rem', slot: 'actions', orderable: false },
];

const cellRenderers: Record<string, (row: EmployeeRow) => string> = {
    employee_name: (row) => {
        const fullName = buildName(row);
        const role = row.role ? `<div class="text-xs text-muted-foreground">${escapeHtml(row.role)}</div>` : '';
        return `<div class="font-medium">${escapeHtml(fullName)}</div>${role}`;
    },
    reporting_manager: (row) => {
        if (!row.reporting_manager) {
            return '<span class="text-xs text-muted-foreground italic">Not assigned</span>';
        }
        return `<span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">${escapeHtml(row.reporting_manager)}</span>`;
    },
    actions: (row) => {
        const name = buildName(row);
        const disabledRemove = row.reporting_manager ? '' : 'disabled';
        return `
            <div class="inline-flex items-center gap-2">
                <button type="button" class="inline-flex items-center rounded border border-primary px-2 py-1 text-xs text-primary hover:bg-primary/10" data-action="assign" data-hrid="${escapeHtml(row.hrid)}" data-name="${escapeHtml(name)}">
                    Assign
                </button>
                <button type="button" class="inline-flex items-center rounded border border-destructive px-2 py-1 text-xs text-destructive hover:bg-destructive/10" data-action="remove" data-hrid="${escapeHtml(row.hrid)}" data-name="${escapeHtml(name)}" ${disabledRemove}>
                    Remove
                </button>
            </div>
        `;
    },
};

const getAjaxParams = computed(() => () => ({}));

const fetchManagers = async () => {
    if (isManagersLoading.value) return;
    isManagersLoading.value = true;
    try {
        const response = await fetch('/api/utilities/reporting-manager/managers', {
            method: 'GET',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        managers.value = (await response.json()) as ManagerOption[];
    } catch {
        managers.value = [];
        void Swal.fire({ icon: 'error', title: 'Failed to load managers' });
    } finally {
        isManagersLoading.value = false;
    }
};

const openAssignDialog = (hrid: number, employeeName: string) => {
    assignDialog.selectedHrid = hrid;
    assignDialog.selectedEmployeeName = employeeName;
    assignDialog.selectedManager = '';
    customManagerInput.value = '';
    assignDialog.isOpen = true;
};

const saveAssignment = async () => {
    const manager = effectiveManager.value;
    if (!assignDialog.selectedHrid || !manager) {
        void Swal.fire({ icon: 'warning', title: 'Please select a manager' });
        return;
    }

    assignDialog.isSaving = true;
    try {
        const response = await fetch('/api/utilities/reporting-manager/assign', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                employee_hrids: [assignDialog.selectedHrid],
                reporting_manager: manager,
            }),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        assignDialog.isOpen = false;
        tableKey.value += 1;
        await fetchManagers();
        void Swal.fire({ icon: 'success', title: 'Reporting manager assigned' });
    } catch {
        void Swal.fire({ icon: 'error', title: 'Assignment failed' });
    } finally {
        assignDialog.isSaving = false;
    }
};

const removeManager = async (hrid: number, employeeName: string) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Remove reporting manager?',
        text: employeeName,
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
    });
    if (!result.isConfirmed) return;

    try {
        const response = await fetch(`/api/utilities/reporting-manager/${hrid}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        tableKey.value += 1;
        void Swal.fire({ icon: 'success', title: 'Removed' });
    } catch {
        void Swal.fire({ icon: 'error', title: 'Unable to remove reporting manager' });
    }
};

const autoAssignTeachers = async () => {
    if (isAutoAssigning.value) return;

    const confirm = await Swal.fire({
        icon: 'question',
        title: 'Auto-assign reporting managers?',
        text: 'Teachers will be assigned to principal/manager by school first, then department fallback.',
        showCancelButton: true,
        confirmButtonText: 'Run Auto-Assign',
        cancelButtonText: 'Cancel',
    });

    if (!confirm.isConfirmed) return;

    isAutoAssigning.value = true;
    try {
        const response = await fetch('/api/utilities/reporting-manager/auto-assign', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = (await response.json()) as {
            assigned: number;
            unmatched: number;
            teachers_scanned: number;
            manager_candidates: number;
        };

        tableKey.value += 1;
        await fetchManagers();

        void Swal.fire({
            icon: 'success',
            title: 'Auto-assignment completed',
            html: `Teachers scanned: <b>${data.teachers_scanned}</b><br>Assigned: <b>${data.assigned}</b><br>Unmatched: <b>${data.unmatched}</b>`,
        });
    } catch {
        void Swal.fire({ icon: 'error', title: 'Auto-assignment failed' });
    } finally {
        isAutoAssigning.value = false;
    }
};

const onTableClick = (e: MouseEvent) => {
    const target = e.target as HTMLElement;
    const button = target.closest('button[data-action]') as HTMLButtonElement | null;
    if (!button || button.disabled) return;

    const action = button.getAttribute('data-action');
    const hrid = Number(button.getAttribute('data-hrid'));
    const name = button.getAttribute('data-name') ?? 'Employee';
    if (!hrid || !action) return;

    e.preventDefault();
    e.stopPropagation();

    if (action === 'assign') {
        openAssignDialog(hrid, name);
        return;
    }

    if (action === 'remove') {
        void removeManager(hrid, name);
    }
};

onMounted(() => {
    fetchManagers();
});
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="rounded-lg border border-sidebar-border/70 bg-white p-6 space-y-6">
                <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            View and assign reporting managers to employees.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" :disabled="isAutoAssigning" @click="autoAssignTeachers">
                            {{ isAutoAssigning ? 'Assigning...' : 'Auto Assign by School/Department' }}
                        </Button>
                        <Button variant="outline" size="sm" :disabled="isManagersLoading" @click="fetchManagers()">
                            Refresh Managers
                        </Button>
                    </div>
                </header>

                <section class="rounded-md border border-border bg-white overflow-x-auto" @click="onTableClick">
                    <DataTable
                        :key="tableKey"
                        :columns="columns"
                        ajax-url="/api/utilities/reporting-manager/datatables"
                        :get-ajax-params="getAjaxParams"
                        row-key="hrid"
                        :per-page-options="[10, 25, 50, 100]"
                        empty-message="No employees found."
                        :cell-renderers="cellRenderers"
                    />
                </section>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="assignDialog.isOpen" @update:open="(v) => (assignDialog.isOpen = v)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Assign Reporting Manager</DialogTitle>
                <DialogDescription>
                    Assign a reporting manager to <span class="font-semibold">{{ assignDialog.selectedEmployeeName }}</span>.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-1">
                    <label class="text-sm text-muted-foreground">Select Reporting Manager</label>
                    <select
                        v-model="assignDialog.selectedManager"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="" disabled>- Choose a manager -</option>
                        <option v-for="m in managers" :key="m.hrid" :value="m.name">
                            {{ m.name }}{{ m.office ? ` (${m.office})` : '' }}
                        </option>
                        <option value="__custom__">Other (type manually)...</option>
                    </select>
                    <p v-if="isManagersLoading" class="text-xs text-muted-foreground">Loading managers...</p>
                </div>

                <div v-if="assignDialog.selectedManager === '__custom__'" class="space-y-1">
                    <label class="text-sm text-muted-foreground">Manager Name</label>
                    <input
                        v-model="customManagerInput"
                        type="text"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Full name of reporting manager"
                    />
                </div>
            </div>

            <DialogFooter class="mt-2 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <Button variant="outline" :disabled="assignDialog.isSaving" @click="assignDialog.isOpen = false">
                    Cancel
                </Button>
                <Button :disabled="assignDialog.isSaving || !effectiveManager" @click="saveAssignment">
                    {{ assignDialog.isSaving ? 'Saving...' : 'Assign' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
