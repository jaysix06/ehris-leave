<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { toast } from 'vue3-toastify';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

type SurveyRow = {
    id: number;
    title: string;
    description: string;
    category: string;
    completed: boolean;
};

const props = withDefaults(
    defineProps<{
        surveys: SurveyRow[];
        category?: string | null;
    }>(),
    { category: null }
);

const pageTitle = computed(() => (props.category ? `Survey - ${props.category}` : 'Survey'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/' },
    { title: 'Survey', href: '/survey/gad' },
    ...(props.category ? [{ title: props.category }] : []),
];

const searchQuery = ref('');

const filteredSurveys = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return props.surveys;
    return props.surveys.filter(
        (s) =>
            s.title.toLowerCase().includes(q) ||
            (s.description && s.description.toLowerCase().includes(q)) ||
            (s.category && s.category.toLowerCase().includes(q))
    );
});

function goToAnswer(surveyId: number) {
    router.visit(`/survey/gad/${surveyId}/answer`);
}

const page = usePage();
watch(
    () => (page.props.flash as { success?: string } | undefined)?.success,
    (message) => {
        if (message) toast.success(message);
    },
    { immediate: true }
);
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <Input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Search by title, description or category..."
                    class="max-w-sm"
                />
            </div>

            <div class="rounded-lg border border-border bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-border bg-muted/50">
                                <th class="text-left py-3 px-4 text-sm font-medium text-destructive">Title</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-primary">Description</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-destructive">Category</th>
                                <th class="text-left py-3 px-4 text-sm font-medium text-primary">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="survey in filteredSurveys"
                                :key="survey.id"
                                class="border-b border-border hover:bg-muted/30"
                            >
                                <td class="py-3 px-4 text-sm">{{ survey.title }}</td>
                                <td class="py-3 px-4 text-sm">{{ survey.description }}</td>
                                <td class="py-3 px-4 text-sm">{{ survey.category }}</td>
                                <td class="py-3 px-4">
                                    <span v-if="survey.completed" class="text-sm text-muted-foreground">Completed</span>
                                    <Button
                                        v-else
                                        variant="default"
                                        size="sm"
                                        @click="goToAnswer(survey.id)"
                                    >
                                        Answer
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="!filteredSurveys.length">
                                <td colspan="4" class="py-8 px-4 text-center text-sm text-muted-foreground">
                                    No surveys available.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
