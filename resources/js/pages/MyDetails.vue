<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    GraduationCap,
    Mail,
    MapPin,
    Pencil,
    Phone,
    User,
} from 'lucide-vue-next';
import { computed } from 'vue';
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

type Profile = {
    hrId?: number | null;
    email?: string | null;
    lastname?: string | null;
    firstname?: string | null;
    middlename?: string | null;
    extname?: string | null;
    avatar?: string | null;
    job_title?: string | null;
    role?: string | null;
    fullname?: string | null;
};

const props = defineProps<{
    profile?: Profile | null;
}>();

const page = usePage();
const authUser = computed(() => page.props.auth.user);

const employeeName = computed(() => {
    if (props.profile?.fullname) {
        return props.profile.fullname;
    }

    const authName = authUser.value?.name;
    return typeof authName === 'string' && authName.length > 0
        ? authName
        : 'N/A';
});

const employeeId = computed(() => {
    if (props.profile?.hrId !== null && props.profile?.hrId !== undefined) {
        return String(props.profile.hrId);
    }

    const authId = authUser.value?.id;
    return authId !== null && authId !== undefined ? String(authId) : 'N/A';
});

const employeeEmail = computed(() => {
    if (props.profile?.email) {
        return props.profile.email;
    }

    const authEmail = authUser.value?.email;
    return typeof authEmail === 'string' && authEmail.length > 0
        ? authEmail
        : 'N/A';
});

const employeeJobTitle = computed(() => props.profile?.job_title || 'N/A');

const avatarSrc = computed(() => {
    const avatar = props.profile?.avatar || authUser.value?.avatar;

    if (typeof avatar !== 'string' || avatar.length === 0) {
        return null;
    }

    if (avatar.startsWith('http://') || avatar.startsWith('https://') || avatar.startsWith('/')) {
        return avatar;
    }

    return `/${avatar}`;
});

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
                        <div class="ehris-avatar ehris-avatar-placeholder" :aria-label="employeeName">
                            <img v-if="avatarSrc" :src="avatarSrc" class="ehris-avatar" :alt="employeeName">
                        </div>

                        <div>   
                            <p class="ehris-name">{{ employeeName }}</p>
                            <p class="ehris-muted">{{ employeeId }}</p>

                            <ul class="ehris-meta-list">
                                <li>
                                    <User class="size-4" />
                                    <span>{{ employeeJobTitle }}</span>
                                </li>
                                <li>
                                    <Mail class="size-4" />
                                    <span>{{ employeeEmail }}</span>
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
