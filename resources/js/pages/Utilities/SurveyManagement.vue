<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { DataTable, type DataTableColumn } from '@/components/DataTable';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';
import { Plus, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const pageTitle = 'Utilities - Survey Management';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home' },
    { title: 'Survey Management' },
];

const tableKey = ref(0);
const showSurveyDialog = ref(false);
const surveyForm = reactive({
    title: '',
    description: '',
    category: '',
});
const surveyErrors = ref<Record<string, string>>({});
const surveySubmitting = ref(false);

function openSurveyDialog() {
    surveyForm.title = '';
    surveyForm.description = '';
    surveyForm.category = '';
    surveyErrors.value = {};
    showSurveyDialog.value = true;
}

function submitSurvey() {
    surveyErrors.value = {};
    surveySubmitting.value = true;
    router.post('/utilities/survey-management', {
        title: surveyForm.title,
        description: surveyForm.description,
        category: surveyForm.category || undefined,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showSurveyDialog.value = false;
            tableKey.value += 1;
        },
        onError: (errors: Record<string, string>) => {
            surveyErrors.value = errors;
        },
        onFinish: () => {
            surveySubmitting.value = false;
        },
    });
}

const columns: DataTableColumn[] = [
    { key: 'title', label: 'Title', data: 'title', width: '25%' },
    { key: 'description', label: 'Description', data: 'description', width: '40%' },
    { key: 'category', label: 'Category', data: 'category', width: '20%' },
    { key: 'action', label: 'Action', data: 'id', width: '15%', orderable: false, slot: 'survey_actions' },
];

function escapeHtml(text: string | number): string {
    const div = document.createElement('div');
    div.textContent = String(text ?? '');
    return div.innerHTML;
}

const cellRenderers: Record<string, (row: unknown) => string> = {
    survey_actions: (row: unknown) => {
        const r = row as { id?: number };
        const id = r?.id ?? '';
        return `
            <span class="inline-flex items-center gap-2">
                <button type="button" class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-primary hover:bg-primary/10" data-action="update" data-id="${escapeHtml(id)}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Update
                </button>
                <button type="button" class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium text-destructive hover:bg-destructive/10" data-action="delete" data-id="${escapeHtml(id)}">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete
                </button>
            </span>`;
    },
};

function getAjaxParams() {
    return {};
}

const categoryOptions = ['PRAISE', 'GAD', 'PASS'];

// Edit Survey modal (survey + questions like reference image)
const showEditSurveyDialog = ref(false);
const editingSurveyId = ref<number | null>(null);
const editSurvey = reactive({ title: '', description: '', category: '' });
const editQuestions = ref<Array<{ survey_question_id?: number; question: string; frm_option: string; type: string }>>([]);
const editSurveySubmitting = ref(false);
const editSurveyErrors = ref<Record<string, string>>({});

function onSurveyTableClick(e: MouseEvent) {
    const target = e.target as HTMLElement;
    const updateBtn = target.closest('button[data-action="update"]');
    if (updateBtn) {
        e.preventDefault();
        e.stopPropagation();
        const id = updateBtn.getAttribute('data-id');
        if (!id) return;
        fetch(`/api/utilities/survey-management/${id}`)
            .then((res) => res.json())
            .then((data) => {
                editSurvey.title = data.survey?.title ?? '';
                editSurvey.description = data.survey?.description ?? '';
                editSurvey.category = data.survey?.category ?? '';
                editQuestions.value = (data.questions ?? []).map((q: { survey_question_id: number; question: string; frm_option: string; type: string }) => ({
                    survey_question_id: q.survey_question_id,
                    question: q.question ?? '',
                    frm_option: q.frm_option ?? '',
                    type: q.type ?? 'radio',
                }));
                editingSurveyId.value = data.survey?.id ?? Number(id);
                editSurveyErrors.value = {};
                showEditSurveyDialog.value = true;
            })
            .catch(() => {
                editSurveyErrors.value = { fetch: 'Failed to load survey.' };
            });
        return;
    }
    const deleteBtn = target.closest('button[data-action="delete"]');
    if (deleteBtn) {
        e.preventDefault();
        e.stopPropagation();
        const id = deleteBtn.getAttribute('data-id');
        if (!id) return;
        Swal.fire({
            title: 'Delete Survey?',
            text: 'This survey and its questions will be removed. This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            customClass: { popup: 'ehris-swal-delete-popup', actions: 'ehris-swal-actions', confirmButton: 'ehris-swal-confirm', cancelButton: 'ehris-swal-cancel' },
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(`/utilities/survey-management/${id}`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        tableKey.value += 1;
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Survey has been deleted successfully.',
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    },
                });
            }
        });
    }
}

// Add Question dialog (Question + Type; Options when Radio/Checkbox)
const showAddQuestionDialog = ref(false);
const addQuestionForm = reactive({ question: '', type: 'Text' });
const addQuestionOptions = ref<string[]>(['']);
const questionTypeOptions = ['Text', 'Radio', 'Checkbox'];

const isOptionsType = () => addQuestionForm.type === 'Radio' || addQuestionForm.type === 'Checkbox';

function addEditQuestion() {
    addQuestionForm.question = '';
    addQuestionForm.type = 'Text';
    addQuestionOptions.value = [''];
    showAddQuestionDialog.value = true;
}

watch(() => addQuestionForm.type, (newType) => {
    if (newType === 'Radio' || newType === 'Checkbox') {
        if (addQuestionOptions.value.length === 0) addQuestionOptions.value = [''];
    }
});

function ensureOptionsForType() {
    if (isOptionsType() && addQuestionOptions.value.length === 0) addQuestionOptions.value = [''];
}

function addMoreOption() {
    addQuestionOptions.value = [...addQuestionOptions.value, ''];
}

function removeOption(index: number) {
    if (addQuestionOptions.value.length <= 1) return;
    addQuestionOptions.value = addQuestionOptions.value.filter((_, i) => i !== index);
}

function buildFrmOption(): string {
    if (!isOptionsType()) return '';
    return addQuestionOptions.value
        .map((s) => String(s ?? '').trim())
        .filter(Boolean)
        .join(',');
}

function addCurrentQuestionAndClose() {
    const q = String(addQuestionForm.question ?? '').trim();
    if (!q) return;
    const type = addQuestionForm.type === 'Radio' ? 'radio' : addQuestionForm.type === 'Checkbox' ? 'checkbox' : 'text';
    const frm_option = buildFrmOption();
    editQuestions.value = [...editQuestions.value, { question: q, frm_option, type }];
    showAddQuestionDialog.value = false;
    addQuestionForm.question = '';
    addQuestionForm.type = 'Text';
    addQuestionOptions.value = [''];
}

function addCurrentQuestionAndContinue() {
    const q = String(addQuestionForm.question ?? '').trim();
    if (!q) return;
    const type = addQuestionForm.type === 'Radio' ? 'radio' : addQuestionForm.type === 'Checkbox' ? 'checkbox' : 'text';
    const frm_option = buildFrmOption();
    editQuestions.value = [...editQuestions.value, { question: q, frm_option, type }];
    addQuestionForm.question = '';
    addQuestionForm.type = 'Text';
    addQuestionOptions.value = [''];
    ensureOptionsForType();
}

function removeEditQuestion(index: number) {
    editQuestions.value = editQuestions.value.filter((_, i) => i !== index);
}

function submitEditSurvey() {
    const id = editingSurveyId.value;
    if (id == null) return;
    editSurveyErrors.value = {};
    editSurveySubmitting.value = true;
    const questions = editQuestions.value
        .filter((q) => String(q.question ?? '').trim() !== '')
        .map((q) => ({
            survey_question_id: q.survey_question_id,
            question: q.question.trim(),
            frm_option: q.frm_option || undefined,
            type: q.type || 'radio',
        }));
    router.put(`/utilities/survey-management/${id}`, {
        title: editSurvey.title,
        description: editSurvey.description,
        category: editSurvey.category || undefined,
        questions,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showEditSurveyDialog.value = false;
            editingSurveyId.value = null;
            tableKey.value += 1;
        },
        onError: (errors: Record<string, string>) => {
            editSurveyErrors.value = errors;
        },
        onFinish: () => {
            editSurveySubmitting.value = false;
        },
    });
}

function optionsList(frmOption: string): string[] {
    if (!frmOption || !String(frmOption).trim()) return [];
    return String(frmOption).split(',').map((s) => s.trim()).filter(Boolean);
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        List of surveys in each category.
                    </p>
                </div>
                <Button variant="default" size="sm" class="gap-1.5 shrink-0 w-fit" @click="openSurveyDialog">
                    <Plus class="h-4 w-4" />
                    Add New Survey
                </Button>
            </div>

            <div class="rounded-lg border border-border bg-card overflow-hidden">
                <div class="px-4 py-2 border-b border-border">
                    <label class="text-sm font-medium text-foreground">Survey</label>
                </div>
                <div class="p-2 overflow-x-auto" @click="onSurveyTableClick">
                    <DataTable
                        :key="tableKey"
                        :columns="columns"
                        ajax-url="/api/utilities/survey-management/datatables"
                        :get-ajax-params="getAjaxParams"
                        row-key="id"
                        :per-page-options="[10, 25, 50]"
                        empty-message="No surveys found."
                        :cell-renderers="cellRenderers"
                    />
                </div>
            </div>

            <!-- New Survey Dialog (matches reference: title "New Survey", Survey Title, Description textarea, Category dropdown) -->
            <Dialog v-model:open="showSurveyDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>New Survey</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitSurvey" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="survey-title" class="text-foreground">Survey Title:</Label>
                            <Input
                                id="survey-title"
                                v-model="surveyForm.title"
                                type="text"
                                placeholder=""
                                class="w-full border-border"
                            />
                            <p v-if="surveyErrors.title" class="text-sm text-destructive">{{ surveyErrors.title }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="survey-description" class="text-foreground">Description:</Label>
                            <textarea
                                id="survey-description"
                                v-model="surveyForm.description"
                                rows="4"
                                placeholder=""
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-y"
                            />
                            <p v-if="surveyErrors.description" class="text-sm text-destructive">{{ surveyErrors.description }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="survey-category" class="text-foreground">Category:</Label>
                            <select
                                id="survey-category"
                                v-model="surveyForm.category"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="">- Select Category -</option>
                                <option v-for="opt in categoryOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <p v-if="surveyErrors.category" class="text-sm text-destructive">{{ surveyErrors.category }}</p>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="showSurveyDialog = false">Cancel</Button>
                            <Button type="submit" :disabled="surveySubmitting">Save</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit Survey Dialog (survey title as header, questions with radio options, Add Question, Save) -->
            <Dialog v-model:open="showEditSurveyDialog">
                <DialogContent class="sm:max-w-2xl max-h-[90vh] flex flex-col">
                    <DialogHeader class="shrink-0">
                        <DialogTitle class="text-lg font-bold">
                            {{ editSurvey.title }}
                            <span v-if="editSurvey.category" class="text-sm font-normal text-muted-foreground ml-1">{{ editSurvey.category }}</span>
                        </DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="submitEditSurvey" class="flex flex-col flex-1 min-h-0 flex gap-4">
                        <div class="flex-1 overflow-y-auto space-y-4 pr-1">
                            <p v-if="editSurveyErrors.fetch" class="text-sm text-destructive">{{ editSurveyErrors.fetch }}</p>
                            <div
                                v-for="(q, index) in editQuestions"
                                :key="index"
                                class="flex gap-2 items-start rounded border border-border p-3 bg-muted/30"
                            >
                                <span class="shrink-0 pt-2 text-sm font-medium text-muted-foreground">{{ index + 1 }}.</span>
                                <div class="flex-1 min-w-0 space-y-2">
                                    <Input
                                        v-model="q.question"
                                        placeholder="Question text"
                                        class="w-full"
                                    />
                                    <div v-if="optionsList(q.frm_option).length" class="flex flex-wrap gap-3 pt-1">
                                        <label
                                            v-for="opt in optionsList(q.frm_option)"
                                            :key="opt"
                                            class="inline-flex items-center gap-2 text-sm cursor-pointer"
                                        >
                                            <input type="radio" :name="`q-${index}`" class="rounded-full" disabled />
                                            <span>{{ opt }}</span>
                                        </label>
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="shrink-0 text-destructive hover:bg-destructive/10"
                                    title="Delete question"
                                    @click="removeEditQuestion(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                        <div class="shrink-0 flex flex-wrap items-center gap-2 pt-2 border-t border-border">
                            <Button type="button" variant="outline" size="sm" class="gap-1" @click="addEditQuestion">
                                <Plus class="h-4 w-4" />
                                Add Question
                            </Button>
                            <div class="flex-1" />
                            <Button type="submit" variant="default" :disabled="editSurveySubmitting">Save</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Add Question dialog: Question, Type (Text/Radio/Checkbox), Options when Radio/Checkbox -->
            <Dialog v-model:open="showAddQuestionDialog">
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Add Question</DialogTitle>
                    </DialogHeader>
                    <form @submit.prevent="addCurrentQuestionAndClose" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="add-q-text" class="text-foreground">Question:</Label>
                            <Input
                                id="add-q-text"
                                v-model="addQuestionForm.question"
                                type="text"
                                placeholder=""
                                class="w-full bg-white border-border"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="add-q-type" class="text-foreground">Type:</Label>
                            <select
                                id="add-q-type"
                                v-model="addQuestionForm.type"
                                class="ehris-add-question-type flex h-9 w-full rounded-md border border-input bg-white px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                            >
                                <option v-for="opt in questionTypeOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                        </div>
                        <!-- Options section when Type is Radio or Checkbox -->
                        <div v-if="isOptionsType()" class="space-y-2">
                            <Label class="text-foreground">Options:</Label>
                            <div class="space-y-2">
                                <div
                                    v-for="(opt, optIndex) in addQuestionOptions"
                                    :key="optIndex"
                                    class="flex gap-2 items-center"
                                >
                                    <Input
                                        v-model="addQuestionOptions[optIndex]"
                                        :placeholder="`Option ${optIndex + 1}`"
                                        class="flex-1 bg-white border-border"
                                    />
                                    <Button
                                        v-if="addQuestionOptions.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="shrink-0 text-destructive hover:bg-destructive/10"
                                        title="Remove option"
                                        @click="removeOption(optIndex)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <Button type="button" variant="outline" size="sm" class="gap-1 text-muted-foreground" @click="addMoreOption">
                                <Plus class="h-4 w-4" />
                                Add More Option
                            </Button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 pt-2">
                            <Button type="button" variant="outline" size="sm" class="gap-1 text-muted-foreground" @click="addCurrentQuestionAndContinue">
                                <Plus class="h-4 w-4" />
                                Add Question
                            </Button>
                            <div class="flex-1" />
                            <Button type="button" variant="outline" @click="showAddQuestionDialog = false">Cancel</Button>
                            <Button type="submit" variant="default">Save</Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>

<!-- Global styles for SweetAlert delete confirmation (popup is rendered in body) -->
<style>
.ehris-swal-delete-popup .ehris-swal-actions {
    display: flex !important;
    flex-direction: row;
    gap: 0.5rem;
}
.ehris-swal-delete-popup .ehris-swal-cancel,
.ehris-swal-delete-popup .ehris-swal-confirm {
    display: inline-flex !important;
    align-items: center;
    padding: 0.5rem 1rem !important;
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

<style scoped>
/* Add Question dialog: Type dropdown blue border when focused */
.ehris-add-question-type {
    border-color: hsl(var(--border));
}
.ehris-add-question-type:focus {
    border-color: hsl(var(--primary));
}

/* Column header colors to match reference: Title & Category red, Description & Action blue */
:deep(.data-table-wrapper) table.dataTable thead th:nth-child(1) {
    color: hsl(var(--destructive));
}
:deep(.data-table-wrapper) table.dataTable thead th:nth-child(2) {
    color: hsl(var(--primary));
}
:deep(.data-table-wrapper) table.dataTable thead th:nth-child(3) {
    color: hsl(var(--destructive));
}
:deep(.data-table-wrapper) table.dataTable thead th:nth-child(4) {
    color: hsl(var(--primary));
}
</style>
