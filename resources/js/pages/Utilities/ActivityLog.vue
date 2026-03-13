<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Filter, RefreshCw } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
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
    severity: string;
    event_type: string;
    activity: string;
    actor: string;
    target: string;
    module: string;
    request_source: string;
};

type Props = {
    filterOptions: {
        severities: string[];
        eventTypes: string[];
        modules: string[];
    };
};

const props = defineProps<Props>();

// Loading state for table operations
const isLoading = ref(false);
const selectedSeverity = ref('');
const selectedEventType = ref('');
const selectedModule = ref('');
const selectedDateFrom = ref('');
const selectedDateTo = ref('');

// Empty message for DataTable
const emptyMessage = computed(() => {
    return 'No activity logs found';
});

// Activity log columns
const activityLogColumns: DataTableColumn[] = [
    { key: 'created_at', label: 'Date & Time', width: '12rem', data: 'created_at' },
    { key: 'severity', label: 'Severity', width: '8rem', data: 'severity' },
    { key: 'event_type', label: 'Event Type', width: '12rem', data: 'event_type' },
    { key: 'actor', label: 'Actor', width: '18rem', data: 'actor' },
    { key: 'target', label: 'Target', width: '18rem', data: 'target' },
    { key: 'activity', label: 'Activity', width: '28rem', data: 'activity' },
    { key: 'module', label: 'Module', width: '14rem', data: 'module' },
    { key: 'request_source', label: 'Request', width: '20rem', data: 'request_source' },
];

// Get AJAX params for DataTables
const getAjaxParams = computed(() => () => ({
    severity: selectedSeverity.value || undefined,
    event_type: selectedEventType.value || undefined,
    module: selectedModule.value || undefined,
    date_from: selectedDateFrom.value || undefined,
    date_to: selectedDateTo.value || undefined,
}));

function clearFilters(): void {
    selectedSeverity.value = '';
    selectedEventType.value = '';
    selectedModule.value = '';
    selectedDateFrom.value = '';
    selectedDateTo.value = '';
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 flex flex-col gap-6">
            <section class="border border-border rounded-lg bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4 gap-4">
                    <div>
                        <h2 class="text-xl font-semibold flex items-center gap-2">
                            <Filter class="h-5 w-5" />
                            Filter Activity Logs
                        </h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            Narrow the audit trail by severity, event type, module, or date range.
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" @click="clearFilters">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Clear All
                    </Button>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="space-y-2">
                        <Label for="activity-log-severity">Severity</Label>
                        <select
                            id="activity-log-severity"
                            v-model="selectedSeverity"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All Severities</option>
                            <option v-for="severity in props.filterOptions.severities" :key="severity" :value="severity">
                                {{ severity.toUpperCase() }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="activity-log-event-type">Event Type</Label>
                        <select
                            id="activity-log-event-type"
                            v-model="selectedEventType"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All Event Types</option>
                            <option v-for="eventType in props.filterOptions.eventTypes" :key="eventType" :value="eventType">
                                {{ eventType }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="activity-log-module">Module</Label>
                        <select
                            id="activity-log-module"
                            v-model="selectedModule"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">All Modules</option>
                            <option v-for="module in props.filterOptions.modules" :key="module" :value="module">
                                {{ module }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="activity-log-date-from">Date From</Label>
                        <input
                            id="activity-log-date-from"
                            v-model="selectedDateFrom"
                            type="date"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="activity-log-date-to">Date To</Label>
                        <input
                            id="activity-log-date-to"
                            v-model="selectedDateTo"
                            type="date"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </div>
            </section>

            <!-- Activity Log Table -->
            <section class="border border-border rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Activity Logs</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Structured audit trail of system activity, security checks, and admin actions.
                    </p>
                </div>

                <!-- Data Table -->
                <div class="rounded-md border overflow-x-auto w-full">
                    <DataTable
                        :columns="activityLogColumns"
                        ajax-url="/utilities/activity-log/datatables"
                        :get-ajax-params="getAjaxParams"
                        row-key="log_id"
                        :loading="isLoading"
                        :empty-message="emptyMessage"
                        :show-export-buttons="false"
                        :per-page-options="[10, 25, 50, 100, -1]"
                        :default-order="[0, 'desc']"
                    />
                </div>
            </section>
        </div>
    </AppLayout>
</template>
