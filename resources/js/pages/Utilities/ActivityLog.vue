<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import type { BreadcrumbItem } from '@/types';

const pageTitle = "User's Activity Log";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Utilities',
    },
    {
        title: pageTitle,
    },
];

type ActivityLog = {
    log_id: number;
    created_at: string;
    activity: string;
    email: string;
    user_name: string;
    module: string;
};

// Loading state for table operations
const isLoading = ref(false);

// Empty message for DataTable
const emptyMessage = computed(() => {
    return 'No activity logs found';
});

// Activity log columns
const activityLogColumns: DataTableColumn[] = [
    { key: 'created_at', label: 'Date & Time', width: '12rem', data: 'created_at' },
    { key: 'activity', label: 'Activities', width: '30rem', data: 'activity' },
    { key: 'email', label: 'User', width: '20rem', data: 'email' },
    { key: 'module', label: 'Module', width: '15rem', data: 'module' },
];

// Get AJAX params for DataTables
const getAjaxParams = computed(() => () => ({}));
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 flex flex-col gap-6">
            <!-- Page Header -->
            <section class="border border-border rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-4">
                    <h1 class="text-3xl font-bold">{{ pageTitle }}</h1>
                    <p class="text-muted-foreground mt-1">
                        View and track all user activities including login, logout, updates, deletions, and password resets.
                    </p>
                </div>
            </section>

            <!-- Activity Log Table -->
            <section class="border border-border rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Activity Logs</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Comprehensive log of all system activities.
                    </p>
                </div>

                <!-- Data Table -->
                <div class="rounded-md border overflow-x-auto w-full">
                    <DataTable
                        :columns="activityLogColumns"
                        ajax-url="/api/utilities/activity-log/datatables"
                        :get-ajax-params="getAjaxParams"
                        row-key="log_id"
                        :loading="isLoading"
                        :empty-message="emptyMessage"
                        :show-export-buttons="false"
                        :per-page-options="[10, 25, 50, 100, -1]"
                    />
                </div>
            </section>
        </div>
    </AppLayout>
</template>
