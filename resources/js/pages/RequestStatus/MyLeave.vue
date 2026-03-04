<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue3-toastify';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import { requestStatus } from '@/routes';
import { myLeave } from '@/routes/request-status';
import { cancel as cancelRoute } from '@/routes/request-status/my-leave';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'My Leave';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Request Status', href: requestStatus().url },
    { title: pageTitle, href: myLeave().url },
];

const confirmId = ref<number | null>(null);
const cancellingId = ref<number | null>(null);

const statusBadgeHtml: Record<string, { label: string; cls: string }> = {
    pending_rm: { label: 'Pending (RM)', cls: 'background:hsl(45 93% 47%/.12);color:hsl(45 93% 30%)' },
    pending_hr: { label: 'Pending (HR)', cls: 'background:hsl(217 91% 60%/.12);color:hsl(217 91% 40%)' },
    pending_sds: { label: 'Pending (SDS)', cls: 'background:hsl(263 70% 50%/.12);color:hsl(263 70% 40%)' },
    approved: { label: 'Approved', cls: 'background:hsl(160 60% 45%/.12);color:hsl(160 60% 30%)' },
    disapproved: { label: 'Disapproved', cls: 'background:hsl(0 72% 51%/.12);color:hsl(0 72% 42%)' },
};

const columns: DataTableColumn[] = [
    { key: 'leave_type', label: 'Leave Type', width: '14rem', data: 'leave_type' },
    { key: 'duration', label: 'Duration', width: '16rem', data: 'duration' },
    { key: 'leave_days', label: 'Days', width: '5rem', data: 'leave_days', slot: 'days' },
    { key: 'date_applied', label: 'Date Applied', width: '10rem', data: 'date_applied' },
    {
        key: 'workflow_status',
        label: 'Status',
        width: '10rem',
        data: 'workflow_status',
        slot: 'status',
    },
    {
        key: 'leave_application_id',
        label: 'Action',
        width: '8rem',
        data: 'leave_application_id',
        slot: 'action',
        orderable: false,
    },
];

const cellRenderers: Record<string, (row: any, value: any) => string> = {
    days(_row: any, value: any) {
        return `<span style="display:block;text-align:center;font-weight:600">${value ?? 0}</span>`;
    },
    status(_row: any, value: any) {
        const meta = statusBadgeHtml[value] ?? { label: value, cls: 'background:hsl(var(--muted));color:hsl(var(--muted-foreground))' };
        return `<span style="display:inline-flex;align-items:center;border-radius:9999px;padding:0.2rem 0.65rem;font-size:0.75rem;font-weight:700;white-space:nowrap;${meta.cls}">${meta.label}</span>`;
    },
    action(row: any, _value: any) {
        const status = row.workflow_status;
        const id = row.id;
        if (status === 'pending_rm' || status === 'pending_hr') {
            return `<div style="text-align:center"><button data-cancel-id="${id}" class="my-leave-cancel-btn" style="display:inline-flex;align-items:center;gap:0.3rem;border:1px solid hsl(0 72% 51%/.4);border-radius:0.5rem;padding:0.3rem 0.7rem;font-size:0.78rem;font-weight:600;color:hsl(0 72% 51%);background:transparent;cursor:pointer">Cancel</button></div>`;
        }
        return '<div style="text-align:center;color:hsl(var(--muted-foreground));font-size:0.78rem">&mdash;</div>';
    },
};

function onRowClick(row: any) {
    const target = event?.target as HTMLElement | undefined;
    const cancelBtn = target?.closest('[data-cancel-id]') as HTMLElement | null;
    if (cancelBtn) {
        const id = Number(cancelBtn.getAttribute('data-cancel-id'));
        if (id > 0) {
            confirmId.value = id;
        }
    }
}

const dataTableRef = ref<InstanceType<typeof DataTable> | null>(null);

function executeCancel() {
    const id = confirmId.value;
    if (id === null) return;
    confirmId.value = null;
    cancellingId.value = id;

    router.delete(cancelRoute(id).url, {
        preserveScroll: true,
        onSuccess: () => {
            cancellingId.value = null;
            toast.success('Leave request cancelled.');
            dataTableRef.value?.reload?.();
        },
        onError: (errors: Record<string, string>) => {
            cancellingId.value = null;
            toast.error(errors.cancel || 'Failed to cancel leave request.');
        },
    });
}

function dismissConfirm() {
    confirmId.value = null;
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="my-leave-page">
            <section class="ehris-card">
                <div class="ehris-card-header">
                    <h3>{{ pageTitle }}</h3>
                </div>

                <div class="rounded-md border overflow-x-auto w-full">
                    <DataTable
                        ref="dataTableRef"
                        :columns="columns"
                        ajax-url="/api/request-status/my-leave/datatables"
                        row-key="id"
                        :per-page-options="[10, 25, 50, -1]"
                        :default-order="[3, 'desc']"
                        empty-message="You have no leave requests yet."
                        :cell-renderers="cellRenderers"
                        :on-row-click="onRowClick"
                    />
                </div>
            </section>
        </div>

        <!-- Cancel confirmation overlay -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="confirmId !== null" class="confirm-overlay" @click.self="dismissConfirm">
                    <div class="confirm-card">
                        <div class="confirm-header">
                            <h4>Cancel Leave Request</h4>
                        </div>
                        <div class="confirm-body">
                            <p>Are you sure you want to cancel this leave request? This action cannot be undone.</p>
                            <div class="confirm-actions">
                                <button type="button" class="confirm-dismiss" @click="dismissConfirm">Keep</button>
                                <button type="button" class="confirm-delete" @click="executeCancel">Yes, Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.my-leave-page {
    margin: 0 auto;
    width: 100%;
    max-width: 1600px;
    padding: 1rem 1.5rem;
}

@media (min-width: 768px) {
    .my-leave-page {
        padding: 1.5rem 2rem;
    }
}
</style>

<style>
.confirm-overlay {
    position: fixed;
    inset: 0;
    z-index: 200;
    display: flex;
    align-items: center;
    justify-content: center;
    background: hsl(0 0% 0% / 0.55);
    backdrop-filter: blur(4px);
}

.confirm-card {
    width: 100%;
    max-width: 400px;
    border-radius: 1rem;
    border: 1px solid hsl(var(--border));
    background: #fff;
    overflow: hidden;
    box-shadow: 0 8px 30px hsl(0 0% 0% / 0.2);
}

.dark .confirm-card {
    background: hsl(223 24% 14%);
}

.confirm-header {
    background: hsl(0 72% 51% / 0.08);
    border-bottom: 1px solid hsl(0 72% 51% / 0.15);
    padding: 1.1rem 1.5rem;
}

.confirm-header h4 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 800;
    color: hsl(0 72% 51%);
}

.confirm-body {
    padding: 1.25rem 1.5rem 1.5rem;
    background: #fff;
}

.dark .confirm-body {
    background: hsl(223 24% 14%);
}

.confirm-body p {
    margin: 0;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.5;
}

.confirm-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.25rem;
}

.confirm-dismiss,
.confirm-delete {
    border-radius: 0.55rem;
    padding: 0.45rem 0.95rem;
    font-size: 0.84rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid transparent;
}

.confirm-dismiss {
    background: hsl(var(--muted));
    color: hsl(var(--foreground));
    border-color: hsl(var(--border));
}

.confirm-dismiss:hover {
    background: hsl(var(--accent));
}

.confirm-delete {
    background: hsl(0 72% 51%);
    color: #fff;
}

.confirm-delete:hover {
    background: hsl(0 72% 45%);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
