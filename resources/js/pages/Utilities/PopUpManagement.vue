<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { toast } from 'vue3-toastify';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import utilitiesRoutes from '@/routes/utilities';

type PopupMessageRow = {
    id: number;
    message: string;
    link: string | null;
    status: 'Active' | 'Inactive';
    created_at: string;
};

const pageTitle = 'Utilities - Pop Up Management';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
    },
];

const page = usePage();
const popupMessages = computed(() => (page.props.popupMessages ?? []) as PopupMessageRow[]);

const showAddModal = ref(false);
const showEditModal = ref(false);
const editingId = ref<number | null>(null);

const newPopupMessage = reactive({
    message: '',
    link: '',
    status: 'Active' as 'Active' | 'Inactive',
});

const editPopupMessage = reactive({
    message: '',
    link: '',
    // Status is not editable in the modal
});

const openAddModal = () => {
    newPopupMessage.message = '';
    newPopupMessage.link = '';
    newPopupMessage.status = 'Active';
    showAddModal.value = true;
};

const closeAddModal = () => {
    showAddModal.value = false;
    newPopupMessage.message = '';
    newPopupMessage.link = '';
    newPopupMessage.status = 'Active';
};

const openEditModal = (row: PopupMessageRow) => {
    editingId.value = row.id;
    editPopupMessage.message = row.message;
    editPopupMessage.link = row.link || '';
    // Status is not editable in the modal - it's managed via table buttons
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingId.value = null;
    editPopupMessage.message = '';
    editPopupMessage.link = '';
};

const createPopupMessage = () => {
    if (!newPopupMessage.message.trim()) {
        toast.error('Message is required.');
        return;
    }

    // Prepare data: convert empty link to null
    const data = {
        message: newPopupMessage.message.trim(),
        link: newPopupMessage.link.trim() || null,
        status: newPopupMessage.status,
    };

    router.post(utilitiesRoutes.popUpManagement.store().url, data, {
        onSuccess: () => {
            closeAddModal();
            toast.success('Popup message created successfully.');
        },
        onError: (errors) => {
            const errorMessage = errors?.message?.[0] || errors?.link?.[0] || errors?.status?.[0] || 'Failed to create popup message.';
            toast.error(errorMessage);
        },
    });
};

const updatePopupMessage = () => {
    if (!editPopupMessage.message.trim()) {
        toast.error('Message is required.');
        return;
    }

    if (editingId.value === null) {
        return;
    }

    // Prepare data: convert empty link to null (status is not included - can only be changed via table buttons)
    const trimmedLink = editPopupMessage.link.trim();
    const data: { message: string; link: string | null } = {
        message: editPopupMessage.message.trim(),
        link: trimmedLink === '' ? null : trimmedLink,
    };

    router.put(utilitiesRoutes.popUpManagement.update(editingId.value).url, data, {
        onSuccess: () => {
            closeEditModal();
            toast.success('Popup message updated successfully.');
        },
        onError: (errors) => {
            console.error('Update error:', errors);
            const errorMessage = errors?.message?.[0] || errors?.link?.[0] || errors?.status?.[0] || 'Failed to update popup message.';
            toast.error(errorMessage);
        },
    });
};

const toggleStatus = (row: PopupMessageRow, newStatus: 'Active' | 'Inactive') => {
    // Only update if status is different
    if (row.status === newStatus) {
        return;
    }

    // Update status via API
    const data = {
        message: row.message,
        link: row.link || null,
        status: newStatus,
    };

    router.put(utilitiesRoutes.popUpManagement.update(row.id).url, data, {
        onSuccess: () => {
            toast.success(`Popup message status changed to ${newStatus}.`);
        },
        onError: (errors) => {
            const errorMessage = errors?.status?.[0] || 'Failed to update popup status.';
            toast.error(errorMessage);
        },
    });
};

const deletePopupMessage = (row: PopupMessageRow) => {
    if (!confirm(`Delete popup message "${row.message.substring(0, 50)}${row.message.length > 50 ? '...' : ''}"?`)) {
        return;
    }

    router.delete(utilitiesRoutes.popUpManagement.destroy(row.id).url, {
        onSuccess: () => {
            toast.success('Popup message deleted successfully.');
        },
        onError: () => {
            toast.error('Failed to delete popup message.');
        },
    });
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }).replace(',', '');
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-2xl font-semibold">{{ pageTitle }}</h1>
                    <button
                        type="button"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
                        @click="openAddModal"
                    >
                        Add New Popup
                    </button>
                </div>

                <div class="mt-6">
                    <h2 class="text-lg font-semibold">Popup Messages</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full border border-border text-sm">
                            <thead class="bg-muted/30">
                                <tr>
                                    <th class="border border-border px-3 py-2 text-left">Message</th>
                                    <th class="border border-border px-3 py-2 text-left">Status</th>
                                    <th class="border border-border px-3 py-2 text-left">Created At</th>
                                    <th class="border border-border px-3 py-2 text-left">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in popupMessages" :key="row.id">
                                    <td class="border border-border px-3 py-2">
                                        <div class="max-w-md">
                                            <p class="break-words">{{ row.message }}</p>
                                            <p v-if="row.link" class="mt-1 text-xs text-muted-foreground">
                                                Link: <a :href="row.link" target="_blank" class="text-primary hover:underline">{{ row.link }}</a>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="border border-border px-3 py-2">
                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                :class="[
                                                    'rounded-md border px-3 py-1 text-xs font-semibold transition-colors',
                                                    row.status === 'Active'
                                                        ? 'border-green-500 bg-green-500 text-white hover:bg-green-600'
                                                        : 'border-input bg-background text-foreground hover:bg-muted',
                                                ]"
                                                @click="toggleStatus(row, 'Active')"
                                            >
                                                Active
                                            </button>
                                            <button
                                                type="button"
                                                :class="[
                                                    'rounded-md border px-3 py-1 text-xs font-semibold transition-colors',
                                                    row.status === 'Inactive'
                                                        ? 'border-gray-500 bg-gray-500 text-white hover:bg-gray-600'
                                                        : 'border-input bg-background text-foreground hover:bg-muted',
                                                ]"
                                                @click="toggleStatus(row, 'Inactive')"
                                            >
                                                Inactive
                                            </button>
                                        </div>
                                    </td>
                                    <td class="border border-border px-3 py-2">{{ formatDate(row.created_at) }}</td>
                                    <td class="border border-border px-3 py-2">
                                        <div class="flex gap-2">
                                            <button
                                                type="button"
                                                class="rounded-md border border-orange-500 px-3 py-1 text-orange-600 hover:bg-orange-50"
                                                @click="openEditModal(row)"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-md border border-destructive px-3 py-1 text-destructive hover:bg-destructive/10"
                                                @click="deletePopupMessage(row)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="popupMessages.length === 0">
                                    <td class="border border-border px-3 py-3 text-center text-muted-foreground" colspan="4">
                                        No popup messages found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Modal -->
        <div
            v-if="showAddModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="closeAddModal"
        >
            <div class="w-full max-w-lg rounded-lg bg-background p-6 shadow-lg">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Add New Popup Message</h2>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        @click="closeAddModal"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Message:</label>
                        <textarea
                            v-model="newPopupMessage.message"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            rows="4"
                            placeholder="Enter popup message"
                            maxlength="1000"
                        ></textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Link:</label>
                        <input
                            v-model="newPopupMessage.link"
                            type="url"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Enter link URL (optional)"
                            maxlength="500"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Status:</label>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                :class="[
                                    'flex-1 rounded-md border px-4 py-2 text-sm font-semibold transition-colors',
                                    newPopupMessage.status === 'Active'
                                        ? 'border-green-500 bg-green-500 text-white hover:bg-green-600'
                                        : 'border-input bg-background text-foreground hover:bg-muted',
                                ]"
                                @click="newPopupMessage.status = 'Active'"
                            >
                                Active
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'flex-1 rounded-md border px-4 py-2 text-sm font-semibold transition-colors',
                                    newPopupMessage.status === 'Inactive'
                                        ? 'border-gray-500 bg-gray-500 text-white hover:bg-gray-600'
                                        : 'border-input bg-background text-foreground hover:bg-muted',
                                ]"
                                @click="newPopupMessage.status = 'Inactive'"
                            >
                                Inactive
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
                        @click="createPopupMessage"
                    >
                        Add Popup
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="closeEditModal"
        >
            <div class="w-full max-w-lg rounded-lg bg-background p-6 shadow-lg">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Edit Popup Message</h2>
                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground"
                        @click="closeEditModal"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Message:</label>
                        <textarea
                            v-model="editPopupMessage.message"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            rows="4"
                            placeholder="Enter popup message"
                            maxlength="1000"
                        ></textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Link:</label>
                        <input
                            v-model="editPopupMessage.link"
                            type="url"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            placeholder="Enter link URL (optional)"
                            maxlength="500"
                        />
                    </div>

                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        type="button"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"
                        @click="updatePopupMessage"
                    >
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
