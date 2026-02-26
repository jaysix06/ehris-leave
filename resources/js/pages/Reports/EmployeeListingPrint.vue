<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

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

type Props = {
    employees: Employee[];
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

const props = defineProps<Props>();

const fullName = (employee: Employee): string => {
    return trim(
        [
            employee.firstname,
            employee.middlename,
            employee.lastname,
            employee.extension,
        ]
            .filter(Boolean)
            .join(' '),
    );
};

const trim = (str: string): string => {
    return str.trim();
};

const activeFilters = computed(() => {
    const filters: string[] = [];
    if (props.filters.school) {
        filters.push(`School: ${props.filters.school}`);
    }
    if (props.filters.job_title) {
        filters.push(`Job Title: ${props.filters.job_title}`);
    }
    if (props.filters.subject) {
        filters.push(`Subject: ${props.filters.subject}`);
    }
    if (props.filters.grade_level) {
        filters.push(`Grade Level: ${props.filters.grade_level}`);
    }
    if (props.filters.employment_status) {
        filters.push(`Employment Status: ${props.filters.employment_status}`);
    }
    if (props.filters.salary_grade) {
        filters.push(`Salary Grade: ${props.filters.salary_grade}`);
    }
    if (props.filters.search) {
        filters.push(`Search: ${props.filters.search}`);
    }
    return filters;
});
</script>

<template>
    <Head title="Employee Listing - Print" />

    <div class="print-container">
        <style>
            @media print {
                @page {
                    margin: 1cm;
                    size: A4 landscape;
                }
                body {
                    margin: 0;
                    padding: 0;
                }
                .no-print {
                    display: none !important;
                }
                .print-container {
                    width: 100%;
                }
                table {
                    page-break-inside: auto;
                }
                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
                thead {
                    display: table-header-group;
                }
                tfoot {
                    display: table-footer-group;
                }
            }
            @media screen {
                .print-container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 2rem;
                }
            }
            .print-container {
                font-family: Arial, sans-serif;
                font-size: 12px;
            }
            .print-header {
                margin-bottom: 1.5rem;
                border-bottom: 2px solid #000;
                padding-bottom: 1rem;
            }
            .print-header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: bold;
            }
            .print-header .filters {
                margin-top: 0.5rem;
                font-size: 11px;
                color: #666;
            }
            .print-header .meta {
                margin-top: 0.5rem;
                font-size: 11px;
                color: #666;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1rem;
            }
            th,
            td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f5f5f5;
                font-weight: bold;
                position: sticky;
                top: 0;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
        </style>

        <div class="print-header">
            <h1>Employee Listing Report</h1>
            <div class="meta">
                Generated: {{ new Date().toLocaleString() }} | Total Records:
                {{ employees.length }}
            </div>
            <div v-if="activeFilters.length > 0" class="filters">
                <strong>Filters Applied:</strong>
                {{ activeFilters.join(' | ') }}
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>HRID</th>
                    <th>Employee ID</th>
                    <th>Name</th>
                    <th>Job Title</th>
                    <th>Subject</th>
                    <th>Grade Level</th>
                    <th>School/Office</th>
                    <th>Station Code</th>
                    <th>Salary Grade</th>
                    <th>Salary Step</th>
                    <th>Employment Status</th>
                    <th>Leave Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="employee in employees" :key="employee.hrid">
                    <td>{{ employee.hrid ?? '' }}</td>
                    <td>{{ employee.employee_id ?? '' }}</td>
                    <td>{{ fullName(employee) }}</td>
                    <td>{{ employee.job_title ?? '' }}</td>
                    <td>{{ employee.subject_taught ?? '' }}</td>
                    <td>{{ employee.grade_level ?? '' }}</td>
                    <td>{{ employee.office ?? '' }}</td>
                    <td>{{ employee.station_code ?? '' }}</td>
                    <td class="text-center">{{ employee.salary_grade ?? '' }}</td>
                    <td class="text-center">{{ employee.salary_step ?? '' }}</td>
                    <td>{{ employee.employ_status ?? '' }}</td>
                    <td class="text-right">{{ employee.leave_balance ?? 0 }}</td>
                </tr>
            </tbody>
        </table>

        <div v-if="employees.length === 0" class="text-center" style="padding: 2rem">
            No employees found matching the selected filters.
        </div>
    </div>
</template>
