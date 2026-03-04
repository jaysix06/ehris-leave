<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { echo } from '@laravel/echo-vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { toast } from 'vue3-toastify';
import AppModal from '@/components/AppModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'Employee Management - Leave Requests';
const props = withDefaults(defineProps<{
    accessDenied?: boolean;
    deniedMessage?: string | null;
    redirectTo?: string | null;
}>(), {
    accessDenied: false,
    deniedMessage: null,
    redirectTo: null,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
    },
];

const statusBadgeMeta: Record<string, { label: string; cls: string }> = {
    pending_rm: { label: 'Pending (RM)', cls: 'background:hsl(45 93% 47%/.12);color:hsl(45 93% 30%)' },
    pending_hr: { label: 'Pending (HR)', cls: 'background:hsl(217 91% 60%/.12);color:hsl(217 91% 40%)' },
    pending_sds: { label: 'Pending (SDS)', cls: 'background:hsl(263 70% 50%/.12);color:hsl(263 70% 40%)' },
    approved: { label: 'Approved', cls: 'background:hsl(160 60% 45%/.12);color:hsl(160 60% 30%)' },
    disapproved: { label: 'Disapproved', cls: 'background:hsl(0 72% 51%/.12);color:hsl(0 72% 42%)' },
};

const columns: DataTableColumn[] = [
    { key: 'id', label: 'ID', width: '6rem', data: 'id' },
    { key: 'employee_name', label: 'Employee', width: '15rem', data: 'employee_name' },
    { key: 'leave_type', label: 'Leave Type', width: '13rem', data: 'leave_type' },
    { key: 'date_range', label: 'Date Range', width: '14rem', data: 'date_range' },
    { key: 'leave_days', label: 'Days', width: '6rem', data: 'leave_days', slot: 'days' },
    { key: 'date_applied', label: 'Date Applied', width: '10rem', data: 'date_applied' },
    { key: 'workflow_status', label: 'Status', width: '10rem', data: 'workflow_status', slot: 'status' },
    { key: 'acted_by', label: 'Acted By', width: '14rem', data: 'acted_by' },
    { key: 'remarks', label: 'Remarks', width: '14rem', data: 'remarks', slot: 'remarks' },
    { key: 'actions', label: 'Action', width: '12rem', data: 'id', slot: 'actions', orderable: false },
];

const cellRenderers: Record<string, (row: any, value: any) => string> = {
    days(_row: any, value: any) {
        return `<span style="display:block;text-align:center;font-weight:600">${value ?? 0}</span>`;
    },
    status(_row: any, value: any) {
        const meta = statusBadgeMeta[value] ?? { label: value, cls: 'background:hsl(var(--muted));color:hsl(var(--muted-foreground))' };
        return `<span style="display:inline-flex;align-items:center;border-radius:9999px;padding:0.2rem 0.65rem;font-size:0.75rem;font-weight:700;white-space:nowrap;${meta.cls}">${meta.label}</span>`;
    },
    remarks(_row: any, value: any) {
        const text = String(value ?? '').trim();
        return text !== '' ? text : '<span style="color:hsl(var(--muted-foreground))">-</span>';
    },
    actions(row: any, _value: any) {
        const status = String(row.workflow_status ?? '').toLowerCase();
        if (status !== 'pending_rm') {
            return '<span style="color:hsl(var(--muted-foreground));font-size:0.8rem">No action</span>';
        }

        const id = Number(row.id ?? 0);
        return `
            <div style="display:flex;gap:0.4rem;flex-wrap:wrap">
                <button data-action="approve" data-id="${id}" class="leave-action-approve" style="border:1px solid hsl(160 60% 35%/.45);color:hsl(160 60% 30%);background:transparent;border-radius:0.45rem;padding:0.22rem 0.52rem;font-size:0.76rem;font-weight:600;cursor:pointer;">Approve</button>
                <button data-action="disapprove" data-id="${id}" class="leave-action-disapprove" style="border:1px solid hsl(0 72% 42%/.45);color:hsl(0 72% 42%);background:transparent;border-radius:0.45rem;padding:0.22rem 0.52rem;font-size:0.76rem;font-weight:600;cursor:pointer;">Disapprove</button>
            </div>
        `;
    },
};

const selectedDecision = ref<'approve' | 'disapprove' | null>(null);
const selectedLeaveId = ref<number | null>(null);
const remarks = ref('');
const processing = ref(false);
const dataTableRef = ref<InstanceType<typeof DataTable> | null>(null);
const showAccessDeniedModal = ref(false);
const page = usePage();
const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED !== 'false';
const authHrid = computed(() => {
    const value = (page.props.auth?.user as Record<string, unknown> | undefined)?.hrId;
    const parsed = Number(value ?? 0);
    return Number.isFinite(parsed) ? parsed : 0;
});

function onRowClick(_row: any) {
    const target = event?.target as HTMLElement | undefined;
    const button = target?.closest('[data-action][data-id]') as HTMLElement | null;
    if (!button) return;

    const action = button.getAttribute('data-action');
    const id = Number(button.getAttribute('data-id'));
    if (!id || (action !== 'approve' && action !== 'disapprove')) return;

    selectedDecision.value = action;
    selectedLeaveId.value = id;
    remarks.value = '';
}

function closeDecisionModal() {
    if (processing.value) return;
    selectedDecision.value = null;
    selectedLeaveId.value = null;
    remarks.value = '';
}

function submitDecision() {
    if (selectedLeaveId.value === null || selectedDecision.value === null) return;
    if (processing.value) return;

    processing.value = true;
    const action = selectedDecision.value;

    router.patch(
        `/self-service/leave-application/${selectedLeaveId.value}/decision`,
        {
            decision: action,
            remarks: remarks.value.trim() !== '' ? remarks.value.trim() : null,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                const label = action === 'approve' ? 'approved' : 'disapproved';
                toast.success(`Leave request ${label} successfully.`);
                processing.value = false;
                closeDecisionModal();
                dataTableRef.value?.reload?.();
            },
            onError: (errors: Record<string, string>) => {
                toast.error(errors.decision || Object.values(errors)[0] || 'Failed to submit decision.');
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

const handleLeaveRequestUpdated = (payload: any) => {
    const rmAssigneeHrid = Number(payload?.rmAssigneeHrid ?? 0);
    if (authHrid.value > 0 && rmAssigneeHrid === authHrid.value) {
        dataTableRef.value?.reload?.();
    }
};

onMounted(() => {
    if (props.accessDenied) {
        showAccessDeniedModal.value = true;
        return;
    }

    if (!reverbEnabled) return;
    try {
        echo().channel('leave-requests').listen('.LeaveRequestUpdated', handleLeaveRequestUpdated);
    } catch {
        // Reverb not connected; real-time updates disabled
    }
});

onBeforeUnmount(() => {
    if (props.accessDenied) return;
    if (!reverbEnabled) return;
    try {
        echo().channel('leave-requests').stopListening('LeaveRequestUpdated');
    } catch {
        // ignore
    }
});

function acknowledgeAccessDenied() {
    showAccessDeniedModal.value = false;
    router.visit(props.redirectTo || '/dashboard');
}

function updateDecisionModal(value: boolean) {
    if (!value) {
        closeDecisionModal();
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="leave-requests-page">
            <section class="ehris-card">
                <div class="ehris-card-header">
                    <h3>Assigned Leave Requests</h3>
                </div>

                <div class="rounded-md border overflow-x-auto w-full">
                    <DataTable
                        v-if="!props.accessDenied"
                        ref="dataTableRef"
                        :columns="columns"
                        ajax-url="/api/employee-management/leave-requests/datatables"
                        row-key="id"
                        :per-page-options="[10, 25, 50, -1]"
                        :default-order="[5, 'desc']"
                        empty-message="No leave requests are currently assigned to you."
                        :cell-renderers="cellRenderers"
                        :on-row-click="onRowClick"
                    />
                    <div v-else class="access-denied-message">
                        <p>{{ props.deniedMessage || 'Access denied.' }}</p>
                    </div>
                </div>
            </section>
        </div>

        <AppModal
            v-model="showAccessDeniedModal"
            title="Access Denied"
            tone="disapprove"
            :close-on-backdrop="false"
            :persistent="true"
        >
            <p class="decision-prompt">
                {{ props.deniedMessage || 'Only reporting managers can view this page.' }}
            </p>
            <template #actions>
                <button
                    type="button"
                    class="btn-disapprove"
                    @click="acknowledgeAccessDenied"
                >
                    Go Back
                </button>
            </template>
        </AppModal>

        <AppModal
            :model-value="selectedDecision !== null && selectedLeaveId !== null"
            :title="`${selectedDecision === 'approve' ? 'Approve' : 'Disapprove'} Leave Request #${selectedLeaveId ?? ''}`"
            :tone="selectedDecision === 'approve' ? 'approve' : 'disapprove'"
            @update:model-value="updateDecisionModal"
        >
            <p class="decision-prompt">
                Are you sure you want to <strong>{{ selectedDecision }}</strong> this leave request?
            </p>
            <label for="decision-remarks" class="decision-remarks-label">Remarks</label>
            <textarea
                id="decision-remarks"
                class="decision-remarks-input"
                v-model="remarks"
                rows="3"
                placeholder="Add remarks (optional)"
            />
            <template #actions>
                <button type="button" class="btn-cancel" :disabled="processing" @click="closeDecisionModal">
                    Cancel
                </button>
                <button
                    type="button"
                    :class="selectedDecision === 'approve' ? 'btn-approve' : 'btn-disapprove'"
                    :disabled="processing"
                    @click="submitDecision"
                >
                    {{ processing ? 'Saving...' : (selectedDecision === 'approve' ? 'Approve' : 'Disapprove') }}
                </button>
            </template>
        </AppModal>
    </AppLayout>
</template>

<style scoped>
.leave-requests-page {
    margin: 0 auto;
    width: 100%;
    max-width: 1680px;
    padding: 1rem 1.5rem;
}

.access-denied-message {
    padding: 1rem;
    color: hsl(var(--muted-foreground));
    font-size: 0.9rem;
}

@media (min-width: 768px) {
    .leave-requests-page {
        padding: 1.5rem 2rem;
    }
}
</style>

<style>
.decision-prompt {
    margin: 0 0 1rem;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
    line-height: 1.5;
}

.decision-remarks-label {
    display: block;
    margin-bottom: 0.45rem;
    font-size: 0.82rem;
    font-weight: 700;
    color: hsl(var(--muted-foreground));
}

.decision-remarks-input {
    width: 100%;
    resize: vertical;
    border: 1.5px solid hsl(var(--muted-foreground) / 0.3);
    border-radius: 0.55rem;
    padding: 0.6rem 0.7rem;
    font-size: 0.86rem;
    line-height: 1.4;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
}

.decision-remarks-input:focus {
    outline: none;
    border-color: hsl(var(--primary));
}

.btn-cancel,
.btn-approve,
.btn-disapprove {
    border-radius: 0.55rem;
    padding: 0.45rem 0.95rem;
    font-size: 0.84rem;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
}

.btn-cancel {
    background: hsl(var(--muted));
    color: hsl(var(--foreground));
    border-color: hsl(var(--border));
}

.btn-cancel:hover {
    background: hsl(var(--accent));
}

.btn-approve {
    background: hsl(160 60% 40%);
    color: #fff;
}

.btn-approve:hover {
    background: hsl(160 60% 34%);
}

.btn-disapprove {
    background: hsl(0 72% 51%);
    color: #fff;
}

.btn-disapprove:hover {
    background: hsl(0 72% 45%);
}

.btn-cancel:disabled,
.btn-approve:disabled,
.btn-disapprove:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}
</style>
