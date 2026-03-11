<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';

const pageTitle = "User's Manuals";

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Self-Service', href: selfServiceRoutes.wfhTimeInOut().url },
    { title: 'WFH Time In/Out', href: selfServiceRoutes.wfhTimeInOut().url },
    { title: pageTitle },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="pageTitle" />
        <div class="space-y-4">
            <div class="flex items-center gap-2">
                <BookOpen class="size-6 text-primary" />
                <h1 class="text-xl font-semibold text-foreground">
                    {{ pageTitle }}
                </h1>
            </div>
            <p class="text-sm text-muted-foreground">
                View or download user manuals and guides for the Self-Service WFH Time In/Out module.
            </p>
            <div class="rounded-lg border border-border bg-muted/30 p-4">
                <p class="text-sm text-muted-foreground">
                    Manuals and documentation can be added here. Place PDF or document links in this page as needed.
                </p>
            </div>
            <Link
                :href="selfServiceRoutes.wfhTimeInOut().url"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
            >
                ← Back to WFH Time In/Out
            </Link>
        </div>
    </AppLayout>
</template>
