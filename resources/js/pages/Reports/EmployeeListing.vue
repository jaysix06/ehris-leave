<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
        employmentStatus: ChartItem[];
        jobTitle: ChartItem[];
        school: ChartItem[];
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

// Computed properties
const fullName = (emp: Employee) => {
    const parts = [emp.firstname, emp.middlename, emp.lastname, emp.extension].filter(Boolean);
    return parts.join(' ');
};

const chartDataSafe = computed(() => ({
    employmentStatus: props.chartData?.employmentStatus ?? [],
    jobTitle: props.chartData?.jobTitle ?? [],
    school: props.chartData?.school ?? [],
}));

const employmentStatusChartData = computed(() => {
    const items = chartDataSafe.value.employmentStatus;
    return {
        labels: items.map((i) => i.label),
        datasets: [
            {
                data: items.map((i) => i.count),
                backgroundColor: getColors(items.length),
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
    const items = chartDataSafe.value.school;
    return {
        labels: items.map((i) => i.label),
        datasets: [
            {
                data: items.map((i) => i.count),
                backgroundColor: getColors(items.length),
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
        },
        {
            preserveState: true,
            preserveScroll: true,
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
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="rounded-lg border p-4 bg-card">
                        <h3 class="text-sm font-semibold text-muted-foreground mb-3">Employment Status</h3>
                        <div class="h-[240px]">
                            <Doughnut
                                v-if="employmentStatusChartData.labels.length"
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
                    </div>
                    <div class="rounded-lg border p-4 bg-card">
                        <h3 class="text-sm font-semibold text-muted-foreground mb-3">By School/Office</h3>
                        <div class="h-[240px]">
                            <Doughnut
                                v-if="schoolChartData.labels.length"
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
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-6 mb-6">
                    <div class="rounded-lg border p-4 bg-card">
                        <h3 class="text-sm font-semibold text-muted-foreground mb-3">Count per Job Title (top 12)</h3>
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
                <div class="rounded-md border overflow-x-auto w-full" style="max-width: 100%;">
                    <table class="ehris-employee-table w-full border-collapse">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="ehris-th">HRID</th>
                                <th class="ehris-th">Employee ID</th>
                                <th class="ehris-th ehris-col-name">Name</th>
                                <th class="ehris-th ehris-col-job">Job Title</th>
                                <th class="ehris-th">Subject</th>
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
                                <td class="ehris-td whitespace-nowrap">{{ employee.subject_taught || '-' }}</td>
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
}

.ehris-employee-table th,
.ehris-employee-table td {
    vertical-align: middle;
}

.ehris-th {
    padding: 0.375rem 0.5rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    border-bottom: 1px solid hsl(var(--border));
    white-space: nowrap;
}

.ehris-td {
    padding: 0.375rem 0.5rem;
    font-size: 0.875rem;
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
</style>
