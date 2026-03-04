<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, Pencil, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
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
    job_shorten?: string | null;
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
const newJobShorten = ref('');
const jobTitleError = ref('');
const isSubmittingJobTitle = ref(false);

// Modal state for Add Monthly Salary
const addMonthlySalaryModalOpen = ref(false);
const newSalaryGrade = ref('');
const newSalaryStep = ref('');
const newSalaryAmount = ref('');
const monthlySalaryError = ref('');
const isSubmittingMonthlySalary = ref(false);

// DataTable ref for reloading
const jobTitleTableRef = ref<any>(null);
const jobTitleTableKey = ref(0);
const monthlySalaryTableRef = ref<any>(null);
const monthlySalaryTableKey = ref(0);

// Escape HTML for safe rendering
function escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Cell renderers for Job Titles
const jobTitleCellRenderers = {
    actions: (row: JobTitle) => {
        const jobShorten = row.job_shorten || '';
        return `
            <div class="flex gap-2 items-center">
                <button 
                    onclick="window.updateJobTitle(${row.id}, '${escapeHtml(row.job_title)}', '${escapeHtml(jobShorten)}')"
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
    newJobShorten.value = '';
    jobTitleError.value = '';
    addJobTitleModalOpen.value = true;
};

const closeAddJobTitleModal = () => {
    addJobTitleModalOpen.value = false;
    newJobTitle.value = '';
    newJobShorten.value = '';
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
    
    if (!newJobShorten.value || !newJobShorten.value.trim()) {
        jobTitleError.value = 'Job shorten is required.';
        return;
    }
    
    if (newJobShorten.value.trim().length > 50) {
        jobTitleError.value = 'Job shorten must not exceed 50 characters.';
        return;
    }
    
    isSubmittingJobTitle.value = true;
    
    try {
        const requestBody: { job_title: string; job_shorten: string } = {
            job_title: newJobTitle.value.trim(),
            job_shorten: newJobShorten.value.trim(),
        };
        
        const response = await fetch('/api/utilities/job-title-monthly-salary/job-titles', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(requestBody),
        });
        
        const data = await response.json();
        
        if (response.ok && (response.status === 200 || response.status === 201) && data.success) {
            closeAddJobTitleModal();
            // Reload DataTable by incrementing key
            jobTitleTableKey.value++;
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Job title created successfully.',
                confirmButtonColor: '#2563eb',
            });
        } else {
            // Handle validation errors (422 status)
            if (data.errors) {
                const errorMessages: string[] = [];
                if (data.errors.job_title) {
                    errorMessages.push(Array.isArray(data.errors.job_title) ? data.errors.job_title[0] : data.errors.job_title);
                }
                if (data.errors.job_shorten) {
                    errorMessages.push(Array.isArray(data.errors.job_shorten) ? data.errors.job_shorten[0] : data.errors.job_shorten);
                }
                jobTitleError.value = errorMessages.join(' ') || data.message || 'Failed to create job title.';
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

// Functions for Add Monthly Salary modal
const openAddMonthlySalaryModal = () => {
    newSalaryGrade.value = '';
    newSalaryStep.value = '';
    newSalaryAmount.value = '';
    monthlySalaryError.value = '';
    addMonthlySalaryModalOpen.value = true;
};

const closeAddMonthlySalaryModal = () => {
    addMonthlySalaryModalOpen.value = false;
    newSalaryGrade.value = '';
    newSalaryStep.value = '';
    newSalaryAmount.value = '';
    monthlySalaryError.value = '';
};

const submitMonthlySalary = async () => {
    monthlySalaryError.value = '';
    
    // Handle number inputs - they can be empty string, null, or undefined
    const gradeValue = newSalaryGrade.value?.toString().trim() || '';
    const stepValue = newSalaryStep.value?.toString().trim() || '';
    const amountValue = newSalaryAmount.value?.toString().trim() || '';
    
    if (!gradeValue) {
        monthlySalaryError.value = 'Salary grade is required.';
        return;
    }
    
    if (!stepValue) {
        monthlySalaryError.value = 'Step increment is required.';
        return;
    }
    
    if (!amountValue) {
        monthlySalaryError.value = 'Salary amount is required.';
        return;
    }
    
    const grade = parseInt(gradeValue);
    const step = parseInt(stepValue);
    const amount = parseFloat(amountValue.replace(/[^0-9.]/g, ''));
    
    if (isNaN(grade) || grade < 1 || grade > 33) {
        monthlySalaryError.value = 'Salary grade must be between 1 and 33.';
        return;
    }
    
    if (isNaN(step) || step < 1 || step > 8) {
        monthlySalaryError.value = 'Step increment must be between 1 and 8.';
        return;
    }
    
    if (isNaN(amount) || amount < 0) {
        monthlySalaryError.value = 'Salary amount must be a valid positive number.';
        return;
    }
    
    isSubmittingMonthlySalary.value = true;
    
    try {
        const response = await fetch('/api/utilities/job-title-monthly-salary/monthly-salaries', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                salary_grade: grade,
                salary_step: step,
                salary_amount: amount,
            }),
        });
        
        const data = await response.json();
        
        if (response.ok && (response.status === 200 || response.status === 201) && data.success) {
            closeAddMonthlySalaryModal();
            // Reload DataTable by incrementing key
            monthlySalaryTableKey.value++;
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Monthly salary created successfully.',
                confirmButtonColor: '#2563eb',
            });
        } else {
            // Handle validation errors (422 status)
            if (data.errors) {
                const errorMessages: string[] = [];
                if (data.errors.salary_grade) {
                    errorMessages.push(Array.isArray(data.errors.salary_grade) ? data.errors.salary_grade[0] : data.errors.salary_grade);
                }
                if (data.errors.salary_step) {
                    errorMessages.push(Array.isArray(data.errors.salary_step) ? data.errors.salary_step[0] : data.errors.salary_step);
                }
                if (data.errors.salary_amount) {
                    errorMessages.push(Array.isArray(data.errors.salary_amount) ? data.errors.salary_amount[0] : data.errors.salary_amount);
                }
                monthlySalaryError.value = errorMessages.join(' ') || data.message || 'Failed to create monthly salary.';
            } else if (data.message) {
                monthlySalaryError.value = data.message;
            } else {
                monthlySalaryError.value = 'Failed to create monthly salary. Please try again.';
            }
        }
    } catch (error) {
        console.error('Error creating monthly salary:', error);
        monthlySalaryError.value = 'An error occurred while creating the monthly salary.';
    } finally {
        isSubmittingMonthlySalary.value = false;
    }
};

// Expose function to window for button click (for compatibility with onclick handlers)
if (typeof window !== 'undefined') {
    (window as any).addJobTitle = openAddJobTitleModal;
    (window as any).addMonthlySalary = openAddMonthlySalaryModal;
}

// Global functions for action buttons
if (typeof window !== 'undefined') {
    (window as any).updateJobTitle = async (id: number, currentTitle: string, currentShorten: string = '') => {
        // Escape HTML for safe insertion
        const escapeHtmlForInput = (str: string) => {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };
        
        const { value: formValues } = await Swal.fire({
            title: 'Update Job Title',
            html: `
                <div style="text-align: left; padding: 0.5rem 0;">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; color: #374151;">Job Title *</label>
                        <input 
                            id="swal-job-title" 
                            class="swal2-input" 
                            placeholder="Enter job title" 
                            value="${escapeHtmlForInput(currentTitle)}"
                            maxlength="50"
                            style="width: 100%; margin: 0; padding: 0.5rem 0.75rem; text-transform: none; font-size: 0.875rem; box-sizing: border-box;"
                        />
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; color: #374151;">Job Shorten *</label>
                        <input 
                            id="swal-job-shorten" 
                            class="swal2-input" 
                            placeholder="Enter job shorten" 
                            value="${escapeHtmlForInput(currentShorten)}"
                            maxlength="50"
                            style="width: 100%; margin: 0; padding: 0.5rem 0.75rem; text-transform: none; font-size: 0.875rem; box-sizing: border-box;"
                        />
                    </div>
                </div>
            `,
            width: '500px',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Update',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
            preConfirm: () => {
                const jobTitleInput = document.getElementById('swal-job-title') as HTMLInputElement;
                const jobShortenInput = document.getElementById('swal-job-shorten') as HTMLInputElement;
                const newTitle = jobTitleInput?.value.trim() || '';
                const newShorten = jobShortenInput?.value.trim() || '';
                
                if (!newTitle) {
                    Swal.showValidationMessage('Job title is required');
                    return false;
                }
                
                if (newTitle.length > 50) {
                    Swal.showValidationMessage('Job title must not exceed 50 characters');
                    return false;
                }
                
                if (!newShorten) {
                    Swal.showValidationMessage('Job shorten is required');
                    return false;
                }
                
                if (newShorten.length > 50) {
                    Swal.showValidationMessage('Job shorten must not exceed 50 characters');
                    return false;
                }
                
                return {
                    job_title: newTitle,
                    job_shorten: newShorten,
                };
            },
        });

        if (formValues) {
            try {
                const response = await fetch(`/api/utilities/job-title-monthly-salary/job-titles/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'same-origin',
                    body: JSON.stringify(formValues),
                });

                const data = await response.json();

                    if (data.success) {
                    // Reload DataTable by incrementing key
                    jobTitleTableKey.value++;
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Job title updated successfully.',
                        confirmButtonColor: '#2563eb',
                    });
                    } else {
                    // Handle validation errors
                    let errorMessage = data.message || 'Failed to update job title.';
                    if (data.errors) {
                        const errorMessages: string[] = [];
                        if (data.errors.job_title) {
                            errorMessages.push(Array.isArray(data.errors.job_title) ? data.errors.job_title[0] : data.errors.job_title);
                    }
                        if (data.errors.job_shorten) {
                            errorMessages.push(Array.isArray(data.errors.job_shorten) ? data.errors.job_shorten[0] : data.errors.job_shorten);
                        }
                        if (errorMessages.length > 0) {
                            errorMessage = errorMessages.join(' ');
                        }
                    }
                    await Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: errorMessage,
                        confirmButtonColor: '#dc2626',
                    });
                }
            } catch (error) {
                    console.error('Error updating job title:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the job title.',
                    confirmButtonColor: '#dc2626',
                });
            }
        }
    };

    (window as any).deleteJobTitle = async (id: number, title: string) => {
        const result = await Swal.fire({
            title: 'Delete Job Title?',
            text: `Are you sure you want to delete "${title}"? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            customClass: { 
                popup: 'ehris-swal-delete-popup', 
                actions: 'ehris-swal-actions', 
                confirmButton: 'ehris-swal-confirm', 
                cancelButton: 'ehris-swal-cancel' 
            },
        });

        if (result.isConfirmed) {
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
                    // Reload DataTable by incrementing key
                    jobTitleTableKey.value++;
                    await Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Job title has been deleted successfully.',
                        confirmButtonColor: '#2563eb',
                    });
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Delete Failed',
                        text: data.message || 'Failed to delete job title.',
                        confirmButtonColor: '#dc2626',
                    });
                }
            } catch (error) {
                console.error('Error deleting job title:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while deleting the job title.',
                    confirmButtonColor: '#dc2626',
                });
            }
        }
    };

    (window as any).updateMonthlySalary = async (id: number, currentGrade: number, currentStep: number, currentAmount: string) => {
        // Extract numeric value from formatted amount (remove "P ", commas, etc.)
        const numericAmount = currentAmount.replace(/[^0-9.]/g, '');
        
        // Escape HTML for safe insertion
        const escapeHtmlForInput = (str: string) => {
            return str
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };
        
        const { value: formValues } = await Swal.fire({
            title: 'Update Monthly Salary',
            html: `
                <div style="text-align: left; padding: 0.5rem 0;">
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; color: #374151;">Salary Grade *</label>
                        <input 
                            id="swal-salary-grade" 
                            class="swal2-input" 
                            type="number"
                            placeholder="Enter salary grade (1-33)" 
                            value="${escapeHtmlForInput(currentGrade.toString())}"
                            min="1"
                            max="33"
                            style="width: 100%; margin: 0; padding: 0.5rem 0.75rem; text-transform: none; font-size: 0.875rem; box-sizing: border-box;"
                        />
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; color: #374151;">Step Increment *</label>
                        <input 
                            id="swal-salary-step" 
                            class="swal2-input" 
                            type="number"
                            placeholder="Enter step increment (1-8)" 
                            value="${escapeHtmlForInput(currentStep.toString())}"
                            min="1"
                            max="8"
                            style="width: 100%; margin: 0; padding: 0.5rem 0.75rem; text-transform: none; font-size: 0.875rem; box-sizing: border-box;"
                        />
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.5rem; color: #374151;">Salary Amount *</label>
                        <input 
                            id="swal-salary-amount" 
                            class="swal2-input" 
                            type="text"
                            placeholder="Enter monthly salary" 
                            value="${escapeHtmlForInput(numericAmount)}"
                            style="width: 100%; margin: 0; padding: 0.5rem 0.75rem; text-transform: none; font-size: 0.875rem; box-sizing: border-box;"
                        />
                    </div>
                </div>
            `,
            width: '500px',
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Update',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#6b7280',
            preConfirm: () => {
                const gradeInput = document.getElementById('swal-salary-grade') as HTMLInputElement;
                const stepInput = document.getElementById('swal-salary-step') as HTMLInputElement;
                const amountInput = document.getElementById('swal-salary-amount') as HTMLInputElement;
                
                const grade = gradeInput?.value.trim() || '';
                const step = stepInput?.value.trim() || '';
                const amount = amountInput?.value.trim() || '';
                
                if (!grade) {
                    Swal.showValidationMessage('Salary grade is required');
                    return false;
                }
                
                const gradeNum = parseInt(grade);
                if (isNaN(gradeNum) || gradeNum < 1 || gradeNum > 33) {
                    Swal.showValidationMessage('Salary grade must be between 1 and 33');
                    return false;
                }
                
                if (!step) {
                    Swal.showValidationMessage('Step increment is required');
                    return false;
                }
                
                const stepNum = parseInt(step);
                if (isNaN(stepNum) || stepNum < 1 || stepNum > 8) {
                    Swal.showValidationMessage('Step increment must be between 1 and 8');
                    return false;
                }
                
                if (!amount) {
                    Swal.showValidationMessage('Salary amount is required');
                    return false;
                }
                
                const amountNum = parseFloat(amount.replace(/[^0-9.]/g, ''));
                if (isNaN(amountNum) || amountNum < 0) {
                    Swal.showValidationMessage('Salary amount must be a valid positive number');
                    return false;
                }
                
                return {
                    salary_grade: gradeNum,
                    salary_step: stepNum,
                    salary_amount: amountNum,
                };
            },
        });

        if (formValues) {
            try {
                const response = await fetch(`/api/utilities/job-title-monthly-salary/monthly-salaries/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                credentials: 'same-origin',
                    body: JSON.stringify(formValues),
                });

                const data = await response.json();

                    if (data.success) {
                    // Reload DataTable by incrementing key
                    monthlySalaryTableKey.value++;
                    await Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Monthly salary updated successfully.',
                        confirmButtonColor: '#2563eb',
                    });
                    } else {
                    // Handle validation errors
                    let errorMessage = data.message || 'Failed to update monthly salary.';
                    if (data.errors) {
                        const errorMessages: string[] = [];
                        if (data.errors.salary_grade) {
                            errorMessages.push(Array.isArray(data.errors.salary_grade) ? data.errors.salary_grade[0] : data.errors.salary_grade);
                        }
                        if (data.errors.salary_step) {
                            errorMessages.push(Array.isArray(data.errors.salary_step) ? data.errors.salary_step[0] : data.errors.salary_step);
                        }
                        if (data.errors.salary_amount) {
                            errorMessages.push(Array.isArray(data.errors.salary_amount) ? data.errors.salary_amount[0] : data.errors.salary_amount);
                        }
                        if (errorMessages.length > 0) {
                            errorMessage = errorMessages.join(' ');
                        }
                    }
                    await Swal.fire({
                        icon: 'error',
                        title: 'Update Failed',
                        text: errorMessage,
                        confirmButtonColor: '#dc2626',
                    });
                }
            } catch (error) {
                    console.error('Error updating monthly salary:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while updating the monthly salary.',
                    confirmButtonColor: '#dc2626',
                });
            }
        }
    };

    (window as any).deleteMonthlySalary = async (id: number) => {
        const result = await Swal.fire({
            title: 'Delete Monthly Salary?',
            text: 'Are you sure you want to delete this salary entry? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            customClass: { 
                popup: 'ehris-swal-delete-popup', 
                actions: 'ehris-swal-actions', 
                confirmButton: 'ehris-swal-confirm', 
                cancelButton: 'ehris-swal-cancel' 
            },
        });

        if (result.isConfirmed) {
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
                    // Reload DataTable by incrementing key
                    monthlySalaryTableKey.value++;
                    await Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Monthly salary has been deleted successfully.',
                        confirmButtonColor: '#2563eb',
                    });
                } else {
                    await Swal.fire({
                        icon: 'error',
                        title: 'Delete Failed',
                        text: data.message || 'Failed to delete monthly salary.',
                        confirmButtonColor: '#dc2626',
                    });
                }
            } catch (error) {
                console.error('Error deleting monthly salary:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while deleting the monthly salary.',
                    confirmButtonColor: '#dc2626',
                });
            }
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
                <section class="border border-border rounded-lg bg-card p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-semibold">List of Job Title</h2>
                        <Button @click="openAddJobTitleModal" variant="default" size="sm" class="bg-blue-600 hover:bg-blue-700 text-white">
                            <Plus class="mr-2 h-4 w-4" />
                            ADD NEW
                        </Button>
                    </div>

                    <div class="rounded-md border w-full">
                        <DataTable
                            :key="jobTitleTableKey"
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
                <section class="border border-border rounded-lg bg-card p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-semibold">List of Monthly Salary</h2>
                        <Button @click="openAddMonthlySalaryModal" variant="default" size="sm" class="bg-blue-600 hover:bg-blue-700 text-white">
                            <Plus class="mr-2 h-4 w-4" />
                            ADD NEW
                        </Button>
                    </div>

                    <div class="rounded-md border w-full">
                        <DataTable
                            :key="monthlySalaryTableKey"
                            ref="monthlySalaryTableRef"
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
                        <Label for="job-title-input" class="text-sm font-medium">Job Title <span class="text-destructive">*</span></Label>
                        <Input
                            id="job-title-input"
                            v-model="newJobTitle"
                            type="text"
                            placeholder="Enter job title"
                            :disabled="isSubmittingJobTitle"
                            class="w-full"
                            maxlength="50"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="job-shorten-input" class="text-sm font-medium">Job Shorten <span class="text-destructive">*</span></Label>
                        <Input
                            id="job-shorten-input"
                            v-model="newJobShorten"
                            type="text"
                            placeholder="Enter job shorten"
                            :disabled="isSubmittingJobTitle"
                            class="w-full"
                            maxlength="50"
                        />
                    </div>
                    <p v-if="jobTitleError" class="text-sm text-destructive mt-1">{{ jobTitleError }}</p>
                    
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

        <!-- Add Monthly Salary Modal -->
        <Dialog :open="addMonthlySalaryModalOpen" @update:open="(v) => { addMonthlySalaryModalOpen = v; if (!v) closeAddMonthlySalaryModal(); }">
            <DialogContent class="sm:max-w-md p-0" :show-close-button="false">
                <div class="bg-blue-600 text-white px-6 py-3 rounded-t-lg flex items-center justify-between">
                    <DialogTitle class="text-lg font-semibold text-white m-0">Add Monthly Salary</DialogTitle>
                    <button
                        @click="closeAddMonthlySalaryModal"
                        class="text-white hover:opacity-80 transition-opacity rounded-full p-1"
                        type="button"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                
                <form @submit.prevent="submitMonthlySalary" class="px-6 py-4 space-y-4">
                    <div class="space-y-2">
                        <Label for="salary-grade-input" class="text-sm font-medium">Salary Grade <span class="text-destructive">*</span></Label>
                        <Input
                            id="salary-grade-input"
                            v-model="newSalaryGrade"
                            type="number"
                            placeholder="Enter salary grade (1-33)"
                            :disabled="isSubmittingMonthlySalary"
                            class="w-full"
                            min="1"
                            max="33"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="salary-step-input" class="text-sm font-medium">Step Increment <span class="text-destructive">*</span></Label>
                        <Input
                            id="salary-step-input"
                            v-model="newSalaryStep"
                            type="number"
                            placeholder="Enter step increment (1-8)"
                            :disabled="isSubmittingMonthlySalary"
                            class="w-full"
                            min="1"
                            max="8"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="salary-amount-input" class="text-sm font-medium">Salary Amount <span class="text-destructive">*</span></Label>
                        <Input
                            id="salary-amount-input"
                            v-model="newSalaryAmount"
                            type="text"
                            placeholder="Enter monthly salary"
                            :disabled="isSubmittingMonthlySalary"
                            class="w-full"
                        />
                    </div>
                    <p v-if="monthlySalaryError" class="text-sm text-destructive mt-1">{{ monthlySalaryError }}</p>
                    
                    <DialogFooter class="flex gap-2 sm:gap-0 pt-4 pb-0">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeAddMonthlySalaryModal"
                            :disabled="isSubmittingMonthlySalary"
                            class="bg-gray-100 hover:bg-gray-200"
                        >
                            Close
                        </Button>
                        <Button
                            type="submit"
                            variant="default"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                            :disabled="isSubmittingMonthlySalary"
                            @click="(e) => { e.preventDefault(); submitMonthlySalary(); }"
                        >
                            <span v-if="isSubmittingMonthlySalary">Adding...</span>
                            <span v-else>Add</span>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<!-- Global styles for SweetAlert delete confirmation (popup is rendered in body) -->
<style>
.ehris-swal-delete-popup .ehris-swal-actions {
    display: flex !important;
    flex-direction: row;
    gap: 0.75rem;
    justify-content: center;
    margin-top: 1.25rem;
    padding: 0;
}
.ehris-swal-delete-popup .ehris-swal-cancel,
.ehris-swal-delete-popup .ehris-swal-confirm {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1.25rem !important;
    min-height: 2.25rem !important;
    font-size: 0.875rem !important;
    font-weight: 500;
    border-radius: 0.375rem;
    cursor: pointer;
    border: none;
}
.ehris-swal-delete-popup .ehris-swal-cancel {
    background-color: #e5e7eb !important;
    color: #374151 !important;
}
.ehris-swal-delete-popup .ehris-swal-cancel:hover {
    background-color: #d1d5db !important;
}
.ehris-swal-delete-popup .ehris-swal-confirm {
    background-color: #dc2626 !important;
    color: #fff !important;
}
.ehris-swal-delete-popup .ehris-swal-confirm:hover {
    background-color: #b91c1c !important;
}
</style>
