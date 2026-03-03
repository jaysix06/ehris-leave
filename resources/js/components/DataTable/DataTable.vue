<!--
  DataTable using datatables.net-vue3 with server-side processing.
  Supports custom cell rendering, accordion rows, and backend export buttons.
-->
<script setup lang="ts">
import { ref, onMounted, watch, nextTick, onUnmounted } from 'vue';
import { DataTablesCore } from '@/config/datatables';
import type { Config } from 'datatables.net';

export type DataTableColumn = {
    key: string;
    label: string;
    /** Optional class for th and td */
    class?: string;
    thClass?: string;
    tdClass?: string;
    /** Slot name for custom cell content */
    slot?: string;
    /** Width style, e.g. "8rem" or "minmax(100px, 1fr)" */
    width?: string;
    /** DataTables column data source (defaults to key) */
    data?: string;
    /** Custom render function for the column */
    render?: (data: unknown, type: string, row: any, meta: any) => string;
    /** Hide column from display but keep in data for exports (default: true) */
    visible?: boolean;
    /** Whether column is sortable (default: true) */
    orderable?: boolean;
};


const props = withDefaults(
    defineProps<{
        /** Column definitions */
        columns: DataTableColumn[];
        /** Server-side processing API URL */
        ajaxUrl?: string;
        /** Function to get query params for ajax requests */
        getAjaxParams?: () => Record<string, string | undefined>;
        /** Row key for :key (e.g. "id", "hrid") */
        rowKey: string;
        /** Show loading overlay when fetching */
        loading?: boolean;
        /** Per-page options */
        perPageOptions?: number[];
        /** Message when no data */
        emptyMessage?: string;
        /** Function to check if a row is expanded (for accordion) */
        isRowExpanded?: (row: unknown) => boolean;
        /** Row click handler */
        onRowClick?: (row: unknown) => void;
        /** Row class function */
        rowClass?: (row: unknown) => string | string[];
        /** Show built-in export buttons (CSV, Excel, Print) */
        showExportButtons?: boolean;
        /** Custom cell renderers - function that returns HTML string */
        cellRenderers?: Record<string, (row: any, value: any, type?: string) => string>;
        /** Accordion content renderer - function that returns HTML string */
        accordionRenderer?: (row: any) => string;
        /** Default order - array of [columnIndex, direction] where direction is 'asc' or 'desc' */
        defaultOrder?: [number, 'asc' | 'desc'];
    }>(),
    {
        loading: false,
        perPageOptions: () => [10, 25, 50, 100],
        emptyMessage: 'No records found',
        isRowExpanded: undefined,
        onRowClick: undefined,
        rowClass: undefined,
        showExportButtons: false,
        cellRenderers: undefined,
        accordionRenderer: undefined,
        defaultOrder: () => [0, 'asc'],
    },
);

const emit = defineEmits<{
    'row-click': [row: unknown];
    'row-expand': [row: unknown];
    'row-collapse': [row: unknown];
    'row-toggle': [row: unknown, isExpanded: boolean];
}>();

const tableRef = ref<HTMLTableElement | null>(null);
let dataTableInstance: any = null;
const expandedRows = ref<Set<string | number>>(new Set());

// Toggle row expansion
function toggleRow(row: any, rowElement?: HTMLElement) {
    const rowKey = row._raw?.[props.rowKey];
    if (!rowKey || !dataTableInstance) return;
    
    let rowEl = rowElement;
    if (!rowEl && tableRef.value) {
        rowEl = tableRef.value.querySelector(`tr[data-row-key="${rowKey}"]`) as HTMLElement;
    }
    
    if (!rowEl) return;
    
    const wasExpanded = expandedRows.value.has(rowKey);
    if (wasExpanded) {
        // Close the current row
        expandedRows.value.delete(rowKey);
        dataTableInstance.row(rowEl).child.hide();
        rowEl.classList.remove('expanded');
        emit('row-collapse', row._raw);
        emit('row-toggle', row._raw, false);
    } else {
        // Close all previously expanded rows first (only one open at a time)
        expandedRows.value.forEach((expandedKey) => {
            if (expandedKey !== rowKey) {
                const expandedRowEl = tableRef.value?.querySelector(`tr[data-row-key="${expandedKey}"]`) as HTMLElement;
                if (expandedRowEl) {
                    expandedRows.value.delete(expandedKey);
                    dataTableInstance.row(expandedRowEl).child.hide();
                    expandedRowEl.classList.remove('expanded');
                }
            }
        });
        
        // Open the new row
        expandedRows.value.add(rowKey);
        if (props.accordionRenderer) {
            const content = props.accordionRenderer(row._raw);
            dataTableInstance.row(rowEl).child(content).show();
        }
        rowEl.classList.add('expanded');
        emit('row-expand', row._raw);
        emit('row-toggle', row._raw, true);
    }
    
    // Redraw to update icons
    dataTableInstance.draw(false);
}

// Initialize DataTable
onMounted(() => {
    if (!tableRef.value || !props.ajaxUrl) return;
    
    nextTick(() => {
        const table = tableRef.value;
        if (!table) return;
        
        // Build DataTables columns configuration
        const dtColumns = props.columns.map((col) => {
            const columnConfig: any = {
                data: col.data || col.key,
                title: col.label,
                className: col.class || col.tdClass || '',
                orderable: col.orderable !== false,
                searchable: col.orderable !== false,
            };
            
            // Hide column if visible is false (but keep in data for exports)
            if (col.visible === false) {
                columnConfig.visible = false;
            }
            
            // Custom render function
            if (col.render) {
                columnConfig.render = col.render;
            } else if (col.slot && props.cellRenderers?.[col.slot]) {
                columnConfig.render = (data: unknown, type: string, row: any) => {
                    // For exports (csv, excel, print, export), return plain data without HTML
                    const isExport = type === 'export' || type === 'csv' || type === 'excel' || type === 'print' || 
                                    type === 'xlsx' || type === 'xls';
                    
                    if (isExport) {
                        // For exports, get plain text value directly from the data
                        return data ?? '';
                    }
                    if (type === 'display' || type === 'type' || !type) {
                        return props.cellRenderers![col.slot!](row._raw, data, type);
                    }
                    return data ?? '';
                };
            } else if (col.slot) {
                // Default renderer for slots
                columnConfig.render = (data: unknown, type: string, row: any) => {
                    if (type === 'display' || type === 'type') {
                        return String(data ?? '-');
                    }
                    return data ?? '';
                };
            }
            
            if (col.width) {
                columnConfig.width = col.width;
            }
            
            return columnConfig;
        });
        
        // Helper function to strip HTML tags from text
        const stripHtml = (html: string): string => {
            const tmp = document.createElement('DIV');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        };

        // Built-in DataTables buttons configuration
        const buttons: any[] = [];
        
        if (props.showExportButtons) {
            buttons.push(
                {
                    extend: 'csv',
                    title: 'Employee Listing Reports',
                    filename: 'Employee Listing Reports',
                    exportOptions: {
                        format: {
                            body: (data: any, row: number, column: number, node: any) => {
                                // Strip HTML from cell content for CSV export
                                if (typeof data === 'string' && data.includes('<')) {
                                    return stripHtml(data);
                                }
                                return data ?? '';
                            },
                        },
                    },
                },
                {
                    extend: 'excel',
                    title: 'Employee Listing Reports',
                    filename: 'Employee Listing Reports',
                    exportOptions: {
                        format: {
                            body: (data: any, row: number, column: number, node: any) => {
                                // Strip HTML from cell content for Excel export
                                if (typeof data === 'string' && data.includes('<')) {
                                    return stripHtml(data);
                                }
                                return data ?? '';
                            },
                        },
                    },
                },
                {
                    extend: 'print',
                    title: 'Employee Listing Reports',
                    exportOptions: {
                        format: {
                            body: (data: any, row: number, column: number, node: any) => {
                                // Strip HTML from cell content for Print export
                                if (typeof data === 'string' && data.includes('<')) {
                                    return stripHtml(data);
                                }
                                return data ?? '';
                            },
                        },
                    },
                }
            );
        }
        
        // DataTables configuration
        const dtConfig: any = {
            processing: true,
            serverSide: true,
            searchDelay: 400, // Wait 400ms after user stops typing before searching (dynamic search)
            ajax: {
                url: props.ajaxUrl,
                type: 'GET',
                data: (d: any) => {
                    // Merge DataTables params with custom params
                    // IMPORTANT: Preserve DataTables search parameter
                    const customParams = props.getAjaxParams?.() || {};
                    
                    // Extract search from DataTables params to preserve it
                    const dataTablesSearch = d.search;
                    
                    // Merge params (custom params may override some DataTables params)
                    const merged = {
                        ...d, // DataTables params come first
                        ...customParams, // Custom params override
                    };
                    
                    // Always preserve DataTables search parameter (it should never be overridden)
                    if (dataTablesSearch) {
                        merged.search = dataTablesSearch;
                    }
                    
                    return merged;
                },
                dataSrc: (json: any) => {
                    // Debug: Log the response
                    console.log('DataTables Response:', json);
                    
                    // Check if response has error
                    if (json.error) {
                        console.error('DataTables AJAX Error:', json.error);
                        return [];
                    }
                    
                    // Check if data exists and is an array
                    if (!json || !json.data) {
                        console.warn('DataTables: No data in response', json);
                        return [];
                    }
                    
                    // Return data array
                    return Array.isArray(json.data) ? json.data : [];
                },
                error: (xhr: any, error: string, thrown: string) => {
                    console.error('DataTables AJAX Request Failed:', {
                        error,
                        thrown,
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        url: props.ajaxUrl,
                    });
                    // Show user-friendly error message
                    alert(`Failed to load data. Please check the console for details. Status: ${xhr.status}`);
                },
            },
            columns: dtColumns,
            pageLength: props.perPageOptions[0] || 10,
            lengthMenu: [
                props.perPageOptions.map(opt => opt === -1 ? -1 : opt),
                props.perPageOptions.map(opt => opt === -1 ? 'All' : String(opt))
            ],
            order: [props.defaultOrder || [0, 'asc']],
            language: {
                emptyTable: props.emptyMessage,
                processing: '<div class="flex items-center justify-center gap-2"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div><span>Loading...</span></div>',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                search: 'Search:',
                searchPlaceholder: 'Search ID or Name...',
                paginate: {
                    first: 'First',
                    last: 'Last',
                    next: 'Next',
                    previous: 'Previous',
                },
            },
            buttons: buttons.length > 0 ? {
                dom: {
                    button: {
                        className: 'px-3 py-1.5 text-sm font-medium rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground',
                    },
                },
                buttons: buttons,
            } : undefined,
            // Show pagination controls both at the top (in header row 2) and bottom (footer)
            // Top pagination: length menu on left, pagination on right
            dom: buttons.length > 0
                ? '<"dataTables-header"<"dataTables-header-row-1"<"dataTables-header-left"f><"dataTables-header-right"B>><"dataTables-header-row-2"<"dataTables-header-row-2-left"l><"dataTables-header-row-2-right"p>>>rt<"dataTables-footer"ip>'
                : '<"dataTables-header"<"dataTables-header-row-1"f><"dataTables-header-row-2"<"dataTables-header-row-2-left"l><"dataTables-header-row-2-right"p>>>rt<"dataTables-footer"ip>',
            responsive: true,
            rowCallback: (row: Node, data: any) => {
                const rowElement = row as HTMLElement;
                const rowKey = data._raw?.[props.rowKey];
                
                // Add data attribute for row identification
                if (rowKey) {
                    rowElement.setAttribute('data-row-key', String(rowKey));
                }
                
                // Apply row classes
                if (props.rowClass && data._raw) {
                    const classes = props.rowClass(data._raw);
                    if (typeof classes === 'string') {
                        rowElement.className += ` ${classes}`;
                    } else if (Array.isArray(classes)) {
                        rowElement.className += ` ${classes.join(' ')}`;
                    }
                }
                
                // Handle row click - toggle accordion on any row click
                if (data._raw) {
                    rowElement.style.cursor = 'pointer';
                    // Use a single event listener to avoid duplicates
                    const clickHandler = (e: Event) => {
                        // Toggle accordion when clicking anywhere on the row
                        if (props.accordionRenderer) {
                            toggleRow(data, rowElement);
                        }
                        // Also call custom row click handler if provided
                        if (props.onRowClick) {
                            props.onRowClick(data._raw);
                        }
                        emit('row-click', data._raw);
                    };
                    // Remove any existing listener and add new one
                    rowElement.removeEventListener('click', clickHandler);
                    rowElement.addEventListener('click', clickHandler);
                }
                
                // Handle accordion expansion state
                if (rowKey && expandedRows.value.has(rowKey)) {
                    rowElement.classList.add('expanded');
                    if (dataTableInstance && props.accordionRenderer) {
                        const content = props.accordionRenderer(data._raw);
                        dataTableInstance.row(rowElement).child(content).show();
                    }
                }
            },
            drawCallback: () => {
                // Note: Row click handlers are attached in rowCallback, so they're automatically
                // re-attached when DataTables redraws. The expand button is just visual - clicking
                // anywhere on the row will toggle the accordion.
            },
        };
        
        // Initialize DataTable
        // @ts-ignore - DataTables types
        dataTableInstance = new DataTablesCore(table, dtConfig);
        
        // Limit pagination to show only 3 page numbers (current page + 2 adjacent)
        const limitPagination = () => {
            const paginateContainer = table.querySelector('.dataTables_paginate');
            if (paginateContainer) {
                const pageButtons = Array.from(paginateContainer.querySelectorAll('.paginate_button:not(.first):not(.previous):not(.next):not(.last)')) as HTMLElement[];
                const currentButton = paginateContainer.querySelector('.paginate_button.current') as HTMLElement;
                
                if (currentButton && pageButtons.length > 0) {
                    const currentIndex = pageButtons.indexOf(currentButton);
                    
                    // Hide all page buttons first
                    pageButtons.forEach(btn => {
                        btn.style.display = 'none';
                    });
                    
                    // Show current page and up to 2 adjacent pages (total 3 pages)
                    const startIndex = Math.max(0, currentIndex - 1);
                    const endIndex = Math.min(pageButtons.length - 1, currentIndex + 1);
                    
                    for (let i = startIndex; i <= endIndex && i < startIndex + 3; i++) {
                        if (pageButtons[i]) {
                            pageButtons[i].style.display = 'inline-block';
                        }
                    }
                } else {
                    // Fallback: show first 3 pages if no current page found
                    pageButtons.slice(0, 3).forEach(btn => {
                        btn.style.display = 'inline-block';
                    });
                }
            }
        };
        
        // Limit pagination on initial draw and after each draw
        setTimeout(limitPagination, 100);
        dataTableInstance.on('draw', () => {
            setTimeout(limitPagination, 100);
        });
    });
});

// Watch for changes and reload
watch(
    () => [props.getAjaxParams?.(), props.ajaxUrl],
    () => {
        if (dataTableInstance) {
            dataTableInstance.ajax.reload();
        }
    },
    { deep: true },
);

// Watch for accordion state changes to update row rendering
watch(
    () => props.isRowExpanded,
    () => {
        if (dataTableInstance) {
            // Redraw to update cell renderers (especially name column with expand icon)
            dataTableInstance.draw(false);
        }
    },
    { deep: true },
);

// Cleanup on unmount
onUnmounted(() => {
    if (dataTableInstance) {
        dataTableInstance.destroy();
        dataTableInstance = null;
    }
});
</script>

<template>
    <div class="data-table-wrapper">
        <table ref="tableRef" class="display" style="width: 100%"></table>
    </div>
</template>

<style scoped>
.data-table-wrapper {
    position: relative;
}

/* Override DataTables styles to match existing design */
:deep(.dataTables_wrapper) {
    padding: 1rem;
}

/* Header container */
:deep(.dataTables-header) {
    margin-bottom: 1rem;
}

/* First row: Search on left, Buttons on right */
:deep(.dataTables-header-row-1) {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    gap: 1rem;
}

/* Header left: Search */
:deep(.dataTables-header-left) {
    flex: 1;
}

/* Header right: Buttons */
:deep(.dataTables-header-right) {
    display: flex;
    gap: 0.5rem;
}

/* Second row: Length menu on left, Pagination on right */
:deep(.dataTables-header-row-2) {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Header row 2 left: Length menu */
:deep(.dataTables-header-row-2-left) {
    flex: 0 0 auto;
}

/* Header row 2 right: Top pagination */
:deep(.dataTables-header-row-2-right) {
    flex: 0 0 auto;
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 0.25rem;
}

/* Search bar styling */
:deep(.dataTables_filter) {
    margin: 0;
    float: none;
    text-align: left;
}

:deep(.dataTables_filter label) {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 500;
    margin: 0;
}

:deep(.dataTables_filter input) {
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--input));
    border-radius: 0.375rem;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    font-size: 0.875rem;
    width: 250px;
    transition: border-color 0.2s;
}

:deep(.dataTables_filter input:focus) {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

/* Buttons container - positioned on the right */
:deep(.dt-buttons) {
    margin: 0;
    display: flex;
    gap: 0.5rem;
}

:deep(.dt-buttons .dt-button) {
    padding: 0.5rem 1rem;
    border: 1px solid hsl(var(--input));
    border-radius: 0.375rem;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
}

:deep(.dt-buttons .dt-button:hover) {
    background: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
}

/* Length menu styling */
:deep(.dataTables_length) {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

:deep(.dataTables_length label) {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-weight: 500;
}

:deep(.dataTables_length select) {
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--input));
    border-radius: 0.375rem;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    font-size: 0.875rem;
    cursor: pointer;
    transition: border-color 0.2s;
}

:deep(.dataTables_length select:focus) {
    outline: none;
    border-color: hsl(var(--ring));
    box-shadow: 0 0 0 2px hsl(var(--ring) / 0.2);
}

/* Footer section container */
:deep(.dataTables-footer) {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    gap: 1rem;
    flex-wrap: wrap;
}

:deep(.dataTables-footer .dataTables_info) {
    flex: 0 0 auto;
    white-space: nowrap;
}

:deep(.dataTables-footer .dataTables_paginate) {
    flex: 0 0 auto;
}

:deep(.dataTables_info) {
    margin: 0;
    color: hsl(var(--muted-foreground));
}

:deep(.dataTables_paginate) {
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    align-items: center;
}

/* Limit pagination - JavaScript handles showing only 3 page numbers */
/* Always show First, Previous, Next, Last buttons */
:deep(.dataTables_paginate .paginate_button.first),
:deep(.dataTables_paginate .paginate_button.previous),
:deep(.dataTables_paginate .paginate_button.next),
:deep(.dataTables_paginate .paginate_button.last) {
    display: inline-block !important;
}

:deep(.dataTables_paginate .paginate_button) {
    padding: 0.5rem 0.75rem;
    margin: 0;
    border: 1px solid hsl(var(--input));
    border-radius: 0.375rem;
    background: hsl(var(--background));
    color: hsl(var(--foreground));
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
}

:deep(.dataTables_paginate .paginate_button:hover) {
    background: hsl(var(--accent));
    color: hsl(var(--accent-foreground));
}

:deep(.dataTables_paginate .paginate_button.current) {
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
    border-color: hsl(var(--primary));
}

:deep(.dataTables_paginate .paginate_button.disabled) {
    opacity: 0.5;
    cursor: not-allowed;
}


/* Accordion row styles */
:deep(tr.expanded) {
    background-color: hsl(var(--muted) / 0.3);
}

:deep(tr.expanded .accordion-arrow),
:deep(tr.expanded .accordion-arrow-css) {
    transform: rotate(90deg);
    color: hsl(var(--primary));
}

:deep(tr.expanded .accordion-arrow-css)::after {
    color: hsl(var(--primary));
}

:deep(.child) {
    background-color: hsl(var(--muted) / 0.1);
    padding: 1rem;
}

/* Table styling */
:deep(table.dataTable) {
    border-collapse: collapse;
    width: 100%;
}

:deep(table.dataTable thead th) {
    background-color: hsl(var(--muted) / 0.5);
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
    border-bottom: 1px solid hsl(var(--border));
}

:deep(table.dataTable tbody td) {
    padding: 0.75rem;
    border-bottom: 1px solid hsl(var(--border));
}

:deep(table.dataTable tbody tr:hover) {
    background-color: hsl(var(--muted) / 0.5);
}
</style>
