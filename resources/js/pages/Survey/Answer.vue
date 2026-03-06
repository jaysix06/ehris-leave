<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

type Question = {
    survey_question_id: number;
    question: string;
    frm_option: string;
    type: string;
};

const props = defineProps<{
    survey: { id: number; title: string; description?: string; category?: string };
    questions: Question[];
    completed: boolean;
}>();

const pageTitle = computed(() => `Survey - ${props.survey?.title ?? 'Answer'}`);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/' },
    { title: 'GAD Survey', href: '/survey/gad' },
    { title: 'Answer' },
];

const answers = ref<Record<number, string | string[]>>({});
const submitting = ref(false);
const errors = ref<Record<string, string>>({});

function optionsList(frmOption: string): string[] {
    if (!frmOption || !String(frmOption).trim()) return [];
    return String(frmOption).split(',').map((s) => s.trim()).filter(Boolean);
}

function submitSurvey() {
    errors.value = {};
    const payload = {
        survey_id: props.survey.id,
        answers: props.questions.map((q) => {
            const val = answers.value[q.survey_question_id];
            const answer = Array.isArray(val) ? (val as string[]).join(',') : (val ?? '');
            return { question_id: q.survey_question_id, answer };
        }),
    };
    submitting.value = true;
    router.post('/survey/gad/answer', payload, {
        preserveScroll: false,
        onSuccess: () => {
            // Redirect to survey list (handled by backend)
        },
        onError: (errs: Record<string, string>) => {
            errors.value = errs ?? {};
        },
        onFinish: () => {
            submitting.value = false;
        },
    });
}

function goBack() {
    router.visit('/survey/gad');
}

function toggleCheckbox(questionId: number, opt: string, checked: boolean) {
    const prev = (answers.value[questionId] as string[] | undefined) ?? [];
    const next = checked ? [...prev, opt] : prev.filter((s) => s !== opt);
    answers.value = { ...answers.value, [questionId]: next };
}

function isCheckboxChecked(questionId: number, opt: string): boolean {
    const arr = answers.value[questionId];
    return Array.isArray(arr) && arr.includes(opt);
}

function onCheckboxChange(questionId: number, opt: string, e: Event) {
    toggleCheckbox(questionId, opt, (e.target as HTMLInputElement)?.checked ?? false);
}

function getAnswerString(questionId: number): string {
    const v = answers.value[questionId];
    return Array.isArray(v) ? v.join(',') : (v ?? '');
}
function setAnswerString(questionId: number, value: string) {
    answers.value = { ...answers.value, [questionId]: value };
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-3xl mx-auto space-y-6">
            <div v-if="completed" class="rounded-lg border border-border bg-muted/30 p-6 text-center">
                <p class="text-muted-foreground">You have already completed this survey.</p>
                <Button variant="outline" class="mt-4" @click="goBack">Back to GAD Survey</Button>
            </div>

            <template v-else>
                <div>
                    <h1 class="text-2xl font-semibold">{{ survey.title }}</h1>
                    <p v-if="survey.description" class="mt-1 text-sm text-muted-foreground">{{ survey.description }}</p>
                </div>

                <form @submit.prevent="submitSurvey" class="space-y-6">
                    <p v-if="errors.answers || errors.survey_id" class="text-sm text-destructive">
                        {{ errors.answers ?? errors.survey_id ?? 'Please fix the errors below.' }}
                    </p>
                    <div
                        v-for="(q, index) in questions"
                        :key="q.survey_question_id"
                        class="rounded-lg border border-border p-4 space-y-3"
                    >
                        <Label class="text-base font-medium">
                            {{ index + 1 }}. {{ q.question }}
                        </Label>

                        <!-- Text -->
                        <Input
                            v-if="q.type === 'text'"
                            :model-value="getAnswerString(q.survey_question_id)"
                            type="text"
                            class="w-full"
                            :required="true"
                            @update:model-value="setAnswerString(q.survey_question_id, String($event ?? ''))"
                        />

                        <!-- Radio -->
                        <div v-else-if="q.type === 'radio'" class="flex flex-col gap-2">
                            <label
                                v-for="opt in optionsList(q.frm_option)"
                                :key="opt"
                                class="inline-flex items-center gap-2 cursor-pointer"
                            >
                                <input
                                    v-model="answers[q.survey_question_id]"
                                    type="radio"
                                    :name="`q-${q.survey_question_id}`"
                                    :value="opt"
                                    class="rounded-full"
                                />
                                <span>{{ opt }}</span>
                            </label>
                        </div>

                        <!-- Checkbox: store as comma-separated -->
                        <div v-else-if="q.type === 'checkbox'" class="flex flex-col gap-2">
                            <label
                                v-for="opt in optionsList(q.frm_option)"
                                :key="opt"
                                class="inline-flex items-center gap-2 cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="opt"
                                    :checked="isCheckboxChecked(q.survey_question_id, opt)"
                                    @change="onCheckboxChange(q.survey_question_id, opt, $event)"
                                />
                                <span>{{ opt }}</span>
                            </label>
                        </div>

                        <!-- Fallback: text input -->
                        <Input
                            v-else
                            :model-value="getAnswerString(q.survey_question_id)"
                            type="text"
                            class="w-full"
                            @update:model-value="setAnswerString(q.survey_question_id, String($event ?? ''))"
                        />
                    </div>

                    <div class="flex gap-2">
                        <Button type="button" variant="outline" @click="goBack">Cancel</Button>
                        <Button type="submit" :disabled="submitting">Submit Survey</Button>
                    </div>
                </form>
            </template>
        </div>
    </AppLayout>
</template>
