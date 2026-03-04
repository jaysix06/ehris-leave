<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { toast } from 'vue3-toastify';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import utilitiesRoutes from '@/routes/utilities';

type LeaveTypeRow = {
    id: number;
    leave: string | null;
    leave_type: string;
};

const pageTitle = 'Utilities - Leave Types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
    },
];

const page = usePage();
const leaveTypes = computed(() => (page.props.leaveTypes ?? []) as LeaveTypeRow[]);

const newLeaveType = reactive({
    leave: '',
    leave_type: '',
});

const edits = reactive<Record<number, { leave: string; leave_type: string }>>({});

const getEdit = (row: LeaveTypeRow) => {
    if (!edits[row.id]) {
        edits[row.id] = {
            leave: row.leave ?? '',
            leave_type: row.leave_type ?? '',
        };
    }
    return edits[row.id];
};

const createLeaveType = () => {
    router.post(utilitiesRoutes.leaveTypes.store().url, newLeaveType, {
        onSuccess: () => {
            newLeaveType.leave = '';
            newLeaveType.leave_type = '';
            toast.success('Leave type created successfully.');
        },
        onError: (errors) => {
            const errorMessage = errors?.leave_type?.[0] || errors?.leave?.[0] || 'Failed to create leave type.';
            toast.error(errorMessage);
        },
    });
};

const updateLeaveType = (row: LeaveTypeRow) => {
    const edit = getEdit(row);
    router.put(utilitiesRoutes.leaveTypes.update(row.id).url, edit, {
        onSuccess: () => {
            toast.success('Leave type updated successfully.');
        },
        onError: (errors) => {
            const errorMessage = errors?.leave_type?.[0] || errors?.leave?.[0] || 'Failed to update leave type.';
            toast.error(errorMessage);
        },
    });
};

const deleteLeaveType = (row: LeaveTypeRow) => {
    if (!confirm(`Delete leave type "${row.leave_type}"?`)) return;
    router.delete(utilitiesRoutes.leaveTypes.destroy(row.id).url, {
        onSuccess: () => {
            toast.success('Leave type deleted successfully.');
        },
        onError: () => {
            toast.error('Failed to delete leave type.');
        },
    });
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>

                <div class="mt-6 grid gap-3">
                    <h2 class="text-lg font-semibold">Add Leave Type</h2>
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="grid gap-2">
                            <label class="text-sm text-muted-foreground">Code (Optional)</label>
                            <input
                                v-model="newLeaveType.leave"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="e.g. VL"
                            />
                        </div>
                        <div class="grid gap-2 md:col-span-2">
                            <label class="text-sm text-muted-foreground">Leave Type</label>
                            <input
                                v-model="newLeaveType.leave_type"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                placeholder="e.g. Vacation Leave"
                            />
                        </div>
                    </div>
                    <div>
                        <button
                            type="button"
                            class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground"
                            @click="createLeaveType"
                        >
                            Add Leave Type
                        </button>
                    </div>
                </div>

                <div class="mt-8">
                    <h2 class="text-lg font-semibold">Existing Leave Types</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full border border-border text-sm">
                            <thead class="bg-muted/30">
                                <tr>
                                    <th class="border border-border px-3 py-2 text-left">ID</th>
                                    <th class="border border-border px-3 py-2 text-left">Code</th>
                                    <th class="border border-border px-3 py-2 text-left">Leave Type</th>
                                    <th class="border border-border px-3 py-2 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in leaveTypes" :key="row.id">
                                    <td class="border border-border px-3 py-2">{{ row.id }}</td>
                                    <td class="border border-border px-3 py-2">
                                        <input
                                            v-model="getEdit(row).leave"
                                            class="w-full rounded-md border border-input bg-background px-2 py-1"
                                        />
                                    </td>
                                    <td class="border border-border px-3 py-2">
                                        <input
                                            v-model="getEdit(row).leave_type"
                                            class="w-full rounded-md border border-input bg-background px-2 py-1"
                                        />
                                    </td>
                                    <td class="border border-border px-3 py-2">
                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                class="rounded-md border border-primary px-3 py-1 text-primary"
                                                @click="updateLeaveType(row)"
                                            >
                                                Save
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-md border border-destructive px-3 py-1 text-destructive"
                                                @click="deleteLeaveType(row)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="leaveTypes.length === 0">
                                    <td class="border border-border px-3 py-3 text-center text-muted-foreground" colspan="4">
                                        No leave types found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
