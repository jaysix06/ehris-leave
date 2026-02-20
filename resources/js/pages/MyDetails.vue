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
    { label: 'Place of birth', value: 'Bandung' },
    { label: 'Birth date', value: '30 Oct 1994' },
    { label: 'Blood type', value: 'AB' },
    { label: 'Marital Status', value: 'Married' },
    { label: 'Religion', value: 'Christian' },
];

const education = [
    {
        degree: 'Master Degree - Bina Nusantara',
        field: 'Business',
        gpa: 'GPA (3.5)',
        period: '2016 - 2018',
    },
    {
        degree: 'Bachelor Degree - Bina Nusantara',
        field: 'Business',
        gpa: 'GPA (3.9)',
        period: '2012 - 2016',
    },
];

const family = [
    { type: 'Father', person: 'Benjamin Williams' },
    { type: 'Mother', person: 'Evelyn Potts' },
    { type: 'Siblings', person: 'James Williams, Emily Williams' },
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
                        <div class="ehris-avatar ehris-avatar-placeholder" aria-label="John Williams">
                            <span>JW</span>
                        </div>

                        <div>
                            <p class="ehris-name">John Williams</p>
                            <p class="ehris-muted">1210372726433743682</p>

                            <ul class="ehris-meta-list">
                                <li>
                                    <User class="size-4" />
                                    <span>Male</span>
                                </li>
                                <li>
                                    <Mail class="size-4" />
                                    <span>johnwilliams@bicaradata.com</span>
                                </li>
                                <li>
                                    <Phone class="size-4" />
                                    <span>081323323311</span>
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
                                <span>Jl. Wayang No.2, Burangrang, Kec. Lengkong, Kota Bandung, Jawa Barat 40262</span>
                            </dd>
                        </div>
                        <div>
                            <dt>Residential address</dt>
                            <dd>
                                <MapPin class="size-4" />
                                <span>Jl. Wayang No.2, Burangrang, Kec. Lengkong, Kota Bandung, Jawa Barat 40262</span>
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
                            <dd>Olivia Bennett</dd>
                        </div>
                        <div>
                            <dt>Relationship</dt>
                            <dd>Wife</dd>
                        </div>
                        <div>
                            <dt>Phone number</dt>
                            <dd>081324815250</dd>
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
