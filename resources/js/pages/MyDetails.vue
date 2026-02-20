<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Calendar,
    GraduationCap,
    Mail,
    MapPin,
    Pencil,
    Phone,
    User,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'Employee';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Employee',
    },
    {
        title: 'Employee Detail',
    },
];

const tabs = [
    'Personal info',
    'Employee details',
    'Payroll details',
    'Documents',
    'Payroll history',
    'Medical history',
    'Leave history',
    'Attendance',
];

const infoRows = [
    { label: 'Place of birth', value: 'Calamba, Misamis Occidental' },
    { label: 'Birth date', value: '29 Aug 2003' },
    { label: 'Blood type', value: 'AB' },
    { label: 'Marital Status', value: 'Single' },
    { label: 'Religion', value: 'Christian' },
];

const education = [
    {
        degree: 'Bachelor\'s Degree - Misamis University',
        field: 'Information Technology',
        gpa: 'GPA (1.43)',
        period: '2022 - 2026',
    },
];

const family = [
    { type: 'Father', person: 'Reagan Balansag' },
    { type: 'Mother', person: 'Aurea Balansag' },
    { type: 'Siblings', person: 'Pawil John Balansag' },
];
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page">
            <section class="ehris-tabs" aria-label="Employee detail sections">
                <button
                    v-for="(tab, index) in tabs"
                    :key="tab"
                    type="button"
                    class="ehris-tab"
                    :class="{ 'is-active': index === 0 }"
                >
                    {{ tab }}
                </button>
            </section>

            <h2 class="ehris-page-title">Personal info</h2>

            <section class="ehris-card">
                <div class="ehris-card-header">
                    <h3>Basic information</h3>
                    <button type="button" class="ehris-edit-btn" aria-label="Edit basic information">
                        <Pencil class="size-4" />
                    </button>
                </div>

                <div class="ehris-profile-grid">
                    <div class="ehris-profile-main">
                        <div class="ehris-avatar ehris-avatar-placeholder" aria-label="Reagan Jade Balansag">
                            <img src="/20856.png" alt="Reagan Jade Balansag">
                        </div>

                        <div>   
                            <p class="ehris-name">Reagan Jade Balansag</p>
                            <p class="ehris-muted">2202054</p>

                            <ul class="ehris-meta-list">
                                <li>
                                    <User class="size-4" />
                                    <span>Male</span>
                                </li>
                                <li>
                                    <Mail class="size-4" />
                                    <span>reaganjade.balansag@deped.gov.ph</span>
                                </li>
                                <li>
                                    <Phone class="size-4" />
                                    <span>09303291846</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <dl class="ehris-details-grid">
                        <div v-for="item in infoRows" :key="item.label" class="ehris-details-row">
                            <dt>{{ item.label }}</dt>
                            <dd>{{ item.value }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <div class="ehris-two-col">
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Address</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit address">
                            <Pencil class="size-4" />
                        </button>
                    </div>

                    <dl class="ehris-stacked-list">
                        <div>
                            <dt>Citizen ID address</dt>
                            <dd>
                                <MapPin class="size-4" />
                                <span>Danao, Plaridel, Misamis Occidental</span>
                            </dd>
                        </div>
                        <div>
                            <dt>Residential address</dt>
                            <dd>
                                <MapPin class="size-4" />
                                <span>Purok 6, Lam-an, Ozamiz City, Misamis Occidental</span>
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Emergency contact</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit emergency contact">
                            <Pencil class="size-4" />
                        </button>
                    </div>

                    <dl class="ehris-compact-grid">
                        <div>
                            <dt>Name</dt>
                            <dd>Januard Amarille</dd>
                        </div>
                        <div>
                            <dt>Relationship</dt>
                            <dd>Classmate</dd>
                        </div>
                        <div>
                            <dt>Phone number</dt>
                            <dd>09761732164</dd>
                        </div>
                    </dl>
                </section>
            </div>

            <div class="ehris-two-col ehris-bottom-grid">
                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Education</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit education">
                            <Pencil class="size-4" />
                        </button>
                    </div>

                    <ul class="ehris-timeline">
                        <li v-for="item in education" :key="item.degree">
                            <span class="ehris-timeline-dot" aria-hidden="true"></span>
                            <div>
                                <p class="ehris-degree">
                                    <GraduationCap class="size-4" />
                                    <span>{{ item.degree }}</span>
                                </p>
                                <p>{{ item.field }}</p>
                                <p>{{ item.gpa }}</p>
                                <p class="ehris-muted">
                                    <Calendar class="size-4" />
                                    <span>{{ item.period }}</span>
                                </p>
                            </div>
                        </li>
                    </ul>
                </section>

                <section class="ehris-card">
                    <div class="ehris-card-header">
                        <h3>Family</h3>
                        <button type="button" class="ehris-edit-btn" aria-label="Edit family details">
                            <Pencil class="size-4" />
                        </button>
                    </div>

                    <div class="ehris-table-wrap">
                        <table class="ehris-table">
                            <thead>
                                <tr>
                                    <th>Family type</th>
                                    <th>Person name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in family" :key="item.type">
                                    <td>{{ item.type }}</td>
                                    <td>{{ item.person }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
