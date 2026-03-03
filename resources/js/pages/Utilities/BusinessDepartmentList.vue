<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';
import { Plus } from 'lucide-vue-next';
import Swal from 'sweetalert2';

type Props = {
    businessUnitsForSelect?: { value: string; label: string }[];
};

const props = withDefaults(defineProps<Props>(), {
    businessUnitsForSelect: () => [],
});

const pageTitle = 'Business Unit and Department';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home' },
    { title: 'Business Unit and Department List' },
];

// Dialog state
const showBusinessUnitDialog = ref(false);
const showDepartmentDialog = ref(false);
// Table refresh keys so DataTables refetch after add
const businessTableKey = ref(0);
const departmentTableKey = ref(0);
// Edit mode: when set, dialog submits as update instead of create
const editingBusinessUnitId = ref<number | null>(null);
const editingDepartmentId = ref<number | null>(null);

// Business Unit form
const businessUnitForm = reactive({
    BusinessUnitId: '' as string,
    BusinessUnit: '',
});
const businessUnitErrors = ref<Record<string, string>>({});
const businessUnitSubmitting = ref(false);

// Department form
const departmentForm = reactive({
    business_id: '' as string,
    department_id: '' as string,
    department_name: '',
    department_abbrev: '',
});
const departmentErrors = ref<Record<string, string>>({});
const departmentSubmitting = ref(false);

function openBusinessUnitDialog() {
    editingBusinessUnitId.value = null;
    businessUnitForm.BusinessUnitId = '';
    businessUnitForm.BusinessUnit = '';
    businessUnitErrors.value = {};
    showBusinessUnitDialog.value = true;
}

function openDepartmentDialog() {
    editingDepartmentId.value = null;
    departmentForm.business_id = '';
    departmentForm.department_id = '';
    departmentForm.department_name = '';
    departmentForm.department_abbrev = '';
    departmentErrors.value = {};
    showDepartmentDialog.value = true;
}

function submitBusinessUnit() {
    businessUnitErrors.value = {};
    businessUnitSubmitting.value = true;
    const id = editingBusinessUnitId.value;
    const payload = {
        BusinessUnitId: Number(businessUnitForm.BusinessUnitId) || 0,
        BusinessUnit: businessUnitForm.BusinessUnit,
    };
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            editingBusinessUnitId.value = null;
            showBusinessUnitDialog.value = false;
            businessTableKey.value += 1;
        },
        onError: (errors: Record<string, string>) => {
            businessUnitErrors.value = errors;
        },
        onFinish: () => {
            businessUnitSubmitting.value = false;
        },
    };
    if (id != null) {
        router.put(`/utilities/business-department-list/business-units/${id}`, payload, options);
    } else {
        router.post('/utilities/business-department-list/business-units', payload, options);
    }
}

function submitDepartment() {
    departmentErrors.value = {};
    departmentSubmitting.value = true;
    const id = editingDepartmentId.value;
    const payload = {
        business_id: Number(departmentForm.business_id) || 0,
        department_id: Number(departmentForm.department_id) || 0,
        department_name: departmentForm.department_name,
        department_abbrev: departmentForm.department_abbrev || undefined,
    };
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            editingDepartmentId.value = null;
            showDepartmentDialog.value = false;
            departmentTableKey.value += 1;
        },
        onError: (errors: Record<string, string>) => {
            departmentErrors.value = errors;
        },
        onFinish: () => {
            departmentSubmitting.value = false;
        },
    };
    if (id != null) {
        router.put(`/utilities/business-department-list/departments/${id}`, payload, options);
    } else {
        router.post('/utilities/business-department-list/departments', payload, options);
    }
}

// Business Unit columns: Business Code, Business Name, Update, Delete (percentages so table fits container)
const businessUnitColumns: DataTableColumn[] = [
    { key: 'BusinessUnitId', label: 'Business Code', data: 'BusinessUnitId', width: '22%' },
    { key: 'BusinessUnit', label: 'Business Name', data: 'BusinessUnit', width: '52%' },
    { key: 'update', label: 'Update', data: 'id', width: '13%', orderable: false, slot: 'actions_bu' },
    { key: 'delete', label: 'Delete', data: 'id', width: '13%', orderable: false, slot: 'actions_bu_delete' },
];

// Department columns: Business Code, Department ID, Department Name, Update, Delete (percentages so table fits container)
const departmentColumns: DataTableColumn[] = [
    { key: 'business_id', label: 'Business Code', data: 'business_id', width: '13%' },
    { key: 'department_id', label: 'Department ID', data: 'department_id', width: '13%' },
    { key: 'department_name', label: 'Department Name', data: 'department_name', width: '44%' },
    { key: 'update', label: 'Update', data: 'id', width: '15%', orderable: false, slot: 'actions_dept' },
    { key: 'delete', label: 'Delete', data: 'id', width: '15%', orderable: false, slot: 'actions_dept_delete' },
];

function escapeHtml(text: string | number): string {
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

function escapeAttr(text: string | number | null | undefined): string {
    if (text == null) return '';
    return String(text).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
}

// Cell renderers for action buttons (single slot per row for both Update and Delete)
const businessUnitCellRenderers: Record<string, (row: any) => string> = {
    actions_bu: (row) => {
        const id = row?.id ?? '';
        const code = escapeAttr(row?.BusinessUnitId ?? '');
        const name = escapeAttr(row?.BusinessUnit ?? '');
        return `
            <span class="inline-flex items-center gap-1">
                <button type="button" class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-primary hover:bg-primary/10" data-action="update" data-id="${escapeHtml(id)}" data-business-code="${code}" data-business-name="${name}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Update
                </button>
            </span>`;
    },
    actions_bu_delete: (row) => {
        const id = row?.id ?? '';
        return `
            <span class="inline-flex items-center gap-1">
                <button type="button" class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-destructive hover:bg-destructive/10" data-action="delete" data-id="${escapeHtml(id)}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete
                </button>
            </span>`;
    },
};

const departmentCellRenderers: Record<string, (row: any) => string> = {
    actions_dept: (row) => {
        const id = row?.id ?? '';
        const businessId = escapeAttr(row?.business_id ?? '');
        const deptId = escapeAttr(row?.department_id ?? '');
        const deptName = escapeAttr(row?.department_name ?? '');
        const deptAbbrev = escapeAttr(row?.department_abbrev ?? '');
        return `
            <span class="inline-flex items-center gap-1">
                <button type="button" class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-primary hover:bg-primary/10" data-action="update" data-id="${escapeHtml(id)}" data-business-id="${businessId}" data-department-id="${deptId}" data-department-name="${deptName}" data-department-abbrev="${deptAbbrev}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Update
                </button>
            </span>`;
    },
    actions_dept_delete: (row) => {
        const id = row?.id ?? '';
        return `
            <span class="inline-flex items-center gap-1">
                <button type="button" class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-destructive hover:bg-destructive/10" data-action="delete" data-id="${escapeHtml(id)}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete
                </button>
            </span>`;
    },
};

const getBusinessUnitAjaxParams = () => ({});
const getDepartmentAjaxParams = () => ({});

function onBusinessUnitTableClick(e: MouseEvent) {
    const target = e.target as HTMLElement;
    const updateBtn = target.closest('button[data-action="update"]');
    if (updateBtn) {
        e.preventDefault();
        e.stopPropagation();
        const id = updateBtn.getAttribute('data-id');
        const code = updateBtn.getAttribute('data-business-code') ?? '';
        const name = updateBtn.getAttribute('data-business-name') ?? '';
        if (id) {
            editingBusinessUnitId.value = Number(id);
            businessUnitForm.BusinessUnitId = code;
            businessUnitForm.BusinessUnit = name;
            businessUnitErrors.value = {};
            showBusinessUnitDialog.value = true;
        }
        return;
    }
    const deleteBtn = target.closest('button[data-action="delete"]');
    if (!deleteBtn) return;
    const id = deleteBtn.getAttribute('data-id');
    if (!id) return;
    e.preventDefault();
    e.stopPropagation();
    Swal.fire({
        title: 'Delete Business Unit?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        customClass: { popup: 'ehris-swal-delete-popup', actions: 'ehris-swal-actions', confirmButton: 'ehris-swal-confirm', cancelButton: 'ehris-swal-cancel' },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/utilities/business-department-list/business-units/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    businessTableKey.value += 1;
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Business unit has been deleted successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
}

function onDepartmentTableClick(e: MouseEvent) {
    const target = e.target as HTMLElement;
    const updateBtn = target.closest('button[data-action="update"]');
    if (updateBtn) {
        e.preventDefault();
        e.stopPropagation();
        const id = updateBtn.getAttribute('data-id');
        const businessId = updateBtn.getAttribute('data-business-id') ?? '';
        const deptId = updateBtn.getAttribute('data-department-id') ?? '';
        const deptName = updateBtn.getAttribute('data-department-name') ?? '';
        const deptAbbrev = updateBtn.getAttribute('data-department-abbrev') ?? '';
        if (id) {
            editingDepartmentId.value = Number(id);
            departmentForm.business_id = businessId;
            departmentForm.department_id = deptId;
            departmentForm.department_name = deptName;
            departmentForm.department_abbrev = deptAbbrev;
            departmentErrors.value = {};
            showDepartmentDialog.value = true;
        }
        return;
    }
    const deleteBtn = target.closest('button[data-action="delete"]');
    if (!deleteBtn) return;
    const id = deleteBtn.getAttribute('data-id');
    if (!id) return;
    e.preventDefault();
    e.stopPropagation();
    Swal.fire({
        title: 'Delete Department?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        customClass: { popup: 'ehris-swal-delete-popup', actions: 'ehris-swal-actions', confirmButton: 'ehris-swal-confirm', cancelButton: 'ehris-swal-cancel' },
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/utilities/business-department-list/departments/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    departmentTableKey.value += 1;
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Department has been deleted successfully.',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                },
            });
        }
    });
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-bd-compact p-4 space-y-4">
            <div>
                <h1 class="text-xl font-semibold">{{ pageTitle }}</h1>
                <p class="mt-0.5 text-xs text-muted-foreground">Control panel</p>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-[1fr_minmax(32rem,1fr)] gap-4 items-start">
                <!-- List of Business Unit -->
                <section class="rounded-md border border-border bg-card overflow-hidden min-w-0">
                    <div class="flex items-center justify-between px-3 py-2 bg-primary/10 border-b border-border">
                        <h2 class="text-base font-semibold text-primary">List of Business Unit</h2>
                        <Button variant="default" size="sm" class="gap-1 shrink-0 h-7 text-xs px-2" @click="openBusinessUnitDialog">
                            <Plus class="h-3.5 w-3.5" />
                            ADD NEW
                        </Button>
                    </div>
                    <div class="p-1.5 overflow-x-auto min-w-0" @click="onBusinessUnitTableClick">
                        <DataTable
                            :key="businessTableKey"
                            :columns="businessUnitColumns"
                            ajax-url="/api/utilities/business-department-list/business-units/datatables"
                            :get-ajax-params="getBusinessUnitAjaxParams"
                            row-key="id"
                            :per-page-options="[10, 25, 50]"
                            empty-message="No business units found."
                            :cell-renderers="businessUnitCellRenderers"
                        />
                    </div>
                </section>

                <!-- List of Department -->
                <section class="rounded-md border border-border bg-card overflow-hidden min-w-0 xl:min-w-[32rem]">
                    <div class="flex items-center justify-between px-3 py-2 bg-primary/10 border-b border-border">
                        <h2 class="text-base font-semibold text-primary">List of Department</h2>
                        <Button variant="default" size="sm" class="gap-1 shrink-0 h-7 text-xs px-2" @click="openDepartmentDialog">
                            <Plus class="h-3.5 w-3.5" />
                            ADD NEW
                        </Button>
                    </div>
                    <div class="ehris-department-wrap p-1.5 overflow-x-auto overflow-y-visible min-w-0 w-full" @click="onDepartmentTableClick">
                        <DataTable
                            :key="departmentTableKey"
                            class="ehris-department-table"
                            :columns="departmentColumns"
                            ajax-url="/api/utilities/business-department-list/departments/datatables"
                            :get-ajax-params="getDepartmentAjaxParams"
                            row-key="id"
                            :per-page-options="[10, 25, 50]"
                            empty-message="No departments found."
                            :cell-renderers="departmentCellRenderers"
                        />
                    </div>
                </section>
            </div>

            <!-- Add/Edit Business Unit Dialog (light design: centered title, grey-bordered inputs, Cancel left / Save blue right) -->
            <Dialog v-model:open="showBusinessUnitDialog">
                <DialogContent class="ehris-add-bu-dialog sm:max-w-md bg-card border border-border shadow-lg rounded-lg">
                    <DialogHeader class="text-center">
                        <DialogTitle class="text-base font-semibold">{{ editingBusinessUnitId != null ? 'Edit Business Unit' : 'Add New Business Unit' }}</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitBusinessUnit" class="ehris-add-bu-form space-y-4">
                        <div class="space-y-2">
                            <Label for="bu-BusinessUnitId">Business Code</Label>
                            <Input
                                id="bu-BusinessUnitId"
                                v-model="businessUnitForm.BusinessUnitId"
                                type="number"
                                placeholder="e.g. 92011"
                                class="ehris-add-bu-field w-full"
                            />
                            <p v-if="businessUnitErrors.BusinessUnitId" class="text-sm text-destructive">{{ businessUnitErrors.BusinessUnitId }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="bu-BusinessUnit">Business Name</Label>
                            <Input
                                id="bu-BusinessUnit"
                                v-model="businessUnitForm.BusinessUnit"
                                type="text"
                                placeholder="e.g. District 11"
                                class="ehris-add-bu-field w-full"
                            />
                            <p v-if="businessUnitErrors.BusinessUnit" class="text-sm text-destructive">{{ businessUnitErrors.BusinessUnit }}</p>
                        </div>
                        <DialogFooter class="flex flex-row justify-end gap-2 pt-2">
                            <Button type="button" variant="outline" @click="showBusinessUnitDialog = false">Cancel</Button>
                            <Button type="submit" variant="default" :disabled="businessUnitSubmitting">Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Add/Edit Department Dialog (blue header, labels and buttons per design) -->
            <Dialog v-model:open="showDepartmentDialog">
                <DialogContent class="ehris-add-dept-dialog sm:max-w-md p-0 gap-0">
                    <div class="ehris-add-dept-header bg-primary text-primary-foreground px-6 py-4 rounded-t-lg flex items-center">
                        <DialogTitle class="text-base font-semibold m-0">{{ editingDepartmentId != null ? 'Edit Department' : 'Add Department' }}</DialogTitle>
                    </div>
                    <form @submit.prevent="submitDepartment" class="ehris-add-dept-form space-y-4 p-6">
                        <div class="space-y-2">
                            <Label for="dept-business_id">Business Unit</Label>
                            <select
                                id="dept-business_id"
                                v-model="departmentForm.business_id"
                                class="ehris-add-dept-field"
                            >
                                <option value="">- Select Business Unit -</option>
                                <option v-for="opt in businessUnitsForSelect" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                            <p v-if="departmentErrors.business_id" class="text-sm text-destructive">{{ departmentErrors.business_id }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="dept-department_id">Department/School ID</Label>
                            <Input
                                id="dept-department_id"
                                v-model="departmentForm.department_id"
                                type="number"
                                placeholder="e.g. 110712"
                                class="ehris-add-dept-field w-full"
                            />
                            <p v-if="departmentErrors.department_id" class="text-sm text-destructive">{{ departmentErrors.department_id }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="dept-department_name">Department/School Name</Label>
                            <Input
                                id="dept-department_name"
                                v-model="departmentForm.department_name"
                                type="text"
                                placeholder="e.g. Tabid National High School"
                                class="ehris-add-dept-field w-full"
                            />
                            <p v-if="departmentErrors.department_name" class="text-sm text-destructive">{{ departmentErrors.department_name }}</p>
                        </div>
                        <DialogFooter class="gap-2 pt-2">
                            <Button type="button" variant="outline" @click="showDepartmentDialog = false">Close</Button>
                            <Button type="submit" variant="default" :disabled="departmentSubmitting">Add</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Compact text and spacing for this page only */
.ehris-bd-compact :deep(.dataTables_wrapper) {
    padding: 0.5rem;
}

.ehris-bd-compact :deep(.dataTables-header) {
    margin-bottom: 0.5rem;
}

.ehris-bd-compact :deep(.dataTables-header-row-1) {
    margin-bottom: 0.5rem;
    gap: 0.5rem;
}

.ehris-bd-compact :deep(.dataTables-header-row-2) {
    margin-top: 0.25rem;
    gap: 0.5rem;
}

.ehris-bd-compact :deep(.dataTables_filter input) {
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
    width: 200px;
}

.ehris-bd-compact :deep(.dataTables_length label),
.ehris-bd-compact :deep(.dataTables_filter label) {
    font-size: 0.8125rem;
}

.ehris-bd-compact :deep(.dataTables_length select) {
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
}

.ehris-bd-compact :deep(table.dataTable thead th) {
    padding: 0.5rem 0.625rem;
    font-size: 0.6875rem;
}

.ehris-bd-compact :deep(table.dataTable tbody td) {
    padding: 0.5rem 0.625rem;
    font-size: 0.8125rem;
}

.ehris-bd-compact :deep(.dataTables_info),
.ehris-bd-compact :deep(.dataTables_paginate .paginate_button) {
    font-size: 0.8125rem;
}

.ehris-bd-compact :deep(.dataTables_paginate .paginate_button) {
    padding: 0.375rem 0.5rem;
    margin: 0 0.125rem;
}

.ehris-bd-compact :deep(.dataTables-footer) {
    margin-top: 0.5rem;
}

/* Keep tables within container - no horizontal overflow */
.ehris-bd-compact :deep(.data-table-wrapper) {
    max-width: 100%;
    overflow-x: hidden;
}

.ehris-bd-compact :deep(table.dataTable) {
    table-layout: fixed;
    width: 100% !important;
}

.ehris-bd-compact :deep(table.dataTable thead th),
.ehris-bd-compact :deep(table.dataTable tbody td) {
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Ensure List of Department table and controls have room and are not clipped */
section:has(.ehris-department-table) :deep(.data-table-wrapper) {
    min-width: 100%;
}

section:has(.ehris-department-table) :deep(.dataTables_wrapper) {
    min-width: 0;
    overflow-y: visible;
}

/* No internal vertical scroll: table area grows with content like Business Unit */
section:has(.ehris-department-table) :deep(.data-table-wrapper) {
    overflow-y: visible;
}

section:has(.ehris-department-table) :deep(.dataTables_filter input) {
    min-width: 12rem;
}

section:has(.ehris-department-table) :deep(.dataTables_length select) {
    min-width: 5rem;
}

/* Add Department dialog: blue header and space for close button */
.ehris-add-dept-dialog {
    padding-top: 0 !important;
}
.ehris-add-dept-dialog .ehris-add-dept-header {
    padding-right: 2.5rem;
}
.ehris-add-dept-dialog [data-slot="dialog-close"] {
    color: hsl(var(--primary-foreground));
    top: 1rem;
}
.ehris-add-dept-dialog [data-slot="dialog-close"]:hover {
    opacity: 0.9;
}

/* Add Department form fields: blue border + blue underline on focus */
.ehris-add-dept-form .ehris-add-dept-field {
    display: flex;
    height: 2.25rem;
    width: 100%;
    min-width: 0;
    border-radius: 0.375rem;
    border: 1px solid hsl(var(--primary) / 0.5);
    background: white;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
}
.ehris-add-dept-form .ehris-add-dept-field::placeholder {
    color: hsl(var(--muted-foreground));
}
.ehris-add-dept-form .ehris-add-dept-field:hover {
    border-color: hsl(var(--primary) / 0.7);
}
.ehris-add-dept-form .ehris-add-dept-field:focus,
.ehris-add-dept-form .ehris-add-dept-field:focus-visible {
    border-color: hsl(var(--primary));
    border-bottom-width: 3px;
    box-shadow: 0 3px 0 0 hsl(var(--primary));
}

/* Add New Business Unit dialog: light design, centered title, grey-bordered inputs */
.ehris-add-bu-dialog {
    background: hsl(var(--card));
}
.ehris-add-bu-dialog [data-slot="dialog-close"] {
    top: 1rem;
    right: 1rem;
    color: hsl(var(--muted-foreground));
}
.ehris-add-bu-dialog [data-slot="dialog-close"]:hover {
    color: hsl(var(--foreground));
    opacity: 1;
}
.ehris-add-bu-form .ehris-add-bu-field {
    display: flex;
    height: 2.25rem;
    width: 100%;
    min-width: 0;
    border-radius: 0.375rem;
    border: 1px solid hsl(var(--border));
    background: white;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    transition: border-color 0.15s, box-shadow 0.15s;
    outline: none;
}
.ehris-add-bu-form .ehris-add-bu-field::placeholder {
    color: hsl(var(--muted-foreground));
}
.ehris-add-bu-form .ehris-add-bu-field:focus,
.ehris-add-bu-form .ehris-add-bu-field:focus-visible {
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 1px hsl(var(--ring));
}
</style>

<!-- Global styles for SweetAlert delete confirmation (popup is rendered in body) -->
<style>
.ehris-swal-delete-popup .ehris-swal-actions {
    display: flex !important;
    flex-direction: row;
    gap: 0.75rem;
    justify-content: center;
    margin-top: 1.25rem;
    padding: 0;
}
.ehris-swal-delete-popup .ehris-swal-cancel,
.ehris-swal-delete-popup .ehris-swal-confirm {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1.25rem !important;
    min-height: 2.25rem !important;
    font-size: 0.875rem !important;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    border: none;
}
.ehris-swal-delete-popup .ehris-swal-cancel {
    background-color: #e5e7eb !important;
    color: #374151 !important;
}
.ehris-swal-delete-popup .ehris-swal-cancel:hover {
    background-color: #d1d5db !important;
}
.ehris-swal-delete-popup .ehris-swal-confirm {
    background-color: #dc2626 !important;
    color: #fff !important;
}
.ehris-swal-delete-popup .ehris-swal-confirm:hover {
    background-color: #b91c1c !important;
}
</style>
