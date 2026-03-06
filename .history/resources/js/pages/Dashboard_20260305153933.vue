<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Activity, CalendarRange, ClipboardCheck, Users } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'Dashboard';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
        href: dashboard().url,
    },
];

const stats = [
    { title: 'Active Employees', value: '214', icon: Users },
    { title: 'Pending Requests', value: '18', icon: ClipboardCheck },
    { title: 'Upcoming Leaves', value: '7', icon: CalendarRange },
    { title: 'Today Activity Logs', value: '63', icon: Activity },
];
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page">
            <section class="ehris-card">
                <div class="dashboard-head">
                    <h2>Overview</h2>
                    <p>Summary of key HR metrics for today.</p>
                </div>

                <div class="dashboard-grid">
                    <article v-for="item in stats" :key="item.title" class="dashboard-stat">
                        <component :is="item.icon" class="size-5" />
                        <p class="stat-value">{{ item.value }}</p>
                        <p class="stat-title">{{ item.title }}</p>
                    </article>
                </div>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.dashboard-head h2 {
    margin: 0;
    font-size: 1.55rem;
    font-weight: 800;
    color: hsl(var(--foreground));
}

.dashboard-head p {
    margin: 0.35rem 0 0;
    color: hsl(var(--muted-foreground));
    font-size: 0.92rem;
}

.dashboard-grid {
    margin-top: 1rem;
    display: grid;
    gap: 0.9rem;
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.dashboard-stat {
    border: 1px solid hsl(var(--border));
    border-radius: 0.9rem;
    padding: 1rem;
    background: hsl(var(--card));
    color: hsl(var(--primary));
}

.stat-value {
    margin: 0.6rem 0 0;
    font-size: 1.8rem;
    font-weight: 800;
    color: hsl(var(--foreground));
}

.stat-title {
    margin: 0.2rem 0 0;
    font-size: 0.85rem;
    color: hsl(var(--muted-foreground));
}

@media (max-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>
