<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'COT-RPMS Summary - Subject Area';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
    },
];
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="rounded-lg border border-sidebar-border/70 bg-background p-6">
                <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    This page is ready for implementation.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
