<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import { useDebounceFn, onClickOutside } from '@vueuse/core';
import { ChevronDown, Filter, Plus, RefreshCw } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import utilitiesRoutes from '@/routes/utilities';
import type { BreadcrumbItem } from '@/types';
import { toast } from 'vue3-toastify';

const pageTitle = 'Utilities - Employee List';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Utilities',
    },
    {
        title: pageTitle,
    },
];

type Props = {
    filterOptions: {
        schools: string[];
        schoolsGrouped?: { district: string; districtCode: number; offices: string[] }[];
        jobTitles: string[];
        subjects: string[];
        gradeLevels: string[];
        employmentStatuses: string[];
        roles: string[];
        departments: Array<{ id: number; name: string }>;
        businessUnits: Array<{ id: string; name: string }>;
        reportingManagers: Array<{ hrid: number; name: string; job_title: string }>;
        nextHrid: number;
    };
};

const props = defineProps<Props>();

// Filter states - initialize from props
const selectedSchool = ref<string>('');
const selectedDistrictCode = ref<number | null>(null);
const selectedJobTitle = ref<string>('');
const selectedSubject = ref<string>('');
const selectedGradeLevel = ref<string>('');
const selectedEmploymentStatus = ref<string>('');
const selectedSalaryGrade = ref<string>('');
const selectedRole = ref<string>('');
const selectedStatus = ref<string>('');
const searchQuery = ref<string>('');

// School/Office accordion selector (open state and ref for click-outside)
const schoolSelectorOpen = ref(false);
const schoolSelectorRef = ref<HTMLElement | null>(null);
// Only one district expanded at a time (accordion behavior)
const openDistrictCode = ref<number | null>(null);

// Track focus state for dropdown icons animation
const focusedSelect = ref<string | null>(null);

// Loading state
const isLoading = ref(false);

// Table key for forcing reload
const tableKey = ref(0);

// Summary stats - reactive ref that updates when filters change
const summaryStatsData = ref<{
    total: number;
    permanent: number;
    avgLeaveBalance: string;
}>({
    total: 0,
    permanent: 0,
    avgLeaveBalance: '0.0',
});

// Create Employee Modal
const createModalOpen = ref(false);
const isCreating = ref(false);
const newEmployee = ref({
    hrid: props.filterOptions?.nextHrid || 21400,
    employee_id: '',
    prefix_name: '',
    firstname: '',
    lastname: '',
    middlename: '',
    extension: '',
    email: '',
    phone_num: '',
    mobile_num: '',
    department_id: null as number | null,
    business_id: null as string | null,
    reporting_manager: '',
    role: '',
    mode_of_employment: '',
    job_title: '',
    employ_status: '',
    date_of_original_appointment: '',
    date_of_leaving: '',
    year_experience: '',
});

// Store employees for action handlers
const employeesMap = ref<Map<number, any>>(new Map());

// DataTable columns
const employeeColumns: DataTableColumn[] = [
    { key: 'hrid', label: 'HRID', width: '6rem', data: 'hrid' },
    { key: 'employee_id', label: 'Employee No', width: '8rem', data: 'employee_id' },
    { key: 'name', label: 'Name', slot: 'name', width: '18rem', data: 'name' },
    { key: 'job_title', label: 'Job Title', width: '15rem', data: 'job_title' },
    { key: 'office', label: 'Office/School', width: '15rem', data: 'office' },
    { key: 'employ_status', label: 'Employment Status', width: '12rem', data: 'employ_status' },
    { key: 'actions', label: 'Actions', slot: 'actions', width: '10rem', orderable: false },
];

// Cell renderers
const cellRenderers = {
    name: (row: any, value: any, type?: string) => {
        const employee = row._raw;
        const fullName = trim(implode(' ', array_filter([
            employee?.firstname,
            employee?.middlename,
            employee?.lastname,
            employee?.extension,
        ]))) || value || '';
        
        if (type === 'export' || type === 'csv' || type === 'excel' || type === 'print') {
            return fullName;
        }
        return `<span title="${escapeHtml(fullName)}">${escapeHtml(fullName)}</span>`;
    },
    actions: (row: any, value: any, type?: string) => {
        if (type === 'export' || type === 'csv' || type === 'excel' || type === 'print') {
            return '';
        }
        const employee = row._raw;
        const hrid = employee?.hrid;
        
        // Store employee in map for later access
        if (hrid) {
            employeesMap.value.set(hrid, employee);
        }
        
        return `
            <div class="flex items-center justify-end gap-2" data-hrid="${hrid}">
                <button class="edit-employee-btn p-1 rounded hover:bg-muted" data-hrid="${hrid}" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <button class="delete-employee-btn p-1 rounded hover:bg-destructive/10 text-destructive" data-hrid="${hrid}" title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        `;
    },
};

// Helper functions
function escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function trim(str: string): string {
    return str?.trim() || '';
}

function implode(separator: string, array: any[]): string {
    return array.filter(Boolean).join(separator);
}

function array_filter(array: any[]): any[] {
    return array.filter(item => item != null && item !== '');
}

// Get AJAX params for DataTables (excludes DataTables search - handled separately by DataTables)
const getAjaxParams = computed(() => () => ({
    school: selectedSchool.value || undefined,
    district: selectedDistrictCode.value != null ? String(selectedDistrictCode.value) : undefined,
    job_title: selectedJobTitle.value || undefined,
    subject: selectedSubject.value || undefined,
    grade_level: selectedGradeLevel.value || undefined,
    employment_status: selectedEmploymentStatus.value || undefined,
    salary_grade: selectedSalaryGrade.value || undefined,
    role: selectedRole.value || undefined,
}));

// Debounced search
const debouncedSearch = useDebounceFn(() => {
    tableKey.value++;
}, 500);

watch(searchQuery, () => {
    debouncedSearch();
});

// Function to fetch summary stats based on current filters
const fetchSummaryStats = async () => {
    try {
        const params = new URLSearchParams();
        const filters = {
            school: selectedSchool.value,
            district: selectedDistrictCode.value != null ? String(selectedDistrictCode.value) : undefined,
            job_title: selectedJobTitle.value,
            subject: selectedSubject.value,
            grade_level: selectedGradeLevel.value,
            employment_status: selectedEmploymentStatus.value,
            salary_grade: selectedSalaryGrade.value,
            role: selectedRole.value,
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

// Apply filters
const applyFilters = () => {
    tableKey.value++;
    fetchSummaryStats();
};

const clearFilters = () => {
    selectedSchool.value = '';
    selectedDistrictCode.value = null;
    selectedJobTitle.value = '';
    selectedSubject.value = '';
    selectedGradeLevel.value = '';
    selectedEmploymentStatus.value = '';
    selectedSalaryGrade.value = '';
    selectedRole.value = '';
    searchQuery.value = '';
    applyFilters();
};

function selectSchool(office: string) {
    selectedSchool.value = office;
    selectedDistrictCode.value = null;
    applyFilters();
    schoolSelectorOpen.value = false;
    openDistrictCode.value = null;
}

function selectDistrict(code: number) {
    if (selectedDistrictCode.value === code) {
        // Click same district again: collapse and clear filter (back to default / all)
        selectedDistrictCode.value = null;
        openDistrictCode.value = null;
        applyFilters();
    } else {
        selectedDistrictCode.value = code;
        selectedSchool.value = '';
        openDistrictCode.value = code;
        applyFilters();
    }
}

// Display label for School/Office selector (district name, school name, or "All")
const schoolSelectorLabel = computed(() => {
    if (selectedDistrictCode.value != null && props.filterOptions?.schoolsGrouped?.length) {
        const group = props.filterOptions.schoolsGrouped.find((g) => g.districtCode === selectedDistrictCode.value);
        return group?.district ?? String(selectedDistrictCode.value);
    }
    return selectedSchool.value || 'All Schools/Offices';
});

// Create Employee
const openCreateModal = () => {
    newEmployee.value = {
        hrid: props.filterOptions?.nextHrid || 21400,
        employee_id: '',
        prefix_name: '',
        firstname: '',
        lastname: '',
        middlename: '',
        extension: '',
        email: '',
        phone_num: '',
        mobile_num: '',
        department_id: null,
        business_id: null,
        reporting_manager: '',
        role: '',
        mode_of_employment: '',
        job_title: '',
        employ_status: '',
        date_of_original_appointment: '',
        date_of_leaving: '',
        year_experience: '',
    };
    createModalOpen.value = true;
};

const closeCreateModal = () => {
    if (isCreating.value) return;
    createModalOpen.value = false;
};

const createEmployee = async () => {
    if (!newEmployee.value.hrid || !newEmployee.value.firstname || !newEmployee.value.lastname) {
        toast.error('Please fill in all required fields (HR ID, Firstname, Lastname).');
        return;
    }

    isCreating.value = true;

    try {
        const response = await fetch('/api/utilities/employee-list', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(newEmployee.value),
        });

        const data = await response.json();

        if (response.ok) {
            closeCreateModal();
            toast.success('Employee created successfully.');
            tableKey.value++;
        } else {
            const errorMessage = data?.message || data?.errors?.hrid?.[0] || data?.errors?.firstname?.[0] || data?.errors?.lastname?.[0] || 'Failed to create employee.';
            toast.error(errorMessage);
        }
    } catch (error) {
        toast.error('Failed to create employee. Please try again.');
    } finally {
        isCreating.value = false;
    }
};

// Edit Employee - Navigate to My Details page
const editEmployee = (employee: any) => {
    const hrid = employee?.hrid;
    if (!hrid) return;
    
    // Navigate to My Details page with the employee's HRID
    router.visit(`/my-details?hrid=${hrid}`);
};

// Delete Employee
const deleteEmployee = async (employee: any) => {
    const hrid = employee?.hrid;
    if (!hrid) return;

    const fullName = trim(implode(' ', array_filter([
        employee?.firstname,
        employee?.middlename,
        employee?.lastname,
        employee?.extension,
    ]))) || 'Employee';

    if (!confirm(`Are you sure you want to delete ${fullName} (HRID: ${hrid})? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch(`/api/utilities/employee-list/${hrid}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
        });

        if (response.ok) {
            toast.success('Employee deleted successfully.');
            tableKey.value++;
        } else {
            const data = await response.json();
            const errorMessage = data?.message || 'Failed to delete employee.';
            toast.error(errorMessage);
        }
    } catch (error) {
        toast.error('Failed to delete employee. Please try again.');
    }
};

// Handle table row clicks for edit/delete buttons
onMounted(() => {
    // Use event delegation for dynamically created buttons
    document.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;
        const editBtn = target.closest('.edit-employee-btn');
        const deleteBtn = target.closest('.delete-employee-btn');

        if (editBtn) {
            e.preventDefault();
            e.stopPropagation();
            const hrid = parseInt(editBtn.getAttribute('data-hrid') || '0');
            const employee = employeesMap.value.get(hrid);
            if (employee) {
                editEmployee(employee);
            }
        }

        if (deleteBtn) {
            e.preventDefault();
            e.stopPropagation();
            const hrid = parseInt(deleteBtn.getAttribute('data-hrid') || '0');
            const employee = employeesMap.value.get(hrid);
            if (employee) {
                deleteEmployee(employee);
            }
        }
    });
});

// Watch for DataTable reload
watch(tableKey, () => {
    // Force DataTable to reload
    isLoading.value = true;
    setTimeout(() => {
        isLoading.value = false;
    }, 100);
});

// Fetch summary stats on mount
onMounted(() => {
    fetchSummaryStats();
});
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 flex flex-col gap-6">
            <!-- Page Header -->
            <section class="border border-border rounded-lg bg-card p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold">{{ pageTitle }}</h1>
                        <p class="text-muted-foreground mt-1">
                            Manage all employee records. Create new employees and edit employee details with full activity logging.
                        </p>
                    </div>
                    <Button @click="openCreateModal" class="flex items-center gap-2">
                        <Plus class="h-4 w-4" />
                        Add New Employee
                    </Button>
                </div>
            </section>

            <!-- Filter Section -->
            <section class="border border-border rounded-lg bg-card p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold flex items-center gap-2">
                            <Filter class="h-5 w-5" />
                            Search & Filter Criteria
                        </h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            Filter employees by any combination of criteria to generate comprehensive reports.
                        </p>
                    </div>
                    <Button variant="ghost" size="sm" @click="clearFilters">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        Clear All
                    </Button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <!-- School Filter: accordion by District (click district to expand schools) -->
                    <div class="space-y-2 relative" ref="schoolSelectorRef">
                        <Label>School/Office</Label>
                        <template v-if="filterOptions.schoolsGrouped?.length">
                            <button
                                type="button"
                                class="ehris-school-select w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring flex items-center justify-between text-left"
                                @click="schoolSelectorOpen = !schoolSelectorOpen"
                            >
                                <span class="truncate">{{ schoolSelectorLabel }}</span>
                                <ChevronDown class="h-4 w-4 shrink-0 opacity-50" :class="{ 'rotate-180': schoolSelectorOpen }" />
                            </button>
                            <div
                                v-show="schoolSelectorOpen"
                                class="absolute z-50 mt-1 left-0 right-0 rounded-md border border-border bg-popover text-popover-foreground shadow-md max-h-80 overflow-auto"
                            >
                                <div class="p-1">
                                    <button
                                        type="button"
                                        class="w-full text-left px-3 py-2 text-sm rounded-md hover:bg-accent hover:text-accent-foreground"
                                        :class="{ 'bg-accent': !selectedSchool && selectedDistrictCode == null }"
                                        @click="selectedSchool = ''; selectedDistrictCode = null; applyFilters();"
                                    >
                                        All Schools/Offices
                                    </button>
                                    <div
                                        v-for="group in filterOptions.schoolsGrouped"
                                        :key="group.districtCode"
                                        class="mt-0.5"
                                    >
                                        <button
                                            type="button"
                                            class="ehris-school-accordion-trigger flex w-full items-center justify-between px-3 py-2 text-sm font-semibold text-primary rounded-md hover:bg-accent hover:text-accent-foreground"
                                            :class="{ 'bg-accent': selectedDistrictCode === group.districtCode }"
                                            @click="selectDistrict(group.districtCode)"
                                        >
                                            {{ group.district }}
                                            <ChevronDown
                                                class="h-4 w-4 shrink-0 transition-transform duration-200 ehris-accordion-chevron"
                                                :class="{ 'rotate-180': openDistrictCode === group.districtCode }"
                                            />
                                        </button>
                                        <div
                                            v-show="openDistrictCode === group.districtCode"
                                            class="pl-2 pb-1"
                                        >
                                            <button
                                                v-for="office in group.offices"
                                                :key="office"
                                                type="button"
                                                class="w-full text-left px-3 py-1.5 text-sm rounded-md hover:bg-accent hover:text-accent-foreground"
                                                :class="{ 'bg-accent': selectedSchool === office }"
                                                @click="selectSchool(office)"
                                            >
                                                {{ office }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <select
                            v-else
                            v-model="selectedSchool"
                            @change="applyFilters"
                            class="ehris-school-select w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">All Schools/Offices</option>
                            <option v-for="school in filterOptions.schools" :key="school" :value="school">
                                {{ school }}
                            </option>
                        </select>
                    </div>

                    <!-- Job Title Filter -->
                    <div class="space-y-2 relative">
                        <Label>Job Title</Label>
                        <select
                            v-model="selectedJobTitle"
                            @change="applyFilters"
                            @focus="focusedSelect = 'jobTitle'"
                            @blur="focusedSelect = null"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring appearance-none"
                        >
                            <option value="">All Job Titles</option>
                            <option v-for="title in filterOptions.jobTitles" :key="title" :value="title">
                                {{ title }}
                            </option>
                        </select>
                        <ChevronDown 
                            class="absolute right-3 top-[calc(0.5rem+1.25rem+0.625rem)] h-4 w-4 shrink-0 opacity-50 pointer-events-none transition-transform duration-200" 
                            :class="{ 'rotate-180': focusedSelect === 'jobTitle' }" 
                        />
                    </div>

                    <!-- Subject Filter -->
                    <div class="space-y-2 relative">
                        <Label>Subject Taught</Label>
                        <select
                            v-model="selectedSubject"
                            @change="applyFilters"
                            @focus="focusedSelect = 'subject'"
                            @blur="focusedSelect = null"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring appearance-none"
                        >
                            <option value="">All Subjects</option>
                            <option v-for="subject in filterOptions.subjects" :key="subject" :value="subject">
                                {{ subject }}
                            </option>
                        </select>
                        <ChevronDown 
                            class="absolute right-3 top-[calc(0.5rem+1.25rem+0.625rem)] h-4 w-4 shrink-0 opacity-50 pointer-events-none transition-transform duration-200" 
                            :class="{ 'rotate-180': focusedSelect === 'subject' }" 
                        />
                    </div>

                    <!-- Grade Level Filter -->
                    <div class="space-y-2 relative">
                        <Label>Grade Level</Label>
                        <select
                            v-model="selectedGradeLevel"
                            @change="applyFilters"
                            @focus="focusedSelect = 'gradeLevel'"
                            @blur="focusedSelect = null"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring appearance-none"
                        >
                            <option value="">All Grade Levels</option>
                            <option v-for="level in filterOptions.gradeLevels" :key="level" :value="level">
                                {{ level }}
                            </option>
                        </select>
                        <ChevronDown 
                            class="absolute right-3 top-[calc(0.5rem+1.25rem+0.625rem)] h-4 w-4 shrink-0 opacity-50 pointer-events-none transition-transform duration-200" 
                            :class="{ 'rotate-180': focusedSelect === 'gradeLevel' }" 
                        />
                    </div>

                    <!-- Employment Status Filter -->
                    <div class="space-y-2 relative">
                        <Label>Employment Status</Label>
                        <select
                            v-model="selectedEmploymentStatus"
                            @change="applyFilters"
                            @focus="focusedSelect = 'employmentStatus'"
                            @blur="focusedSelect = null"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring appearance-none"
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
                        <ChevronDown 
                            class="absolute right-3 top-[calc(0.5rem+1.25rem+0.625rem)] h-4 w-4 shrink-0 opacity-50 pointer-events-none transition-transform duration-200" 
                            :class="{ 'rotate-180': focusedSelect === 'employmentStatus' }" 
                        />
                    </div>

                    <!-- Salary Grade Filter -->
                    <div class="space-y-2 relative">
                        <Label>Salary Grade</Label>
                        <select
                            v-model="selectedSalaryGrade"
                            @change="applyFilters"
                            @focus="focusedSelect = 'salaryGrade'"
                            @blur="focusedSelect = null"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring appearance-none"
                        >
                            <option value="">All Salary Grades</option>
                            <option v-for="grade in [11, 12, 13, 14, 15, 16, 17, 18, 19, 20]" :key="grade" :value="grade.toString()">
                                SG {{ grade }}
                            </option>
                        </select>
                        <ChevronDown 
                            class="absolute right-3 top-[calc(0.5rem+1.25rem+0.625rem)] h-4 w-4 shrink-0 opacity-50 pointer-events-none transition-transform duration-200" 
                            :class="{ 'rotate-180': focusedSelect === 'salaryGrade' }" 
                        />
                    </div>

                    <!-- Role Filter -->
                    <div class="space-y-2 relative">
                        <Label>Role</Label>
                        <select
                            v-model="selectedRole"
                            @change="applyFilters"
                            @focus="focusedSelect = 'role'"
                            @blur="focusedSelect = null"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 pr-10 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring appearance-none"
                        >
                            <option value="">All Roles</option>
                            <option
                                v-for="role in filterOptions.roles"
                                :key="role"
                                :value="role"
                            >
                                {{ role }}
                            </option>
                        </select>
                        <ChevronDown 
                            class="absolute right-3 top-[calc(0.5rem+1.25rem+0.625rem)] h-4 w-4 shrink-0 opacity-50 pointer-events-none transition-transform duration-200" 
                            :class="{ 'rotate-180': focusedSelect === 'role' }" 
                        />
                    </div>

                </div>
            </section>

            <!-- Data Table Section -->
            <section class="border border-border rounded-lg bg-card p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold">Employee Listing Results</h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Showing filtered results based on your criteria.
                    </p>
                </div>

                <!-- Summary Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="rounded-lg border p-4 bg-card">
                        <div class="text-sm text-muted-foreground">Total Employees</div>
                        <div class="text-2xl font-bold mt-1 text-primary">{{ summaryStatsData.total }}</div>
                    </div>
                    <div class="rounded-lg border p-4 bg-card">
                        <div class="text-sm text-muted-foreground">Permanent</div>
                        <div class="text-2xl font-bold mt-1 text-primary">{{ summaryStatsData.permanent }}</div>
                    </div>
                    <div class="rounded-lg border p-4 bg-card">
                        <div class="text-sm text-muted-foreground">Avg Leave Balance</div>
                        <div class="text-2xl font-bold mt-1 text-primary">{{ summaryStatsData.avgLeaveBalance }}</div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="w-full overflow-x-auto rounded-md border p-4">
                    <DataTable
                        :key="tableKey"
                        :columns="employeeColumns"
                        ajax-url="/api/utilities/employee-list/datatables"
                        :get-ajax-params="getAjaxParams"
                        row-key="hrid"
                        :loading="isLoading"
                        empty-message="No employees found matching your criteria."
                        :cell-renderers="cellRenderers"
                        :per-page-options="[10, 25, 50, 100, -1]"
                        :default-order="[0, 'asc']"
                    />
                    </div>
                </section>
        </div>
    </AppLayout>

    <!-- Create Employee Modal -->
    <Dialog :open="createModalOpen" @update:open="(v) => (v ? (createModalOpen = true) : closeCreateModal())">
        <DialogContent class="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Create New Employee</DialogTitle>
                <DialogDescription>
                    Create a new employee record. All fields marked with * are required.
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Left Column -->
                <div class="space-y-4">
                    <div class="space-y-1">
                        <Label>HR ID *</Label>
                        <input
                            v-model.number="newEmployee.hrid"
                            type="number"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            required
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Employee No</Label>
                        <input
                            v-model.number="newEmployee.employee_id"
                            type="number"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Prefix Name</Label>
                        <select
                            v-model="newEmployee.prefix_name"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">- Select Prefix -</option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Prof.">Prof.</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <Label>Firstname *</Label>
                        <input
                            v-model="newEmployee.firstname"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            required
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Middlename</Label>
                        <input
                            v-model="newEmployee.middlename"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Lastname *</Label>
                        <input
                            v-model="newEmployee.lastname"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            required
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Extension Name</Label>
                        <select
                            v-model="newEmployee.extension"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">- Select Extension -</option>
                            <option value="Jr.">Jr.</option>
                            <option value="Sr.">Sr.</option>
                            <option value="II">II</option>
                            <option value="III">III</option>
                            <option value="IV">IV</option>
                        </select>
                    </div>
                </div>

                <!-- Middle Column -->
                <div class="space-y-4">
                    <div class="space-y-1">
                        <Label>DepEd Email</Label>
                        <input
                            v-model="newEmployee.email"
                            type="email"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="name@deped.gov.ph"
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Work Telephone Number</Label>
                        <input
                            v-model="newEmployee.phone_num"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="(02) 123-4567"
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Mobile Number</Label>
                        <input
                            v-model="newEmployee.mobile_num"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="0912-345-6789"
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Office Name</Label>
                        <select
                            v-model.number="newEmployee.department_id"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option :value="null">- Select Office -</option>
                            <option
                                v-for="dept in filterOptions.departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <Label>Business Name</Label>
                        <select
                            v-model="newEmployee.business_id"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option :value="null">- Select Business Unit -</option>
                            <option
                                v-for="unit in filterOptions.businessUnits"
                                :key="unit.id"
                                :value="unit.id"
                            >
                                {{ unit.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <Label>Department Name</Label>
                        <select
                            v-model.number="newEmployee.department_id"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option :value="null">- Select Department -</option>
                            <option
                                v-for="dept in filterOptions.departments"
                                :key="dept.id"
                                :value="dept.id"
                            >
                                {{ dept.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <Label>Reporting Manager</Label>
                        <select
                            v-model="newEmployee.reporting_manager"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">- Select Reporting Manager -</option>
                            <option
                                v-for="manager in filterOptions.reportingManagers"
                                :key="manager.hrid"
                                :value="manager.name"
                            >
                                {{ manager.name }} - {{ manager.job_title }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                    <div class="space-y-1">
                        <Label>Role</Label>
                        <select
                            v-model="newEmployee.role"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">- Select Role -</option>
                            <option
                                v-for="role in filterOptions.roles"
                                :key="role"
                                :value="role"
                            >
                                {{ role }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <Label>Mode of Employment</Label>
                        <select
                            v-model="newEmployee.mode_of_employment"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">- Select Mode of Employment -</option>
                            <option value="Permanent">Permanent</option>
                            <option value="Contractual">Contractual</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Casual">Casual</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <Label>Job Title</Label>
                        <select
                            v-model="newEmployee.job_title"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">- Select Job Title -</option>
                            <option
                                v-for="title in filterOptions.jobTitles"
                                :key="title"
                                :value="title"
                            >
                                {{ title }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <Label>Employment Status</Label>
                        <select
                            v-model="newEmployee.employ_status"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">- Select Employment Status -</option>
                            <option
                                v-for="status in filterOptions.employmentStatuses"
                                :key="status"
                                :value="status"
                            >
                                {{ status }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <Label>Date of Original Appointment</Label>
                        <input
                            v-model="newEmployee.date_of_original_appointment"
                            type="date"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Date of Leaving</Label>
                        <input
                            v-model="newEmployee.date_of_leaving"
                            type="date"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="space-y-1">
                        <Label>Number of Year Experience</Label>
                        <input
                            v-model.number="newEmployee.year_experience"
                            type="number"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            min="0"
                        />
                    </div>
                </div>
            </div>

            <DialogFooter class="mt-4">
                <Button variant="outline" :disabled="isCreating" @click="closeCreateModal">
                    Cancel
                </Button>
                <Button :disabled="isCreating" @click="createEmployee">
                    {{ isCreating ? 'Creating...' : 'Insert Record' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
