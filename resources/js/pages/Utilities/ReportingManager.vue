<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';

const pageTitle = 'Utilities - Reporting Manager';
const breadcrumbs: BreadcrumbItem[] = [{ title: pageTitle }];

type ReportingManagerRow = {
    id: number;
    manager_name: string | null;
    position: string | null;
    department_school_name: string | null;
};

const tableKey = ref(0);

const columns: DataTableColumn[] = [
    { key: 'row_num', label: 'No.', data: 'row_num', width: '6rem' },
    { key: 'manager_name', label: 'Manager Name', data: 'manager_name', width: '18rem', slot: 'manager_name' },
    { key: 'position', label: 'Position', data: 'position', width: '14rem', slot: 'position' },
    { key: 'department_school_name', label: 'Department/School Name', data: 'department_school_name', width: '18rem', slot: 'department_school_name' },
];

const escapeHtml = (value: unknown): string => {
    const div = document.createElement('div');
    div.textContent = String(value ?? '');
    return div.innerHTML;
};

const cellRenderers: Record<string, (row: ReportingManagerRow, value?: unknown) => string> = {
    manager_name: (_row, value) => {
        const name = String(value ?? '').trim();
        if (!name) {
            return '<span class="text-xs text-muted-foreground italic">Not set</span>';
        }
        return escapeHtml(name);
    },
    position: (_row, value) => {
        const text = String(value ?? '').trim();
        return text ? escapeHtml(text) : '<span class="text-xs text-muted-foreground italic">-</span>';
    },
    department_school_name: (_row, value) => {
        const text = String(value ?? '').trim();
        return text ? escapeHtml(text) : '<span class="text-xs text-muted-foreground italic">-</span>';
    },
};

const getAjaxParams = computed(() => () => ({}));

const refreshTable = () => {
    tableKey.value += 1;
};
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
                            List of Reporting Managers in each Schools/Departments.
                        </p>
                    </div>
                    <Button variant="outline" size="sm" @click="refreshTable">
                        Refresh
                    </Button>
                </header>

                <section class="rounded-md border border-border bg-white overflow-x-auto">
                    <DataTable
                        :key="tableKey"
                        :columns="columns"
                        ajax-url="/utilities/reporting-manager/datatables"
                        :get-ajax-params="getAjaxParams"
                        row-key="id"
                        :per-page-options="[10, 25, 50, 100]"
                        empty-message="No reporting managers found."
                        :cell-renderers="cellRenderers"
                    />
                </section>
            </div>
        </div>
    </AppLayout>
</template>

