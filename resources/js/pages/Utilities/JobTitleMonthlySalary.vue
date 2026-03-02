<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, Pencil, Trash2, X } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'Job Title and Monthly Salary Control panel';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
    },
    {
        title: 'Job Title and Monthly Salary List',
    },
];

type JobTitle = {
    id: number;
    row_number: number;
    job_title: string;
};

type MonthlySalary = {
    id: number;
    row_number: number;
    salary_grade: number;
    salary_step: number;
    salary_amount: string;
};

const isLoadingJobTitles = ref(false);
const isLoadingSalaries = ref(false);

const emptyMessageJobTitles = computed(() => 'No job titles found');
const emptyMessageSalaries = computed(() => 'No monthly salaries found');

// Modal state for Add Job Title
const addJobTitleModalOpen = ref(false);
const newJobTitle = ref('');
const jobTitleError = ref('');
const isSubmittingJobTitle = ref(false);

// DataTable ref for reloading
const jobTitleTableRef = ref<any>(null);

// Escape HTML for safe rendering
function escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Cell renderers for Job Titles
const jobTitleCellRenderers = {
    actions: (row: JobTitle) => {
        return `
            <div class="flex gap-2 items-center">
                <button 
                    onclick="window.updateJobTitle(${row.id}, '${escapeHtml(row.job_title)}')"
                    class="inline-flex items-center text-blue-600 hover:text-blue-700 transition-colors text-xs"
                    title="Update"
                >
                    <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Update
                </button>
                <button 
                    onclick="window.deleteJobTitle(${row.id}, '${escapeHtml(row.job_title)}')"
                    class="inline-flex items-center text-red-600 hover:text-red-700 transition-colors text-xs"
                    title="Delete"
                >
                    <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                </button>
            </div>
        `;
    },
};

// Cell renderers for Monthly Salaries
const monthlySalaryCellRenderers = {
    actions: (row: MonthlySalary) => {
        return `
            <div class="flex gap-2 items-center">
                <button 
                    onclick="window.updateMonthlySalary(${row.id}, ${row.salary_grade}, ${row.salary_step}, '${row.salary_amount}')"
                    class="inline-flex items-center text-blue-600 hover:text-blue-700 transition-colors text-xs"
                    title="Update"
                >
                    <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Update
                </button>
                <button 
                    onclick="window.deleteMonthlySalary(${row.id})"
                    class="inline-flex items-center text-red-600 hover:text-red-700 transition-colors text-xs"
                    title="Delete"
                >
                    <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                </button>
            </div>
        `;
    },
};

const jobTitleColumns: DataTableColumn[] = [
    { key: 'row_number', label: '#', width: '3rem', data: 'row_number' },
    { key: 'job_title', label: 'Job Title', data: 'job_title' },
    { key: 'actions', label: 'Actions', slot: 'actions', width: '9rem', data: 'actions' },
];

const monthlySalaryColumns: DataTableColumn[] = [
    { key: 'row_number', label: '#', width: '3rem', data: 'row_number' },
    { key: 'salary_grade', label: 'Salary Grade', width: '7rem', data: 'salary_grade' },
    { key: 'salary_step', label: 'Step Increment', width: '7rem', data: 'salary_step' },
    { key: 'salary_amount', label: 'Monthly Salary', width: '9rem', data: 'salary_amount' },
    { key: 'actions', label: 'Actions', slot: 'actions', width: '9rem', data: 'actions' },
];

const getAjaxParams = computed(() => () => ({}));

// Functions for Add Job Title modal
const openAddJobTitleModal = () => {
    newJobTitle.value = '';
    jobTitleError.value = '';
    addJobTitleModalOpen.value = true;
};

const closeAddJobTitleModal = () => {
    addJobTitleModalOpen.value = false;
    newJobTitle.value = '';
    jobTitleError.value = '';
};

const submitJobTitle = async () => {
    jobTitleError.value = '';
    
    if (!newJobTitle.value || !newJobTitle.value.trim()) {
        jobTitleError.value = 'Job title is required.';
        return;
    }
    
    if (newJobTitle.value.trim().length > 50) {
        jobTitleError.value = 'Job title must not exceed 50 characters.';
        return;
    }
    
    isSubmittingJobTitle.value = true;
    
    try {
        const response = await fetch('/api/utilities/job-title-monthly-salary/job-titles', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ job_title: newJobTitle.value.trim() }),
        });
        
        const data = await response.json();
        
        if (response.ok && (response.status === 200 || response.status === 201) && data.success) {
            closeAddJobTitleModal();
            // Reload page to refresh DataTable
            window.location.reload();
        } else {
            // Handle validation errors (422 status)
            if (data.errors && data.errors.job_title) {
                jobTitleError.value = Array.isArray(data.errors.job_title) 
                    ? data.errors.job_title[0] 
                    : data.errors.job_title;
            } else if (data.message) {
                jobTitleError.value = data.message;
            } else {
                jobTitleError.value = 'Failed to create job title. Please try again.';
            }
        }
    } catch (error) {
        console.error('Error creating job title:', error);
        jobTitleError.value = 'An error occurred while creating the job title.';
    } finally {
        isSubmittingJobTitle.value = false;
    }
};

// Expose function to window for button click (for compatibility with onclick handlers)
if (typeof window !== 'undefined') {
    (window as any).addJobTitle = openAddJobTitleModal;
}

// Global functions for action buttons
if (typeof window !== 'undefined') {
    (window as any).updateJobTitle = (id: number, currentTitle: string) => {
        const newTitle = prompt('Enter new job title:', currentTitle);
        if (newTitle && newTitle.trim() !== '') {
            fetch(`/api/utilities/job-title-monthly-salary/job-titles/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ job_title: newTitle.trim() }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        router.reload({ only: [] });
                        // Reload the DataTable
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to update job title.');
                    }
                })
                .catch((error) => {
                    console.error('Error updating job title:', error);
                    alert('An error occurred while updating the job title.');
                });
        }
    };

    (window as any).deleteJobTitle = async (id: number, title: string) => {
        if (confirm(`Are you sure you want to delete "${title}"?`)) {
            try {
                const response = await fetch(`/api/utilities/job-title-monthly-salary/job-titles/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    credentials: 'same-origin',
                });

                const data = await response.json();
                if (data.success) {
                    router.reload({ only: [] });
                    // Reload the DataTable
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete job title.');
                }
            } catch (error) {
                console.error('Error deleting job title:', error);
                alert('An error occurred while deleting the job title.');
            }
        }
    };

    (window as any).updateMonthlySalary = (id: number, currentGrade: number, currentStep: number, currentAmount: string) => {
        const grade = prompt('Enter salary grade (1-33):', currentGrade.toString());
        const step = prompt('Enter step increment (1-8):', currentStep.toString());
        const amount = prompt('Enter monthly salary:', currentAmount.replace(/[^0-9.]/g, ''));

        if (grade && step && amount) {
            fetch(`/api/utilities/job-title-monthly-salary/monthly-salaries/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    salary_grade: parseInt(grade),
                    salary_step: parseInt(step),
                    salary_amount: parseFloat(amount),
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        router.reload({ only: [] });
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to update monthly salary.');
                    }
                })
                .catch((error) => {
                    console.error('Error updating monthly salary:', error);
                    alert('An error occurred while updating the monthly salary.');
                });
        }
    };

    (window as any).deleteMonthlySalary = async (id: number) => {
        if (confirm('Are you sure you want to delete this salary entry?')) {
            try {
                const response = await fetch(`/api/utilities/job-title-monthly-salary/monthly-salaries/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    credentials: 'same-origin',
                });

                const data = await response.json();
                if (data.success) {
                    router.reload({ only: [] });
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete monthly salary.');
                }
            } catch (error) {
                console.error('Error deleting monthly salary:', error);
                alert('An error occurred while deleting the monthly salary.');
            }
        }
    };


    (window as any).addMonthlySalary = () => {
        const grade = prompt('Enter salary grade (1-33):');
        const step = prompt('Enter step increment (1-8):');
        const amount = prompt('Enter monthly salary:');

        if (grade && step && amount) {
            fetch('/api/utilities/job-title-monthly-salary/monthly-salaries', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    salary_grade: parseInt(grade),
                    salary_step: parseInt(step),
                    salary_amount: parseFloat(amount),
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        router.reload({ only: [] });
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to create monthly salary.');
                    }
                })
                .catch((error) => {
                    console.error('Error creating monthly salary:', error);
                    alert('An error occurred while creating the monthly salary.');
                });
        }
    };
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Job Titles Table -->
                <section class="border border-border rounded-lg bg-background p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-semibold">List of Job Title</h2>
                        <Button @click="openAddJobTitleModal" variant="default" size="sm" class="bg-blue-600 hover:bg-blue-700 text-white">
                            <Plus class="mr-2 h-4 w-4" />
                            ADD NEW
                        </Button>
                    </div>

                    <div class="rounded-md border w-full">
                        <DataTable
                            ref="jobTitleTableRef"
                            :columns="jobTitleColumns"
                            ajax-url="/api/utilities/job-title-monthly-salary/job-titles/datatables"
                            :get-ajax-params="getAjaxParams"
                            row-key="id"
                            :loading="isLoadingJobTitles"
                            :empty-message="emptyMessageJobTitles"
                            :show-export-buttons="false"
                            :per-page-options="[10, 25, 50, 100, -1]"
                            :cell-renderers="jobTitleCellRenderers"
                        />
                    </div>
                </section>

                <!-- Monthly Salaries Table -->
                <section class="border border-border rounded-lg bg-background p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-semibold">List of Monthly Salary</h2>
                        <Button @click="(window as any).addMonthlySalary()" variant="default" size="sm" class="bg-blue-600 hover:bg-blue-700 text-white">
                            <Plus class="mr-2 h-4 w-4" />
                            ADD NEW
                        </Button>
                    </div>

                    <div class="rounded-md border w-full">
                        <DataTable
                            :columns="monthlySalaryColumns"
                            ajax-url="/api/utilities/job-title-monthly-salary/monthly-salaries/datatables"
                            :get-ajax-params="getAjaxParams"
                            row-key="id"
                            :loading="isLoadingSalaries"
                            :empty-message="emptyMessageSalaries"
                            :show-export-buttons="false"
                            :per-page-options="[10, 25, 50, 100, -1]"
                            :cell-renderers="monthlySalaryCellRenderers"
                        />
                    </div>
                </section>
            </div>
        </div>

        <!-- Add Job Title Modal -->
        <Dialog :open="addJobTitleModalOpen" @update:open="(v) => { addJobTitleModalOpen = v; if (!v) closeAddJobTitleModal(); }">
            <DialogContent class="sm:max-w-md p-0" :show-close-button="false">
                <div class="bg-blue-600 text-white px-6 py-3 rounded-t-lg flex items-center justify-between">
                    <DialogTitle class="text-lg font-semibold text-white m-0">Add Job Title</DialogTitle>
                    <button
                        @click="closeAddJobTitleModal"
                        class="text-white hover:opacity-80 transition-opacity rounded-full p-1"
                        type="button"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                
                <form @submit.prevent="submitJobTitle" class="px-6 py-4 space-y-4">
                    <div class="space-y-2">
                        <Label for="job-title-input" class="text-sm font-medium">Job Title</Label>
                        <Input
                            id="job-title-input"
                            v-model="newJobTitle"
                            type="text"
                            placeholder="Enter job title"
                            :disabled="isSubmittingJobTitle"
                            class="w-full"
                            maxlength="50"
                        />
                        <p v-if="jobTitleError" class="text-sm text-destructive mt-1">{{ jobTitleError }}</p>
                    </div>
                    
                    <DialogFooter class="flex gap-2 sm:gap-0 pt-4 pb-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeAddJobTitleModal"
                            :disabled="isSubmittingJobTitle"
                            class="bg-gray-100 hover:bg-gray-200"
                        >
                            Close
                        </Button>
                        <Button
                            type="submit"
                            variant="default"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                            :disabled="isSubmittingJobTitle"
                        >
                            <span v-if="isSubmittingJobTitle">Adding...</span>
                            <span v-else>Add</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
