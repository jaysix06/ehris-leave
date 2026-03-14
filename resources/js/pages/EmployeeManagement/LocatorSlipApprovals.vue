<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { toast } from 'vue3-toastify';
import AppModal from '@/components/AppModal.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import AppLayout from '@/layouts/AppLayout.vue';
import employeeManagementRoutes from '@/routes/employee-management';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'Employee Management - Locator Slip Approvals';
const props = withDefaults(
    defineProps<{
        accessDenied?: boolean;
        deniedMessage?: string | null;
        redirectTo?: string | null;
        canAct?: boolean;
    }>(),
    {
        accessDenied: false,
        deniedMessage: null,
        redirectTo: null,
        canAct: false,
    },
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employee Management',
        href: employeeManagementRoutes.employeeProfile().url,
    },
    {
        title: 'Locator Slip Approvals',
        href: employeeManagementRoutes.locatorSlipApprovals().url,
    },
];

const statusBadgeMeta: Record<string, { label: string; cls: string }> = {
    pending_rm: {
        label: 'Pending (RM)',
        cls: 'background:hsl(45 93% 47%/.12);color:hsl(45 93% 30%)',
    },
    approved: {
        label: 'Approved',
        cls: 'background:hsl(160 60% 45%/.12);color:hsl(160 60% 30%)',
    },
    disapproved: {
        label: 'Disapproved',
        cls: 'background:hsl(0 72% 51%/.12);color:hsl(0 72% 42%)',
    },
};

const columns: DataTableColumn[] = [
    {
        key: 'control_no',
        label: 'Control No.',
        width: '10rem',
        data: 'control_no',
    },
    {
        key: 'employee_name',
        label: 'Employee',
        width: '15rem',
        data: 'employee_name',
    },
    {
        key: 'permanent_station',
        label: 'Station',
        width: '14rem',
        data: 'permanent_station',
    },
    {
        key: 'purpose_of_travel',
        label: 'Purpose',
        width: '18rem',
        data: 'purpose_of_travel',
        slot: 'purpose',
    },
    {
        key: 'travel_schedule',
        label: 'Travel Schedule',
        width: '16rem',
        data: 'travel_schedule',
    },
    {
        key: 'date_of_filing',
        label: 'Filed On',
        width: '10rem',
        data: 'date_of_filing',
    },
    {
        key: 'workflow_status',
        label: 'Status',
        width: '10rem',
        data: 'workflow_status',
        slot: 'status',
    },
    {
        key: 'reporting_manager',
        label: 'Reporting Manager',
        width: '14rem',
        data: 'reporting_manager',
    },
    {
        key: 'remarks',
        label: 'Remarks',
        width: '14rem',
        data: 'remarks',
        slot: 'remarks',
    },
    {
        key: 'actions',
        label: 'Action',
        width: '12rem',
        data: 'id',
        slot: 'actions',
        orderable: false,
    },
];

const cellRenderers: Record<
    string,
    (row: Record<string, unknown>, value: unknown) => string
> = {
    purpose(row, value) {
        const raw = (row._raw as Record<string, unknown> | undefined) ?? {};
        const destination = String(raw.destination ?? '').trim();
        const summary = String(value ?? '').trim();
        const destinationHtml =
            destination !== ''
                ? `<div style="margin-top:0.2rem;color:hsl(var(--muted-foreground));font-size:0.75rem">Destination: ${destination}</div>`
                : '';

        return `<div><div style="font-weight:600">${summary || '-'}</div>${destinationHtml}</div>`;
    },
    status(_row, value) {
        const key = String(value ?? '').toLowerCase();
        const meta = statusBadgeMeta[key] ?? {
            label: key || 'Unknown',
            cls: 'background:hsl(var(--muted));color:hsl(var(--muted-foreground))',
        };

        return `<span style="display:inline-flex;align-items:center;border-radius:9999px;padding:0.2rem 0.65rem;font-size:0.75rem;font-weight:700;white-space:nowrap;${meta.cls}">${meta.label}</span>`;
    },
    remarks(_row, value) {
        const text = String(value ?? '').trim();
        return text !== ''
            ? text
            : '<span style="color:hsl(var(--muted-foreground))">-</span>';
    },
    actions(row) {
        if (!Boolean(row.can_act)) {
            return '<span style="color:hsl(var(--muted-foreground));font-size:0.8rem">View only</span>';
        }

        const id = Number(row.id ?? 0);
        return `
            <div style="display:flex;gap:0.4rem;flex-wrap:wrap">
                <button data-action="approve" data-id="${id}" style="border:1px solid hsl(160 60% 35%/.45);color:hsl(160 60% 30%);background:transparent;border-radius:0.45rem;padding:0.22rem 0.52rem;font-size:0.76rem;font-weight:600;cursor:pointer;">Approve</button>
                <button data-action="disapprove" data-id="${id}" style="border:1px solid hsl(0 72% 42%/.45);color:hsl(0 72% 42%);background:transparent;border-radius:0.45rem;padding:0.22rem 0.52rem;font-size:0.76rem;font-weight:600;cursor:pointer;">Disapprove</button>
            </div>
        `;
    },
};

const selectedDecision = ref<'approve' | 'disapprove' | null>(null);
const selectedLocatorSlipId = ref<number | null>(null);
const remarks = ref('');
const processing = ref(false);
const showAccessDeniedModal = ref(props.accessDenied);
const dataTableRef = ref<InstanceType<typeof DataTable> | null>(null);

const queueDescription = computed(() =>
    props.canAct
        ? 'Review and approve locator slip requests assigned to your school.'
        : 'Monitor locator slip requests and their reporting manager decisions.',
);

function onRowClick(): void {
    const target = event?.target as HTMLElement | undefined;
    const button = target?.closest(
        '[data-action][data-id]',
    ) as HTMLElement | null;
    if (button === null) {
        return;
    }

    const action = button.getAttribute('data-action');
    const id = Number(button.getAttribute('data-id'));
    if (!id || (action !== 'approve' && action !== 'disapprove')) {
        return;
    }

    selectedDecision.value = action;
    selectedLocatorSlipId.value = id;
    remarks.value = '';
}

function closeDecisionModal(): void {
    if (processing.value) {
        return;
    }

    selectedDecision.value = null;
    selectedLocatorSlipId.value = null;
    remarks.value = '';
}

function submitDecision(): void {
    if (
        selectedLocatorSlipId.value === null ||
        selectedDecision.value === null ||
        processing.value
    ) {
        return;
    }

    processing.value = true;
    const action = selectedDecision.value;

    router.patch(
        `/employee-management/locator-slip-approvals/${selectedLocatorSlipId.value}/decision`,
        {
            decision: action,
            remarks: remarks.value.trim() !== '' ? remarks.value.trim() : null,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success(
                    `Locator slip ${action === 'approve' ? 'approved' : 'disapproved'} successfully.`,
                );
                closeDecisionModal();
                dataTableRef.value?.reload?.();
            },
            onError: (errors: Record<string, string>) => {
                toast.error(
                    errors.decision ||
                        Object.values(errors)[0] ||
                        'Failed to submit locator slip decision.',
                );
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function acknowledgeAccessDenied(): void {
    showAccessDeniedModal.value = false;
    router.visit(props.redirectTo || '/dashboard');
}

function updateDecisionModal(value: boolean): void {
    if (!value) {
        closeDecisionModal();
    }
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="locator-slip-approvals-page">
            <section class="ehris-card">
                <div class="ehris-card-header">
                    <div>
                        <h3>Locator Slip Approvals</h3>
                        <p class="queue-copy">{{ queueDescription }}</p>
                    </div>
                </div>

                <div class="w-full overflow-x-auto rounded-md border">
                    <DataTable
                        v-if="!props.accessDenied"
                        ref="dataTableRef"
                        :columns="columns"
                        ajax-url="/api/employee-management/locator-slip-approvals/datatables"
                        row-key="id"
                        :per-page-options="[10, 25, 50, -1]"
                        :default-order="[5, 'desc']"
                        empty-message="No locator slip requests are currently available."
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
                {{
                    props.deniedMessage ||
                    'Only reporting managers, HR, and admins can view this page.'
                }}
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
            :model-value="
                selectedDecision !== null && selectedLocatorSlipId !== null
            "
            :title="`${selectedDecision === 'approve' ? 'Approve' : 'Disapprove'} Locator Slip #${selectedLocatorSlipId ?? ''}`"
            :tone="selectedDecision === 'approve' ? 'approve' : 'disapprove'"
            @update:model-value="updateDecisionModal"
        >
            <p class="decision-prompt">
                Are you sure you want to
                <strong>{{ selectedDecision }}</strong> this locator slip
                request?
            </p>
            <label for="decision-remarks" class="decision-remarks-label"
                >Remarks</label
            >
            <textarea
                id="decision-remarks"
                v-model="remarks"
                class="decision-remarks-input"
                rows="3"
                placeholder="Add remarks (optional)"
            />
            <template #actions>
                <button
                    type="button"
                    class="btn-cancel"
                    :disabled="processing"
                    @click="closeDecisionModal"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    :class="
                        selectedDecision === 'approve'
                            ? 'btn-approve'
                            : 'btn-disapprove'
                    "
                    :disabled="processing"
                    @click="submitDecision"
                >
                    {{
                        processing
                            ? 'Saving...'
                            : selectedDecision === 'approve'
                              ? 'Approve'
                              : 'Disapprove'
                    }}
                </button>
            </template>
        </AppModal>
    </AppLayout>
</template>

<style scoped>
.locator-slip-approvals-page {
    margin: 0 auto;
    width: 100%;
    max-width: 1680px;
    padding: 1rem 1.5rem;
}

.queue-copy {
    margin-top: 0.35rem;
    color: hsl(var(--muted-foreground));
    font-size: 0.9rem;
}

.access-denied-message {
    padding: 1rem;
    color: hsl(var(--muted-foreground));
    font-size: 0.9rem;
}

@media (min-width: 768px) {
    .locator-slip-approvals-page {
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
