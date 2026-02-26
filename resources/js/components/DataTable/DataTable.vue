<!--
  Vue.js DataTable: server-side chunk + lazy loading.
  - Chunk: only one page of data is requested from the server (no full dataset).
  - Lazy: optional "Load more" appends next chunk on demand.
  Avoids retrieving all data and prevents UI freezing/lag.
-->
<script setup lang="ts">
import { ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginationMeta = {
    data: unknown[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: PaginationLink[];
};

export type DataTableColumn = {
    key: string;
    label: string;
    /** Optional class for th and td */
    class?: string;
    thClass?: string;
    tdClass?: string;
    /** Slot name for custom cell content: use slot "cell-{slot}" */
    slot?: string;
    /** Width style, e.g. "8rem" or "minmax(100px, 1fr)" */
    width?: string;
};

const props = withDefaults(
    defineProps<{
        /** Column definitions */
        columns: DataTableColumn[];
        /** Current page (or accumulated) rows */
        data: unknown[];
        /** Laravel-style pagination meta */
        pagination: PaginationMeta;
        /** Row key for :key (e.g. "id", "hrid") */
        rowKey: string;
        /** Show loading overlay when fetching */
        loading?: boolean;
        /** Show "Load more" instead of page numbers; parent appends next chunk */
        loadMoreMode?: boolean;
        /** Per-page options (chunk sizes) */
        perPageOptions?: number[];
        /** Message when no data */
        emptyMessage?: string;
        /** Min width for table container (e.g. "1200px") */
        minTableWidth?: string;
        /** Show pagination above the table as well (default false) */
        showPaginationTop?: boolean;
        /** Function to check if a row is expanded (for accordion) */
        isRowExpanded?: (row: unknown) => boolean;
        /** Row click handler */
        onRowClick?: (row: unknown) => void;
        /** Row class function */
        rowClass?: (row: unknown) => string | string[];
    }>(),
    {
        loading: false,
        loadMoreMode: false,
        perPageOptions: () => [10, 25, 50, 100],
        emptyMessage: 'No records found',
        minTableWidth: '1200px',
        showPaginationTop: false,
        isRowExpanded: undefined,
        onRowClick: undefined,
        rowClass: undefined,
    },
);

const emit = defineEmits<{
    'page-change': [url: string | null];
    'per-page-change': [perPage: number];
    'load-more': [];
    'row-click': [row: unknown];
}>();

function cleanPaginationLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '')
        .replace(/&raquo;/g, '')
        .replace(/&lsaquo;/g, '')
        .replace(/&rsaquo;/g, '')
        .trim();
}

function isNavigationLink(label: string): boolean {
    const cleaned = cleanPaginationLabel(label).toLowerCase();
    return cleaned === 'previous' || cleaned === 'next';
}

function changePage(url: string | null) {
    if (url) emit('page-change', url);
}

function changePerPage(perPage: number) {
    emit('per-page-change', perPage);
}

function getCellValue(row: Record<string, unknown>, key: string): unknown {
    const parts = key.split('.');
    let v: unknown = row;
    for (const p of parts) {
        if (v != null && typeof v === 'object' && p in v) v = (v as Record<string, unknown>)[p];
        else return undefined;
    }
    return v;
}

function handleRowClick(row: unknown) {
    if (props.onRowClick) {
        props.onRowClick(row);
    }
    emit('row-click', row);
}
</script>

<template>
    <div class="data-table-wrapper">
        <!-- Pagination above table -->
        <div
            v-if="showPaginationTop"
            class="data-table-pagination data-table-pagination-top flex items-center justify-between mb-4 pb-3 pt-3 border-b gap-6"
        >
            <div class="flex items-center gap-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
                </div>
                <div v-if="!loadMoreMode" class="flex items-center gap-2">
                    <Label class="text-sm">Per page:</Label>
                    <select
                        :value="pagination.per_page"
                        @change="changePerPage(Number(($event.target as HTMLSelectElement).value))"
                        class="rounded-md border border-input bg-background px-2 py-1 text-sm"
                    >
                        <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2">
                <template v-if="loadMoreMode">
                    <Button
                        v-if="pagination.current_page < pagination.last_page"
                        variant="outline"
                        size="sm"
                        :disabled="loading"
                        @click="emit('load-more')"
                    >
                        <Loader2 v-if="loading" class="h-4 w-4 animate-spin mr-2" />
                        Load more
                    </Button>
                    <span v-else-if="pagination.data.length > 0" class="text-sm text-muted-foreground">
                        All {{ pagination.total }} loaded
                    </span>
                </template>
                <template v-else>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="pagination.current_page === 1"
                        @click="changePage(pagination.links.find((l) => isNavigationLink(l.label) && cleanPaginationLabel(l.label).toLowerCase() === 'previous')?.url ?? null)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Previous
                    </Button>
                    <template v-for="(link, idx) in pagination.links" :key="'top-' + idx">
                        <Button
                            v-if="!isNavigationLink(link.label)"
                            variant="outline"
                            size="sm"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                            :disabled="!link.url"
                            @click="changePage(link.url)"
                        >
                            {{ cleanPaginationLabel(link.label) }}
                        </Button>
                    </template>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="pagination.current_page === pagination.last_page"
                        @click="changePage(pagination.links.find((l) => isNavigationLink(l.label) && cleanPaginationLabel(l.label).toLowerCase() === 'next')?.url ?? null)"
                    >
                        Next
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </template>
            </div>
        </div>

        <div class="rounded-md border overflow-x-auto w-full">
            <div v-if="loading" class="data-table-loading">
                <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
                <span class="text-sm text-muted-foreground">Loading...</span>
            </div>
            <table
                class="data-table ehris-employee-table w-full border-collapse"
                :style="{ minWidth: minTableWidth }"
            >
                <thead class="bg-muted/50">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="data-table-th ehris-th"
                            :class="[col.class, col.thClass]"
                            :style="col.width ? { width: col.width, minWidth: col.width } : undefined"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="(row, index) in data" :key="(row as Record<string, unknown>)[rowKey] ?? index">
                        <!-- Main row -->
                        <tr
                            :class="[
                                'hover:bg-muted/50 border-b transition-colors',
                                isRowExpanded && isRowExpanded(row) ? 'bg-muted/30' : '',
                                rowClass ? (typeof rowClass(row) === 'string' ? rowClass(row) : (rowClass(row) as string[]).join(' ')) : '',
                                (onRowClick || isRowExpanded) ? 'cursor-pointer' : '',
                            ]"
                            @click="handleRowClick(row)"
                        >
                            <td
                                v-for="col in columns"
                                :key="col.key"
                                class="data-table-td ehris-td"
                                :class="[col.class, col.tdClass]"
                            >
                                <slot
                                    v-if="col.slot"
                                    :name="`cell-${col.slot}`"
                                    :row="row"
                                    :value="getCellValue(row as Record<string, unknown>, col.key)"
                                >
                                    {{ getCellValue(row as Record<string, unknown>, col.key) ?? '-' }}
                                </slot>
                                <template v-else>
                                    {{ getCellValue(row as Record<string, unknown>, col.key) ?? '-' }}
                                </template>
                            </td>
                        </tr>
                        
                        <!-- Accordion row (if expanded) -->
                        <tr
                            v-if="isRowExpanded && isRowExpanded(row)"
                            class="accordion-content-row"
                        >
                            <td :colspan="columns.length" class="p-0">
                                <slot
                                    name="accordion"
                                    :row="row"
                                >
                                    <!-- Default accordion content slot -->
                                </slot>
                            </td>
                        </tr>
                    </template>
                    
                    <tr v-if="data.length === 0">
                        <td :colspan="columns.length" class="data-table-empty">
                            <slot name="empty">
                                {{ emptyMessage }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="data-table-pagination flex items-center justify-between mt-6 pt-4 pb-2 border-t gap-6">
            <div class="flex items-center gap-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
                </div>
                <div v-if="!loadMoreMode" class="flex items-center gap-2">
                    <Label class="text-sm">Per page:</Label>
                    <select
                        :value="pagination.per_page"
                        @change="changePerPage(Number(($event.target as HTMLSelectElement).value))"
                        class="rounded-md border border-input bg-background px-2 py-1 text-sm"
                    >
                        <option
                            v-for="n in perPageOptions"
                            :key="n"
                            :value="n"
                        >
                            {{ n }}
                        </option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2">
                <!-- Load more mode: single "Load more" button -->
                <template v-if="loadMoreMode">
                    <Button
                        v-if="pagination.current_page < pagination.last_page"
                        variant="outline"
                        size="sm"
                        :disabled="loading"
                        @click="emit('load-more')"
                    >
                        <Loader2 v-if="loading" class="h-4 w-4 animate-spin mr-2" />
                        Load more
                    </Button>
                    <span
                        v-else-if="pagination.data.length > 0"
                        class="text-sm text-muted-foreground"
                    >
                        All {{ pagination.total }} loaded
                    </span>
                </template>
                <!-- Classic pagination -->
                <template v-else>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="pagination.current_page === 1"
                        @click="changePage(pagination.links.find((l) => isNavigationLink(l.label) && cleanPaginationLabel(l.label).toLowerCase() === 'previous')?.url ?? null)"
                    >
                        <ChevronLeft class="h-4 w-4" />
                        Previous
                    </Button>
                    <template v-for="(link, idx) in pagination.links" :key="idx">
                        <Button
                            v-if="!isNavigationLink(link.label)"
                            variant="outline"
                            size="sm"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                            :disabled="!link.url"
                            @click="changePage(link.url)"
                        >
                            {{ cleanPaginationLabel(link.label) }}
                        </Button>
                    </template>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="pagination.current_page === pagination.last_page"
                        @click="changePage(pagination.links.find((l) => isNavigationLink(l.label) && cleanPaginationLabel(l.label).toLowerCase() === 'next')?.url ?? null)"
                    >
                        Next
                        <ChevronRight class="h-4 w-4" />
                    </Button>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.data-table-wrapper {
    position: relative;
}

.data-table-pagination-top {
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.data-table-pagination {
    padding-top: 1rem;
    padding-bottom: 0.5rem;
}

.data-table-loading {
    position: absolute;
    inset: 0;
    background: hsl(var(--background) / 0.8);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    z-index: 10;
    border-radius: 0.375rem;
}

.data-table {
    table-layout: fixed;
    min-width: 0;
    width: 100%;
}

.data-table th,
.data-table td {
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.data-table-th {
    padding: 0.375rem 0.5rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    border-bottom: 1px solid hsl(var(--border));
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.data-table-td {
    padding: 0.375rem 0.5rem;
    font-size: 0.875rem;
    overflow: hidden;
    text-overflow: ellipsis;
}

.data-table-empty {
    padding: 1rem 0.5rem;
    text-align: center;
    color: hsl(var(--muted-foreground));
}

/* EHRIS column constraints (same as EmployeeListing) */
.ehris-employee-table td,
.ehris-employee-table th {
    max-width: 0;
}

.ehris-employee-table th.ehris-th,
.ehris-employee-table td.ehris-td {
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Optional column width classes (match EHRIS design) */
.data-table .ehris-col-name {
    max-width: 10rem;
    min-width: 8rem;
}
.data-table .ehris-col-job {
    max-width: 9rem;
    min-width: 6rem;
}
.data-table .ehris-col-office {
    max-width: 10rem;
    min-width: 7rem;
}
.data-table .ehris-col-subject {
    max-width: 15rem;
    min-width: 10rem;
}
.data-table .ehris-col-leave {
    min-width: 5.5rem;
}
.data-table td.ehris-td:not(.ehris-col-name):not(.ehris-col-job):not(.ehris-col-office):not(.ehris-col-subject) {
    white-space: nowrap;
}

/* Accordion content row animation */
.accordion-content-row {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 1000px;
    }
}
</style>
