<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    ChevronUp,
    Filter,
    RefreshCw,
} from 'lucide-vue-next';
import { Doughnut, Bar } from 'vue-chartjs';
import {
    ArcElement,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    Title,
    Tooltip,
} from 'chart.js';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { employeeListing } from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Title, Tooltip, Legend);

const pageTitle = 'Employee Listing & Reports';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reports',
    },
    {
        title: pageTitle,
    },
];

type Employee = {
    hrid: number;
    employee_id: number;
    firstname: string | null;
    middlename: string | null;
    lastname: string | null;
    extension: string | null;
    job_title: string | null;
    subject_taught: string | null;
    grade_level: string | null;
    office: string | null;
    station_code: string | null;
    salary_grade: number | null;
    salary_step: number | null;
    employ_status: string | null;
    leave_balance: number;
};

type PaginatedData = {
    data: Employee[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type ChartItem = { label: string; count: number };

type Props = {
    employees: PaginatedData;
    summaryStats: {
        total: number;
        permanent: number;
        avgLeaveBalance: string;
    };
    chartData?: {
        employmentStatus: {
            chart: ChartItem[];
            legend: ChartItem[];
            others: ChartItem[];
        };
        jobTitle: ChartItem[];
        school: {
            chart: ChartItem[];
            legend: ChartItem[];
            others: ChartItem[];
        };
    };
    filterOptions: {
        schools: string[];
        jobTitles: string[];
        subjects: string[];
        gradeLevels: string[];
        employmentStatuses: string[];
    };
    filters: {
        school?: string;
        job_title?: string;
        subject?: string;
        grade_level?: string;
        employment_status?: string;
        salary_grade?: string;
        search?: string;
    };
};

// Extended color palette with distinct, non-similar colors
const CHART_COLORS = [
    'hsl(217, 91%, 60%)',   // Blue
    'hsl(142, 71%, 45%)',   // Green
    'hsl(24, 95%, 53%)',    // Orange
    'hsl(280, 67%, 58%)',  // Purple
    'hsl(0, 72%, 51%)',     // Red
    'hsl(199, 89%, 48%)',   // Cyan
    'hsl(47, 96%, 53%)',    // Yellow
    'hsl(262, 83%, 58%)',   // Violet
    'hsl(330, 75%, 55%)',   // Pink
    'hsl(195, 85%, 50%)',   // Teal
    'hsl(30, 90%, 55%)',    // Orange-Red
    'hsl(160, 70%, 50%)',   // Turquoise
    'hsl(270, 70%, 60%)',   // Lavender
    'hsl(15, 95%, 50%)',    // Red-Orange
    'hsl(180, 75%, 45%)',   // Aqua
    'hsl(300, 65%, 55%)',   // Magenta
    'hsl(60, 90%, 50%)',    // Bright Yellow
    'hsl(210, 85%, 55%)',   // Sky Blue
    'hsl(120, 65%, 50%)',   // Lime Green
    'hsl(340, 80%, 55%)',   // Rose
    'hsl(240, 75%, 60%)',   // Indigo
    'hsl(50, 95%, 55%)',    // Gold
    'hsl(150, 60%, 45%)',   // Sea Green
    'hsl(290, 70%, 55%)',   // Orchid
    'hsl(20, 100%, 55%)',   // Bright Orange
    'hsl(200, 80%, 50%)',   // Ocean Blue
    'hsl(100, 70%, 50%)',   // Chartreuse
    'hsl(310, 75%, 60%)',   // Hot Pink
    'hsl(230, 80%, 55%)',   // Royal Blue
    'hsl(40, 95%, 50%)',    // Amber
    'hsl(170, 65%, 50%)',   // Mint
    'hsl(250, 70%, 60%)',   // Blue-Violet
    'hsl(10, 90%, 55%)',    // Coral
    'hsl(190, 75%, 50%)',   // Steel Blue
    'hsl(80, 75%, 50%)',    // Yellow-Green
    'hsl(320, 70%, 55%)',   // Deep Pink
    'hsl(220, 85%, 60%)',   // Light Blue
    'hsl(130, 60%, 50%)',   // Forest Green
    'hsl(260, 75%, 58%)',   // Blue-Purple
    'hsl(35, 90%, 55%)',    // Peach
    'hsl(140, 75%, 45%)',   // Emerald
    'hsl(275, 65%, 58%)',   // Plum
    'hsl(5, 85%, 55%)',     // Cherry Red
    'hsl(205, 80%, 55%)',   // Powder Blue
    'hsl(90, 70%, 50%)',    // Spring Green
    'hsl(315, 75%, 58%)',   // Fuchsia
    'hsl(225, 75%, 60%)',   // Periwinkle
    'hsl(55, 95%, 55%)',    // Canary Yellow
    'hsl(165, 70%, 50%)',   // Jade
    'hsl(255, 70%, 60%)',   // Slate Blue
    'hsl(25, 100%, 55%)',   // Tangerine
    'hsl(185, 75%, 50%)',   // Turquoise Blue
    'hsl(110, 65%, 50%)',   // Olive Green
    'hsl(285, 70%, 58%)',   // Medium Purple
    'hsl(0, 85%, 60%)',     // Light Red
    'hsl(215, 90%, 55%)',   // Cornflower Blue
    'hsl(125, 70%, 50%)',   // Medium Green
    'hsl(265, 75%, 60%)',   // Medium Slate Blue
    'hsl(45, 95%, 55%)',    // Bright Yellow-Orange
    'hsl(175, 65%, 50%)',   // Medium Turquoise
    'hsl(295, 70%, 58%)',   // Medium Orchid
];

function getColors(n: number) {
    // Use distinct colors from the palette, cycling only if we have more items than colors
    return Array.from({ length: n }, (_, i) => CHART_COLORS[i % CHART_COLORS.length]);
}

const props = defineProps<Props>();

// Filter states - initialize from props
const selectedSchool = ref<string>(props.filters.school || '');
const selectedJobTitle = ref<string>(props.filters.job_title || '');
const selectedSubject = ref<string>(props.filters.subject || '');
const selectedGradeLevel = ref<string>(props.filters.grade_level || '');
const selectedEmploymentStatus = ref<string>(props.filters.employment_status || '');
const selectedSalaryGrade = ref<string>(props.filters.salary_grade || '');
const searchQuery = ref<string>(props.filters.search || '');

// Note: Search is now handled by DataTables built-in search
// Table data ref - holds current table payload (same shape as props.employees)
// Initialized from props.employees and kept in sync
const tableData = ref<PaginatedData>(props.employees);

// API endpoint constant
const EMPLOYEE_LISTING_API = '/api/reports/employee-listing';

// Loading state for table operations
const isLoading = ref(false);

// Summary stats - reactive ref that updates when filters change
const summaryStatsData = ref<{
    total: number;
    permanent: number;
    avgLeaveBalance: string;
}>({
    total: props.summaryStats.total,
    permanent: props.summaryStats.permanent,
    avgLeaveBalance: props.summaryStats.avgLeaveBalance,
});

// Watch props.employees to sync tableData when filters change (Inertia updates)
watch(
    () => props.employees,
    (newEmployees) => {
        tableData.value = newEmployees;
    },
    { deep: true },
);

// Note: Pagination is now handled by DataTables server-side processing

// Empty message for DataTable
const emptyMessage = computed(() => {
    if (searchQuery.value) {
        return `No employees found matching "${searchQuery.value}"`;
    }
    return 'No employees found matching your criteria';
});

// Dropdown states for "others" records
const showEmploymentStatusOthers = ref(false);
const showSchoolOthers = ref(false);

// Track hidden items (items unchecked will be hidden from chart)
// Initialize with all items beyond top 5 hidden by default
const hiddenEmploymentStatus = ref<string[]>([]);
const hiddenSchools = ref<string[]>([]);

// Initialize hidden items when component mounts or data changes
const initializeHiddenItems = () => {
    if (props.chartData?.employmentStatus?.chart) {
        hiddenEmploymentStatus.value = props.chartData.employmentStatus.chart
            .slice(5)
            .map((item) => item.label);
    }
    if (props.chartData?.school?.chart) {
        hiddenSchools.value = props.chartData.school.chart
            .slice(5)
            .map((item) => item.label);
    }
};

// Initialize on mount
onMounted(() => {
    initializeHiddenItems();
});

// Watch for chartData changes
watch(
    () => props.chartData,
    () => {
        initializeHiddenItems();
    },
    { deep: true },
);

// Debounced search - auto-search as user types (for filter section)
const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 500);

// Watch searchQuery for dynamic search (filter section)
watch(searchQuery, () => {
    debouncedSearch();
});

// Note: Search is now handled by DataTables built-in search functionality

// Methods to toggle item visibility
const toggleEmploymentStatusVisibility = (label: string) => {
    const index = hiddenEmploymentStatus.value.indexOf(label);
    if (index > -1) {
        hiddenEmploymentStatus.value.splice(index, 1);
    } else {
        hiddenEmploymentStatus.value.push(label);
    }
};

const toggleSchoolVisibility = (label: string) => {
    const index = hiddenSchools.value.indexOf(label);
    if (index > -1) {
        hiddenSchools.value.splice(index, 1);
    } else {
        hiddenSchools.value.push(label);
    }
};

// Computed properties
const fullName = (emp: Employee) => {
    const parts = [emp.firstname, emp.middlename, emp.lastname, emp.extension].filter(Boolean);
    return parts.join(' ');
};

// Use tableData directly (server-side filtering via API)
const filteredEmployees = computed(() => {
    return tableData.value.data;
});

const chartDataSafe = computed(() => ({
    employmentStatus: {
        chart: props.chartData?.employmentStatus?.chart ?? [],
        legend: props.chartData?.employmentStatus?.legend ?? [],
        others: props.chartData?.employmentStatus?.others ?? [],
    },
    jobTitle: props.chartData?.jobTitle ?? [],
    school: {
        chart: props.chartData?.school?.chart ?? [],
        legend: props.chartData?.school?.legend ?? [],
        others: props.chartData?.school?.others ?? [],
    },
}));

const prevLink = computed(() => props.employees.links[0] ?? null);
const nextLink = computed(
    () => props.employees.links[props.employees.links.length - 1] ?? null,
);
const pageNumberLinks = computed(() => {
    if (props.employees.links.length <= 2) {
        return [];
    }
    return props.employees.links.slice(1, -1);
});

const employmentStatusChartData = computed(() => {
    const allItems = chartDataSafe.value.employmentStatus.chart;
    const visibleItems = allItems.filter(
        (item) => !hiddenEmploymentStatus.value.includes(item.label),
    );
    const allColors = getColors(allItems.length);
    
    return {
        labels: visibleItems.map((i) => i.label),
        datasets: [
            {
                data: visibleItems.map((i) => i.count),
                backgroundColor: visibleItems.map((item) => {
                    const originalIndex = allItems.findIndex((c) => c.label === item.label);
                    return allColors[originalIndex];
                }),
                borderWidth: 1,
            },
        ],
    };
});

const jobTitleChartData = computed(() => {
    const items = chartDataSafe.value.jobTitle;
    return {
        labels: items.map((i) => i.label),
        datasets: [
            {
                label: 'Employees',
                data: items.map((i) => i.count),
                backgroundColor: CHART_COLORS[0],
                borderWidth: 0,
            },
        ],
    };
});

const schoolChartData = computed(() => {
    const allItems = chartDataSafe.value.school.chart;
    const visibleItems = allItems.filter(
        (item) => !hiddenSchools.value.includes(item.label),
    );
    const allColors = getColors(allItems.length);
    
    return {
        labels: visibleItems.map((i) => i.label),
        datasets: [
            {
                data: visibleItems.map((i) => i.count),
                backgroundColor: visibleItems.map((item) => {
                    const originalIndex = allItems.findIndex((c) => c.label === item.label);
                    return allColors[originalIndex];
                }),
                borderWidth: 1,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const },
        tooltip: { enabled: true },
    },
};

const doughnutOptions = {
    ...chartOptions,
    cutout: '60%',
    plugins: {
        ...chartOptions.plugins,
        legend: {
            display: false,
        },
    },
};

const barOptions = {
    ...chartOptions,
    indexAxis: 'y' as const,
    scales: {
        x: { beginAtZero: true },
    },
};

// Function to fetch summary stats based on current filters
const fetchSummaryStats = async () => {
    try {
        const params = new URLSearchParams();
        const filters = {
            school: selectedSchool.value,
            job_title: selectedJobTitle.value,
            subject: selectedSubject.value,
            grade_level: selectedGradeLevel.value,
            employment_status: selectedEmploymentStatus.value,
            salary_grade: selectedSalaryGrade.value,
        };

        Object.entries(filters).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });

        const response = await fetch(`/api/reports/employee-listing/summary-stats?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            const data = await response.json();
            summaryStatsData.value = data;
        }
    } catch (error) {
        console.error('Failed to fetch summary stats:', error);
    }
};

// Methods
const applyFilters = () => {
    // DataTable will auto-reload when getAjaxParams changes (watched by DataTable component)
    // Fetch updated summary stats based on current filters
    fetchSummaryStats();
    // No need to reload full page - graphs stay static and unaffected by filters
    // The computed getAjaxParams will update when filter refs change, triggering DataTable reload
};

const clearFilters = () => {
    selectedSchool.value = '';
    selectedJobTitle.value = '';
    selectedSubject.value = '';
    selectedGradeLevel.value = '';
    selectedEmploymentStatus.value = '';
    selectedSalaryGrade.value = '';
    searchQuery.value = '';
    applyFilters();
};

const employeeColumns: DataTableColumn[] = [
    { key: 'hrid', label: 'HRID', width: '5rem', data: 'hrid' },
    { key: 'employee_id', label: 'Employee ID', width: '7rem', data: 'employee_id' },
    { key: 'name', label: 'Name', slot: 'name', class: 'ehris-col-name', width: '20rem', data: 'name' },
    { key: 'job_title', label: 'Job Title', slot: 'job_title', class: 'ehris-col-job', width: '15rem', data: 'job_title' },
    // Additional info previously shown in accordion, now as table columns
    { key: 'subject_taught', label: 'Subjects', data: 'subject_taught', width: '12rem' },
    { key: 'grade_level', label: 'Grade Level', data: 'grade_level', width: '8rem' },
    { key: 'office', label: 'School/Office', data: 'office', width: '14rem' },
    { key: 'station_code', label: 'Station Code', data: 'station_code', width: '8rem' },
    { key: 'salary_grade', label: 'SG', data: 'salary_grade', width: '5rem' },
    { key: 'salary_step', label: 'Step', data: 'salary_step', width: '5rem' },
    { key: 'employ_status', label: 'Status', slot: 'employ_status', width: '10rem', data: 'employ_status' },
    { key: 'leave_balance', label: 'Leave Balance', class: 'ehris-col-leave', slot: 'leave_balance', width: '9rem', data: 'leave_balance' },
];

// Cell renderers for DataTables - must be functions, not computed
const cellRenderers = {
    name: (row: Employee, value: any) => {
        const name = fullName(row);
        return `<span title="${name}">${name}</span>`;
    },
    job_title: (row: Employee, value: any) => {
        const jobTitle = value || '-';
        return `<span class="inline-flex items-center rounded-md border border-input bg-background px-2 py-1 text-xs font-medium whitespace-nowrap max-w-full truncate">${jobTitle}</span>`;
    },
    employ_status: (row: Employee, value: any) => {
        const status = value || '-';
        const variant = status === 'Permanent' ? 'default' : 'secondary';
        const bgColor = variant === 'default' ? 'bg-primary text-primary-foreground' : 'bg-secondary text-secondary-foreground';
        return `<span class="inline-flex items-center rounded-md ${bgColor} px-2 py-1 text-xs font-medium">${status}</span>`;
    },
    leave_balance: (row: Employee, value: any) => {
        const balance = (value as number) ?? 0;
        const variant = balance < 5 ? 'destructive' : 'outline';
        const bgColor = variant === 'destructive' ? 'bg-destructive text-destructive-foreground' : 'border-input bg-background';
        return `<span class="inline-flex items-center rounded-md border ${bgColor} px-2 py-1 text-xs font-medium">${balance} days</span>`;
    },
};

const changePage = async (url: string | null) => {
    if (!url) return;
    
    isLoading.value = true;
    try {
        // Build API URL from pagination URL
        const apiUrl = new URL(url, window.location.origin);
        apiUrl.pathname = EMPLOYEE_LISTING_API;
        
        const response = await fetch(apiUrl.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        
        if (response.ok) {
            const data = await response.json();
            tableData.value = data;
        } else {
            // Fallback to Inertia on error
            router.get(url, {}, {
                only: ['employees'],
                preserveState: true,
                preserveScroll: true,
            });
        }
    } catch (error) {
        console.error('Failed to fetch page:', error);
        // Fallback to Inertia on fetch error
        router.get(url, {}, {
            only: ['employees'],
            preserveState: true,
            preserveScroll: true,
        });
    } finally {
        isLoading.value = false;
    }
};

const changePerPage = async (perPage: number) => {
    isLoading.value = true;
    try {
        // Build query string with current filters and new per_page
        const queryParams = new URLSearchParams();
        
        if (props.filters.school) queryParams.append('school', props.filters.school);
        if (props.filters.job_title) queryParams.append('job_title', props.filters.job_title);
        if (props.filters.subject) queryParams.append('subject', props.filters.subject);
        if (props.filters.grade_level) queryParams.append('grade_level', props.filters.grade_level);
        if (props.filters.employment_status) queryParams.append('employment_status', props.filters.employment_status);
        if (props.filters.salary_grade) queryParams.append('salary_grade', props.filters.salary_grade);
        if (props.filters.search) queryParams.append('search', props.filters.search);
        
        queryParams.append('page', '1'); // Reset to first page
        queryParams.append('per_page', perPage.toString());
        
        const apiUrl = `${EMPLOYEE_LISTING_API}?${queryParams.toString()}`;
        
        const response = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
        
        if (response.ok) {
            const data = await response.json();
            tableData.value = data;
        } else {
            // Fallback to Inertia on error
            router.get(
                employeeListing().url,
                {
                    ...props.filters,
                    per_page: perPage,
                },
                {
                    only: ['employees'],
                    preserveState: true,
                    preserveScroll: true,
                },
            );
        }
    } catch (error) {
        console.error('Failed to change per page:', error);
        // Fallback to Inertia on fetch error
        router.get(
            employeeListing().url,
            {
                ...props.filters,
                per_page: perPage,
            },
            {
                only: ['employees'],
                preserveState: true,
                preserveScroll: true,
            },
        );
    } finally {
        isLoading.value = false;
    }
};

// Helper function to clean HTML entities from pagination labels
const cleanPaginationLabel = (label: string): string => {
    return label
        .replace(/&laquo;/g, '')
        .replace(/&raquo;/g, '')
        .replace(/&lsaquo;/g, '')
        .replace(/&rsaquo;/g, '')
        .trim();
};

// Helper function to check if a link is a Previous/Next navigation link
const isNavigationLink = (label: string): boolean => {
    const cleaned = cleanPaginationLabel(label).toLowerCase();
    return cleaned === 'previous' || cleaned === 'next';
};

// Get AJAX params for DataTables (excludes DataTables search - handled separately by DataTables)
// Uses local refs so DataTable auto-reloads when filters change, without affecting graphs
const getAjaxParams = computed(() => () => ({
    school: selectedSchool.value || undefined,
    job_title: selectedJobTitle.value || undefined,
    subject: selectedSubject.value || undefined,
    grade_level: selectedGradeLevel.value || undefined,
    employment_status: selectedEmploymentStatus.value || undefined,
    salary_grade: selectedSalaryGrade.value || undefined,
    // Note: search is NOT included here - DataTables handles its own search parameter
}));
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
                        Comprehensive personnel listing and reporting system for HR management.
                        Quickly find employees by school, position, department, or any criteria.
                        Perfect for queries like "Show all teachers in a school" or "List all permanent employees".
                    </p>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="border border-border rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Analytics & Reports</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Visual insights into employee distribution and statistics.
                    </p>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 items-start">
                    <div class="rounded-lg border p-4 bg-white">
                        <h3 class="text-sm font-semibold text-muted-foreground mb-3">Employment Status</h3>
                        <div class="h-[240px]">
                            <Doughnut
                                v-if="employmentStatusChartData.labels.length"
                                :key="`employment-${hiddenEmploymentStatus.length}-${employmentStatusChartData.labels.length}`"
                                :data="employmentStatusChartData"
                                :options="doughnutOptions"
                            />
                            <div
                                v-else
                                class="h-full flex items-center justify-center text-muted-foreground text-sm"
                            >
                                No data
                            </div>
                        </div>
                        <!-- Custom Legend: Top 5 + Display -->
                        <div class="mt-4">
                            <div class="flex flex-wrap gap-3 items-center mb-2">
                                <template
                                    v-for="item in chartDataSafe.employmentStatus.legend"
                                    :key="item.label"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-4 h-4 rounded"
                                            :style="{
                                                backgroundColor: getColors(
                                                    chartDataSafe.employmentStatus.chart.length,
                                                )[chartDataSafe.employmentStatus.chart.findIndex(
                                                    (c) => c.label === item.label,
                                                )],
                                            }"
                                        ></div>
                                        <span class="text-xs text-muted-foreground">{{ item.label }}</span>
                                    </div>
                                </template>
                                <button
                                    v-if="chartDataSafe.employmentStatus.chart.length > 4 || chartDataSafe.employmentStatus.others.length > 0"
                                    @click="showEmploymentStatusOthers = !showEmploymentStatusOthers"
                                    class="flex items-center gap-2 px-2 py-1 rounded border border-border bg-background hover:bg-muted/50 text-xs font-medium text-foreground hover:text-primary transition-colors"
                                >
                                    <span>Others ({{ chartDataSafe.employmentStatus.others.length || chartDataSafe.employmentStatus.chart.length - 4 }})</span>
                                    <span class="text-xs">{{ showEmploymentStatusOthers ? '▼' : '▶' }}</span>
                                </button>
                            </div>
                            <!-- Display Dropdown - Show ALL items -->
                            <transition
                                enter-active-class="transition-all duration-300 ease-out"
                                enter-from-class="opacity-0 max-h-0 overflow-hidden"
                                enter-to-class="opacity-100"
                                leave-active-class="transition-all duration-300 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0 max-h-0 overflow-hidden"
                            >
                                <div
                                    v-if="showEmploymentStatusOthers && chartDataSafe.employmentStatus.chart.length > 0"
                                    class="mt-2 max-h-48 overflow-y-auto overflow-x-hidden border rounded-md bg-white"
                                    style="max-height: 12rem;"
                                >
                                <table class="w-full text-sm" style="table-layout: fixed;">
                                    <thead class="sticky top-0 z-20 bg-white border-b">
                                        <tr>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-muted-foreground whitespace-nowrap" style="width: 3rem; min-width: 3rem;">
                                                Show
                                            </th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-muted-foreground whitespace-nowrap" style="width: 4rem; min-width: 4rem;">
                                                Color
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-muted-foreground whitespace-nowrap" style="min-width: 8rem;">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="item in chartDataSafe.employmentStatus.chart"
                                            :key="item.label"
                                            class="border-b hover:bg-muted/30"
                                            :class="{
                                                'opacity-50': hiddenEmploymentStatus.includes(item.label),
                                            }"
                                        >
                                            <td class="px-3 py-2 text-center" style="width: 3rem; min-width: 3rem;">
                                                <input
                                                    type="checkbox"
                                                    :checked="!hiddenEmploymentStatus.includes(item.label)"
                                                    @change="toggleEmploymentStatusVisibility(item.label)"
                                                    class="w-4 h-4 rounded border-input cursor-pointer"
                                                />
                                            </td>
                                            <td class="px-3 py-2 text-center" style="width: 4rem; min-width: 4rem;">
                                                <div
                                                    class="w-4 h-4 rounded mx-auto"
                                                    :style="{
                                                        backgroundColor: getColors(
                                                            chartDataSafe.employmentStatus.chart.length,
                                                        )[
                                                            chartDataSafe.employmentStatus.chart.findIndex(
                                                                (c) => c.label === item.label,
                                                            )
                                                        ],
                                                    }"
                                                ></div>
                                            </td>
                                            <td class="px-3 py-2" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ item.label }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>
                            </transition>
                        </div>
                    </div>
                    <div class="rounded-lg border p-4 bg-white">
                        <h3 class="text-sm font-semibold text-muted-foreground mb-3">By School/Office</h3>
                        <div class="h-[240px]">
                            <Doughnut
                                v-if="schoolChartData.labels.length"
                                :key="`school-${hiddenSchools.length}-${schoolChartData.labels.length}`"
                                :data="schoolChartData"
                                :options="doughnutOptions"
                            />
                            <div
                                v-else
                                class="h-full flex items-center justify-center text-muted-foreground text-sm"
                            >
                                No data
                            </div>
                        </div>
                        <!-- Custom Legend: Top 5 + Display -->
                        <div class="mt-4">
                            <div class="flex flex-wrap gap-3 items-center mb-2">
                                <template
                                    v-for="item in chartDataSafe.school.legend"
                                    :key="item.label"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-4 h-4 rounded"
                                            :style="{
                                                backgroundColor: getColors(
                                                    chartDataSafe.school.chart.length,
                                                )[chartDataSafe.school.chart.findIndex(
                                                    (c) => c.label === item.label,
                                                )],
                                            }"
                                        ></div>
                                        <span class="text-xs text-muted-foreground">{{ item.label }}</span>
                                    </div>
                                </template>
                                <button
                                    v-if="chartDataSafe.school.chart.length > 4 || chartDataSafe.school.others.length > 0"
                                    @click="showSchoolOthers = !showSchoolOthers"
                                    class="flex items-center gap-2 px-2 py-1 rounded border border-border bg-background hover:bg-muted/50 text-xs font-medium text-foreground hover:text-primary transition-colors"
                                >
                                    <span>Others ({{ chartDataSafe.school.others.length || chartDataSafe.school.chart.length - 4 }})</span>
                                    <span class="text-xs">{{ showSchoolOthers ? '▼' : '▶' }}</span>
                                </button>
                            </div>
                            <!-- Display Dropdown - Show ALL items -->
                            <transition
                                enter-active-class="transition-all duration-300 ease-out"
                                enter-from-class="opacity-0 max-h-0 overflow-hidden"
                                enter-to-class="opacity-100"
                                leave-active-class="transition-all duration-300 ease-in"
                                leave-from-class="opacity-100"
                                leave-to-class="opacity-0 max-h-0 overflow-hidden"
                            >
                                <div
                                    v-if="showSchoolOthers && chartDataSafe.school.chart.length > 0"
                                    class="mt-2 max-h-48 overflow-y-auto overflow-x-hidden border rounded-md bg-white"
                                    style="max-height: 12rem;"
                                >
                                <table class="w-full text-sm" style="table-layout: fixed;">
                                    <thead class="sticky top-0 z-20 bg-white border-b">
                                        <tr>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-muted-foreground whitespace-nowrap" style="width: 3rem; min-width: 3rem;">
                                                Show
                                            </th>
                                            <th class="px-3 py-2 text-center text-xs font-semibold text-muted-foreground whitespace-nowrap" style="width: 4rem; min-width: 4rem;">
                                                Color
                                            </th>
                                            <th class="px-3 py-2 text-left text-xs font-semibold text-muted-foreground whitespace-nowrap" style="min-width: 8rem;">
                                                School/Office
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="item in chartDataSafe.school.chart"
                                            :key="item.label"
                                            class="border-b hover:bg-muted/30"
                                            :class="{
                                                'opacity-50': hiddenSchools.includes(item.label),
                                            }"
                                        >
                                            <td class="px-3 py-2 text-center" style="width: 3rem; min-width: 3rem;">
                                                <input
                                                    type="checkbox"
                                                    :checked="!hiddenSchools.includes(item.label)"
                                                    @change="toggleSchoolVisibility(item.label)"
                                                    class="w-4 h-4 rounded border-input cursor-pointer"
                                                />
                                            </td>
                                            <td class="px-3 py-2 text-center" style="width: 4rem; min-width: 4rem;">
                                                <div
                                                    class="w-4 h-4 rounded mx-auto"
                                                    :style="{
                                                        backgroundColor: getColors(
                                                            chartDataSafe.school.chart.length,
                                                        )[
                                                            chartDataSafe.school.chart.findIndex(
                                                                (c) => c.label === item.label,
                                                            )
                                                        ],
                                                    }"
                                                ></div>
                                            </td>
                                            <td class="px-3 py-2" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ item.label }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-6 mb-6">
                    <div class="rounded-lg border p-4 bg-white">
                        <h3 class="text-sm font-semibold text-muted-foreground mb-3">Count per Job Title (top 10)</h3>
                        <div class="h-[320px]">
                            <Bar
                                v-if="jobTitleChartData.labels.length"
                                :data="jobTitleChartData"
                                :options="barOptions"
                            />
                            <div
                                v-else
                                class="h-full flex items-center justify-center text-muted-foreground text-sm"
                            >
                                No data
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filter Section -->
            <section class="border border-border rounded-lg bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold flex items-center gap-2">
                            <Filter class="h-5 w-5" />
                            Search & Filter Criteria
                        </h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            Filter employees by any combination of criteria to generate comprehensive personnel reports.
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" @click="clearFilters">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Clear All
                    </Button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <!-- School Filter -->
                    <div class="space-y-2">
                        <Label>School/Office</Label>
                        <select
                            v-model="selectedSchool"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">All Schools/Offices</option>
                            <option v-for="school in filterOptions.schools" :key="school" :value="school">
                                {{ school }}
                            </option>
                        </select>
                    </div>

                    <!-- Job Title Filter -->
                    <div class="space-y-2">
                        <Label>Job Title</Label>
                        <select
                            v-model="selectedJobTitle"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">All Job Titles</option>
                            <option v-for="title in filterOptions.jobTitles" :key="title" :value="title">
                                {{ title }}
                            </option>
                        </select>
                    </div>

                    <!-- Subject Filter -->
                    <div class="space-y-2">
                        <Label>Subject Taught</Label>
                        <select
                            v-model="selectedSubject"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">All Subjects</option>
                            <option v-for="subject in filterOptions.subjects" :key="subject" :value="subject">
                                {{ subject }}
                            </option>
                        </select>
                    </div>

                    <!-- Grade Level Filter -->
                    <div class="space-y-2">
                        <Label>Grade Level</Label>
                        <select
                            v-model="selectedGradeLevel"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">All Grade Levels</option>
                            <option v-for="level in filterOptions.gradeLevels" :key="level" :value="level">
                                {{ level }}
                            </option>
                        </select>
                    </div>

                    <!-- Employment Status Filter -->
                    <div class="space-y-2">
                        <Label>Employment Status</Label>
                        <select
                            v-model="selectedEmploymentStatus"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">All Status</option>
                            <option
                                v-for="status in filterOptions.employmentStatuses"
                                :key="status"
                                :value="status"
                            >
                                {{ status }}
                            </option>
                        </select>
                    </div>

                    <!-- Salary Grade Filter -->
                    <div class="space-y-2">
                        <Label>Salary Grade</Label>
                        <select
                            v-model="selectedSalaryGrade"
                            @change="applyFilters"
                            class="w-full rounded-md border border-input bg-white px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">All Salary Grades</option>
                            <option v-for="grade in [11, 12, 13, 14, 15, 16, 17, 18, 19, 20]" :key="grade" :value="grade.toString()">
                                SG {{ grade }}
                            </option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Report Results Section -->
            <section class="border border-border rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Employee Listing Results</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Showing {{ tableData.from }} to {{ tableData.to }} of {{ tableData.total }} records
                    </p>
                </div>

                <!-- Summary Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="rounded-lg border p-4 bg-white">
                        <div class="text-sm text-muted-foreground">Total Employees</div>
                        <div class="text-2xl font-bold mt-1 text-primary">{{ summaryStatsData.total }}</div>
                    </div>
                    <div class="rounded-lg border p-4 bg-white">
                        <div class="text-sm text-muted-foreground">Permanent</div>
                        <div class="text-2xl font-bold mt-1 text-primary">{{ summaryStatsData.permanent }}</div>
                    </div>
                    <div class="rounded-lg border p-4 bg-white">
                        <div class="text-sm text-muted-foreground">Avg Leave Balance</div>
                        <div class="text-2xl font-bold mt-1 text-primary">{{ summaryStatsData.avgLeaveBalance }}</div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="rounded-md border overflow-x-auto w-full">
                    <DataTable
                        :columns="employeeColumns"
                        ajax-url="/api/reports/employee-listing/datatables"
                        :get-ajax-params="getAjaxParams"
                        row-key="hrid"
                        :loading="isLoading"
                        :empty-message="emptyMessage"
                        :show-export-buttons="true"
                        :cell-renderers="cellRenderers"
                        :per-page-options="[10, 25, 50, 100, -1]"
                    />
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.ehris-employee-table {
    table-layout: fixed;
    width: 100%;
}

.ehris-employee-table th,
.ehris-employee-table td {
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.ehris-th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-size: 0.8125rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    border-bottom: 1px solid hsl(var(--border));
    white-space: nowrap;
}

.ehris-th:not(.ehris-col-name):not(.ehris-col-job):not(.ehris-col-leave) {
    overflow: hidden;
    text-overflow: ellipsis;
}

.ehris-td {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}

/* Ensure consistent spacing for HRID and Employee ID columns */
.ehris-employee-table th:nth-child(1),
.ehris-employee-table td:nth-child(1) {
    padding-right: 1.5rem;
    min-width: 5rem;
    width: 5rem;
}

.ehris-employee-table th:nth-child(2),
.ehris-employee-table td:nth-child(2) {
    padding-left: 1.5rem;
    padding-right: 1rem;
    min-width: 7rem;
    width: 7rem;
}

.ehris-col-name {
    min-width: 12rem;
    max-width: 20rem;
}

.ehris-col-job {
    min-width: 10rem;
    max-width: 15rem;
}

.ehris-col-leave {
    min-width: 9rem;
    width: 9rem;
}

.ehris-td:not(.ehris-col-name):not(.ehris-col-job):not(.ehris-col-leave) {
    white-space: nowrap;
}

.accordion-content-row {
    animation: slideDown 0.2s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 500px;
    }
}
</style>
