<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import {
    Download,
    FileText,
    Filter,
    Printer,
    RefreshCw,
    Search,
    ChevronLeft,
    ChevronRight,
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
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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

const CHART_COLORS = [
    'hsl(217, 91%, 60%)',
    'hsl(262, 83%, 58%)',
    'hsl(24, 95%, 53%)',
    'hsl(142, 71%, 45%)',
    'hsl(199, 89%, 48%)',
    'hsl(280, 67%, 58%)',
    'hsl(0, 72%, 51%)',
    'hsl(47, 96%, 53%)',
];
function getColors(n: number) {
    return Array.from({ length: n }, (_, i) => CHART_COLORS[i % CHART_COLORS.length]);
}

const props = defineProps<Props>();

const page = usePage();

// Filter states - initialize from props
const selectedSchool = ref<string>(props.filters.school || '');
const selectedJobTitle = ref<string>(props.filters.job_title || '');
const selectedSubject = ref<string>(props.filters.subject || '');
const selectedGradeLevel = ref<string>(props.filters.grade_level || '');
const selectedEmploymentStatus = ref<string>(props.filters.employment_status || '');
const selectedSalaryGrade = ref<string>(props.filters.salary_grade || '');
const searchQuery = ref<string>(props.filters.search || '');

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

// Methods
const applyFilters = () => {
    // Reset chart hidden items when filters change (new data will come from server)
    hiddenEmploymentStatus.value = [];
    hiddenSchools.value = [];
    
    router.get(
        employeeListing().url,
        {
            school: selectedSchool.value || undefined,
            job_title: selectedJobTitle.value || undefined,
            subject: selectedSubject.value || undefined,
            grade_level: selectedGradeLevel.value || undefined,
            employment_status: selectedEmploymentStatus.value || undefined,
            salary_grade: selectedSalaryGrade.value || undefined,
            search: searchQuery.value || undefined,
            page: 1, // Reset to first page when filters change
        },
        {
            preserveState: false, // Don't preserve state to ensure fresh data
            preserveScroll: false, // Don't preserve scroll position
            replace: false, // Allow browser history
            onSuccess: () => {
                // Re-initialize hidden items after new data loads
                initializeHiddenItems();
            },
        },
    );
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

const changePage = (url: string | null) => {
    if (url) {
        router.get(url, {}, { preserveState: true, preserveScroll: true });
    }
};

const changePerPage = (perPage: number) => {
    router.get(
        employeeListing().url,
        {
            ...props.filters,
            per_page: perPage,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const exportReport = (format: 'pdf' | 'excel' | 'csv') => {
    // TODO: Implement export functionality
    console.log(`Exporting as ${format}`);
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page">
            <!-- Page Header -->
            <section class="ehris-card">
                <div class="mb-4">
                    <h1 class="text-3xl font-bold">{{ pageTitle }}</h1>
                    <p class="text-muted-foreground mt-1">
                        Comprehensive personnel listing and reporting system for HR management.
                        Quickly find employees by school, position, department, or any criteria.
                        Perfect for queries like "Show all teachers in a school" or "List all permanent employees".
                    </p>
                </div>
            </section>

            <!-- Filter Section -->
            <section class="ehris-card">
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
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
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
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
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
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
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
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
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
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
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
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">All Salary Grades</option>
                            <option v-for="grade in [11, 12, 13, 14, 15, 16, 17, 18, 19, 20]" :key="grade" :value="grade.toString()">
                                SG {{ grade }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Search and Action Buttons -->
                <div class="flex items-end gap-4">
                    <div class="flex-1 space-y-2">
                        <Label>Search Employee</Label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="searchQuery"
                                @keyup.enter="applyFilters"
                                placeholder="Search by name or employee ID..."
                                class="pl-10"
                            />
                        </div>
                    </div>
                    <Button @click="applyFilters" class="min-w-[140px]">
                        <Search class="mr-2 h-4 w-4" />
                        Search Employees
                    </Button>
                </div>
            </section>

            <!-- Report Results Section -->
            <section class="ehris-card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold">Employee Listing Results</h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            Showing {{ employees.from }} to {{ employees.to }} of {{ employees.total }} records
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" @click="exportReport('csv')">
                            <Download class="mr-2 h-4 w-4" />
                            CSV
                        </Button>
                        <Button variant="outline" size="sm" @click="exportReport('excel')">
                            <Download class="mr-2 h-4 w-4" />
                            Excel
                        </Button>
                        <Button variant="outline" size="sm" @click="exportReport('pdf')">
                            <Printer class="mr-2 h-4 w-4" />
                            Print
                        </Button>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="rounded-lg border p-4 bg-card">
                        <div class="text-sm text-muted-foreground">Total Employees</div>
                        <div class="text-2xl font-bold mt-1 text-primary">{{ summaryStats.total }}</div>
                    </div>
                    <div class="rounded-lg border p-4 bg-card">
                        <div class="text-sm text-muted-foreground">Permanent</div>
                        <div class="text-2xl font-bold mt-1 text-primary">{{ summaryStats.permanent }}</div>
                    </div>
                    <div class="rounded-lg border p-4 bg-card">
                        <div class="text-sm text-muted-foreground">Avg Leave Balance</div>
                        <div class="text-2xl font-bold mt-1 text-primary">{{ summaryStats.avgLeaveBalance }}</div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6 items-start">
                    <div class="rounded-lg border p-4 bg-card">
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
                                    v-for="(item, index) in chartDataSafe.employmentStatus.legend"
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
                                    class="mt-2 max-h-48 overflow-y-auto overflow-x-hidden border rounded-md bg-background"
                                    style="max-height: 12rem;"
                                >
                                <table class="w-full text-sm" style="table-layout: fixed;">
                                    <thead class="sticky top-0 z-20 bg-background border-b">
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
                                            v-for="(item, index) in chartDataSafe.employmentStatus.chart"
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
                    <div class="rounded-lg border p-4 bg-card">
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
                                    v-for="(item, index) in chartDataSafe.school.legend"
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
                                    class="mt-2 max-h-48 overflow-y-auto overflow-x-hidden border rounded-md bg-background"
                                    style="max-height: 12rem;"
                                >
                                <table class="w-full text-sm" style="table-layout: fixed;">
                                    <thead class="sticky top-0 z-20 bg-background border-b">
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
                                            v-for="(item, index) in chartDataSafe.school.chart"
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
                    <div class="rounded-lg border p-4 bg-card">
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

                <!-- Data Table -->
                <div class="rounded-md border overflow-x-auto w-full">
                    <table class="ehris-employee-table w-full border-collapse" style="min-width: 1200px;">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="ehris-th">HRID</th>
                                <th class="ehris-th">Employee ID</th>
                                <th class="ehris-th ehris-col-name">Name</th>
                                <th class="ehris-th ehris-col-job">Job Title</th>
                                <th class="ehris-th ehris-col-subject">Subject</th>
                                <th class="ehris-th">Grade Level</th>
                                <th class="ehris-th ehris-col-office">School/Office</th>
                                <th class="ehris-th">Station Code</th>
                                <th class="ehris-th">Salary Grade</th>
                                <th class="ehris-th">Salary Step</th>
                                <th class="ehris-th">Status</th>
                                <th class="ehris-th ehris-col-leave">Leave Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="employee in employees.data"
                                :key="employee.hrid"
                                class="hover:bg-muted/50 border-b"
                            >
                                <td class="ehris-td whitespace-nowrap">{{ employee.hrid }}</td>
                                <td class="ehris-td whitespace-nowrap">{{ employee.employee_id }}</td>
                                <td class="ehris-td ehris-col-name" :title="fullName(employee)">{{ fullName(employee) }}</td>
                                <td class="ehris-td ehris-col-job">
                                    <Badge variant="outline" class="whitespace-nowrap max-w-full truncate inline-block">{{ employee.job_title || '-' }}</Badge>
                                </td>
                                <td class="ehris-td ehris-col-subject" :title="employee.subject_taught || ''">{{ employee.subject_taught || '-' }}</td>
                                <td class="ehris-td whitespace-nowrap">{{ employee.grade_level || '-' }}</td>
                                <td class="ehris-td ehris-col-office" :title="employee.office || ''">{{ employee.office || '-' }}</td>
                                <td class="ehris-td whitespace-nowrap">{{ employee.station_code || '-' }}</td>
                                <td class="ehris-td whitespace-nowrap">
                                    {{ employee.salary_grade ? 'SG ' + employee.salary_grade : '-' }}
                                </td>
                                <td class="ehris-td whitespace-nowrap">{{ employee.salary_step || '-' }}</td>
                                <td class="ehris-td whitespace-nowrap">
                                    <Badge
                                        :variant="employee.employ_status === 'Permanent' ? 'default' : 'secondary'"
                                    >
                                        {{ employee.employ_status || '-' }}
                                    </Badge>
                                </td>
                                <td class="ehris-td ehris-col-leave whitespace-nowrap">
                                    <Badge
                                        :variant="(employee.leave_balance || 0) < 5 ? 'destructive' : 'outline'"
                                    >
                                        {{ employee.leave_balance ?? 0 }} days
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="employees.data.length === 0">
                                <td colspan="12" class="px-4 py-8 text-center text-muted-foreground">
                                    No employees found matching your criteria
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between mt-4 pt-4 border-t">
                    <div class="flex items-center gap-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ employees.from }} to {{ employees.to }} of {{ employees.total }} results
                        </div>
                        <div class="flex items-center gap-2">
                            <Label class="text-sm">Per page:</Label>
                            <select
                                :value="employees.per_page"
                                @change="changePerPage(Number(($event.target as HTMLSelectElement).value))"
                                class="rounded-md border border-input bg-background px-2 py-1 text-sm"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="employees.current_page === 1"
                            @click="changePage(employees.links.find((l) => l.label === '&laquo; Previous')?.url || null)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Previous
                        </Button>
                        <template v-for="(link, index) in employees.links" :key="index">
                            <Button
                                v-if="link.label !== '&laquo; Previous' && link.label !== 'Next &raquo;'"
                                variant="outline"
                                size="sm"
                                :class="{ 'bg-primary text-primary-foreground': link.active }"
                                :disabled="!link.url"
                                @click="changePage(link.url)"
                            >
                                {{ link.label }}
                            </Button>
                        </template>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="employees.current_page === employees.last_page"
                            @click="changePage(employees.links.find((l) => l.label === 'Next &raquo;')?.url || null)"
                        >
                            Next
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.ehris-page {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.ehris-card {
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    background: hsl(var(--card));
    padding: 1.5rem;
}

.ehris-employee-table {
    table-layout: fixed;
    min-width: 0;
    width: 100%;
}

.ehris-employee-table th,
.ehris-employee-table td {
    vertical-align: middle;
    word-wrap: break-word;
    overflow-wrap: break-word;
}

.ehris-th {
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

.ehris-td {
    padding: 0.375rem 0.5rem;
    font-size: 0.875rem;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ehris-td:not(.ehris-col-name):not(.ehris-col-job):not(.ehris-col-office):not(.ehris-col-subject) {
    white-space: nowrap;
}

.ehris-col-name {
    max-width: 10rem;
    min-width: 8rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ehris-col-job {
    max-width: 9rem;
    min-width: 6rem;
}

.ehris-col-office {
    max-width: 10rem;
    min-width: 7rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.ehris-col-leave {
    min-width: 5.5rem;
}

.ehris-col-subject {
    max-width: 15rem;
    min-width: 10rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Ensure table cells don't overflow - max-width: 0 allows fixed table layout to work properly */
.ehris-employee-table td {
    max-width: 0;
}

.ehris-employee-table th {
    max-width: 0;
}

.ehris-employee-table th.ehris-th,
.ehris-employee-table td.ehris-td {
    overflow: hidden;
    text-overflow: ellipsis;
}

.ehris-employee-table th.ehris-th:not(.ehris-col-name):not(.ehris-col-job):not(.ehris-col-office):not(.ehris-col-subject):not(.ehris-col-leave),
.ehris-employee-table td.ehris-td:not(.ehris-col-name):not(.ehris-col-job):not(.ehris-col-office):not(.ehris-col-subject):not(.ehris-col-leave) {
    white-space: nowrap;
}
</style>
