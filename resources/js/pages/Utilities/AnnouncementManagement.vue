<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { toast } from 'vue3-toastify';
import AppModal from '@/components/AppModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import utilitiesRoutes from '@/routes/utilities';

type AnnouncementLink = {
    label: string;
    url: string;
};

type AnnouncementRow = {
    id: number;
    title: string;
    content: string | null;
    links: AnnouncementLink[] | null;
    status: 'Active' | 'Inactive';
    created_at: string;
};

const pageTitle = 'Utilities - Announcement Management';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
    },
];

const page = usePage();
const announcements = computed(() => (page.props.announcements ?? []) as AnnouncementRow[]);

const showAddModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingId = ref<number | null>(null);
const pendingDeleteAnnouncement = ref<AnnouncementRow | null>(null);
const isDeleteLoading = ref(false);

const newAnnouncement = reactive({
    title: '',
    content: '',
    status: 'Active' as 'Active' | 'Inactive',
    links: [{ label: '', url: '' }] as AnnouncementLink[],
});

const editAnnouncement = reactive({
    title: '',
    content: '',
    status: 'Active' as 'Active' | 'Inactive',
    links: [{ label: '', url: '' }] as AnnouncementLink[],
});

const resetNewAnnouncement = (): void => {
    newAnnouncement.title = '';
    newAnnouncement.content = '';
    newAnnouncement.status = 'Active';
    newAnnouncement.links = [{ label: '', url: '' }];
};

const resetEditAnnouncement = (): void => {
    editAnnouncement.title = '';
    editAnnouncement.content = '';
    editAnnouncement.status = 'Active';
    editAnnouncement.links = [{ label: '', url: '' }];
    editingId.value = null;
};

const openAddModal = (): void => {
    resetNewAnnouncement();
    showAddModal.value = true;
};

const closeAddModal = (): void => {
    showAddModal.value = false;
    resetNewAnnouncement();
};

const openEditModal = (row: AnnouncementRow): void => {
    editAnnouncement.title = row.title;
    editAnnouncement.content = row.content ?? '';
    editAnnouncement.status = row.status;
    editAnnouncement.links = row.links && row.links.length > 0
        ? row.links.map((link) => ({ label: link.label ?? '', url: link.url ?? '' }))
        : [{ label: '', url: '' }];
    editingId.value = row.id;
    showEditModal.value = true;
};

const closeEditModal = (): void => {
    showEditModal.value = false;
    resetEditAnnouncement();
};

const addLinkField = (target: 'new' | 'edit'): void => {
    if (target === 'new') {
        newAnnouncement.links.push({ label: '', url: '' });
        return;
    }

    editAnnouncement.links.push({ label: '', url: '' });
};

const removeLinkField = (target: 'new' | 'edit', index: number): void => {
    const list = target === 'new' ? newAnnouncement.links : editAnnouncement.links;

    if (list.length <= 1) {
        list[0] = { label: '', url: '' };
        return;
    }

    list.splice(index, 1);
};

const normalizeLinks = (links: AnnouncementLink[]): AnnouncementLink[] => {
    return links
        .map((link) => ({
            label: link.label.trim(),
            url: link.url.trim(),
        }))
        .filter((link) => link.label !== '' || link.url !== '');
};

const createAnnouncement = (): void => {
    if (!newAnnouncement.title.trim()) {
        toast.error('Title is required.');
        return;
    }

    const payload = {
        title: newAnnouncement.title.trim(),
        content: newAnnouncement.content.trim() || null,
        status: newAnnouncement.status,
        links: normalizeLinks(newAnnouncement.links),
    };

    router.post(utilitiesRoutes.announcementManagement.store().url, payload, {
        onSuccess: () => {
            closeAddModal();
            toast.success('Announcement created successfully.');
        },
        onError: (errors) => {
            const errorMessage = errors?.title?.[0] || errors?.content?.[0] || errors?.status?.[0] || errors?.['links.0.url']?.[0] || 'Failed to create announcement.';
            toast.error(errorMessage);
        },
    });
};

const updateAnnouncement = (): void => {
    if (editingId.value === null) {
        return;
    }

    if (!editAnnouncement.title.trim()) {
        toast.error('Title is required.');
        return;
    }

    const payload = {
        title: editAnnouncement.title.trim(),
        content: editAnnouncement.content.trim() || null,
        status: editAnnouncement.status,
        links: normalizeLinks(editAnnouncement.links),
    };

    router.put(utilitiesRoutes.announcementManagement.update(editingId.value).url, payload, {
        onSuccess: () => {
            closeEditModal();
            toast.success('Announcement updated successfully.');
        },
        onError: (errors) => {
            const errorMessage = errors?.title?.[0] || errors?.content?.[0] || errors?.status?.[0] || errors?.['links.0.url']?.[0] || 'Failed to update announcement.';
            toast.error(errorMessage);
        },
    });
};

const requestDeleteAnnouncement = (row: AnnouncementRow): void => {
    pendingDeleteAnnouncement.value = row;
    showDeleteModal.value = true;
};

const cancelDeleteAnnouncement = (): void => {
    if (isDeleteLoading.value) {
        return;
    }

    showDeleteModal.value = false;
    pendingDeleteAnnouncement.value = null;
};

const confirmDeleteAnnouncement = (): void => {
    if (!pendingDeleteAnnouncement.value) {
        return;
    }

    isDeleteLoading.value = true;

    router.delete(utilitiesRoutes.announcementManagement.destroy(pendingDeleteAnnouncement.value.id).url, {
        onSuccess: () => {
            toast.success('Announcement deleted successfully.');
            showDeleteModal.value = false;
            pendingDeleteAnnouncement.value = null;
        },
        onError: () => {
            toast.error('Failed to delete announcement.');
        },
        onFinish: () => {
            isDeleteLoading.value = false;
        },
    });
};

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);

    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
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
                        Add Announcement
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border border-border text-sm">
                        <thead class="bg-muted/30">
                            <tr>
                                <th class="border border-border px-3 py-2 text-left">Announcement</th>
                                <th class="border border-border px-3 py-2 text-left">Status</th>
                                <th class="border border-border px-3 py-2 text-left">Created At</th>
                                <th class="border border-border px-3 py-2 text-left">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in announcements" :key="row.id">
                                <td class="border border-border px-3 py-2 align-top">
                                    <p class="font-semibold">{{ row.title }}</p>
                                    <p v-if="row.content" class="mt-1 max-w-2xl whitespace-pre-line text-muted-foreground">{{ row.content }}</p>
                                    <ul v-if="row.links && row.links.length > 0" class="mt-2 space-y-1 text-xs">
                                        <li v-for="(link, index) in row.links" :key="`${row.id}-${index}`">
                                            <a
                                                :href="link.url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-primary hover:underline"
                                            >
                                                {{ link.label || link.url }}
                                            </a>
                                        </li>
                                    </ul>
                                </td>
                                <td class="border border-border px-3 py-2">{{ row.status }}</td>
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
                                            @click="requestDeleteAnnouncement(row)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="announcements.length === 0">
                                <td colspan="4" class="border border-border px-3 py-3 text-center text-muted-foreground">
                                    No announcements found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closeAddModal">
            <div class="w-full max-w-2xl rounded-lg bg-background p-6 shadow-lg">
                <h2 class="text-xl font-semibold">Add Announcement</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Title</label>
                        <input v-model="newAnnouncement.title" type="text" maxlength="255" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Content</label>
                        <textarea v-model="newAnnouncement.content" rows="4" maxlength="3000" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Status</label>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                :class="[
                                    'rounded-md border px-4 py-2 text-sm font-semibold transition-colors',
                                    newAnnouncement.status === 'Active' ? 'border-green-500 bg-green-500 text-white hover:bg-green-600' : 'border-input bg-background text-foreground hover:bg-muted',
                                ]"
                                @click="newAnnouncement.status = 'Active'"
                            >
                                Active
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-md border px-4 py-2 text-sm font-semibold transition-colors',
                                    newAnnouncement.status === 'Inactive' ? 'border-gray-500 bg-gray-500 text-white hover:bg-gray-600' : 'border-input bg-background text-foreground hover:bg-muted',
                                ]"
                                @click="newAnnouncement.status = 'Inactive'"
                            >
                                Inactive
                            </button>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-medium">Links (optional)</label>
                            <button type="button" class="rounded-md border border-input px-3 py-1 text-xs hover:bg-muted" @click="addLinkField('new')">Add Link</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(link, index) in newAnnouncement.links" :key="`new-${index}`" class="grid gap-2 md:grid-cols-[1fr_1.5fr_auto]">
                                <input v-model="link.label" type="text" maxlength="100" class="rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Label" />
                                <input v-model="link.url" type="url" maxlength="500" class="rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="https://example.com" />
                                <button type="button" class="rounded-md border border-destructive px-3 py-2 text-xs text-destructive hover:bg-destructive/10" @click="removeLinkField('new', index)">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-md border border-input px-4 py-2 text-sm hover:bg-muted" @click="closeAddModal">Cancel</button>
                    <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90" @click="createAnnouncement">Create</button>
                </div>
            </div>
        </div>

        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closeEditModal">
            <div class="w-full max-w-2xl rounded-lg bg-background p-6 shadow-lg">
                <h2 class="text-xl font-semibold">Edit Announcement</h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium">Title</label>
                        <input v-model="editAnnouncement.title" type="text" maxlength="255" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Content</label>
                        <textarea v-model="editAnnouncement.content" rows="4" maxlength="3000" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"></textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Status</label>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                :class="[
                                    'rounded-md border px-4 py-2 text-sm font-semibold transition-colors',
                                    editAnnouncement.status === 'Active' ? 'border-green-500 bg-green-500 text-white hover:bg-green-600' : 'border-input bg-background text-foreground hover:bg-muted',
                                ]"
                                @click="editAnnouncement.status = 'Active'"
                            >
                                Active
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-md border px-4 py-2 text-sm font-semibold transition-colors',
                                    editAnnouncement.status === 'Inactive' ? 'border-gray-500 bg-gray-500 text-white hover:bg-gray-600' : 'border-input bg-background text-foreground hover:bg-muted',
                                ]"
                                @click="editAnnouncement.status = 'Inactive'"
                            >
                                Inactive
                            </button>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <label class="text-sm font-medium">Links (optional)</label>
                            <button type="button" class="rounded-md border border-input px-3 py-1 text-xs hover:bg-muted" @click="addLinkField('edit')">Add Link</button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(link, index) in editAnnouncement.links" :key="`edit-${index}`" class="grid gap-2 md:grid-cols-[1fr_1.5fr_auto]">
                                <input v-model="link.label" type="text" maxlength="100" class="rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Label" />
                                <input v-model="link.url" type="url" maxlength="500" class="rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="https://example.com" />
                                <button type="button" class="rounded-md border border-destructive px-3 py-2 text-xs text-destructive hover:bg-destructive/10" @click="removeLinkField('edit', index)">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-md border border-input px-4 py-2 text-sm hover:bg-muted" @click="closeEditModal">Cancel</button>
                    <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90" @click="updateAnnouncement">Save Changes</button>
                </div>
            </div>
        </div>

        <AppModal v-model="showDeleteModal" title="Delete Announcement" tone="disapprove" :persistent="isDeleteLoading">
            <p class="text-sm text-muted-foreground">
                Are you sure you want to delete
                <span class="font-semibold text-foreground">{{ pendingDeleteAnnouncement?.title }}</span>?
                This action cannot be undone.
            </p>

            <template #actions>
                <button
                    type="button"
                    class="rounded-md border border-input px-4 py-2 text-sm hover:bg-muted"
                    :disabled="isDeleteLoading"
                    @click="cancelDeleteAnnouncement"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded-md bg-destructive px-4 py-2 text-sm font-semibold text-destructive-foreground hover:bg-destructive/90 disabled:opacity-60"
                    :disabled="isDeleteLoading"
                    @click="confirmDeleteAnnouncement"
                >
                    {{ isDeleteLoading ? 'Deleting...' : 'Delete' }}
                </button>
            </template>
        </AppModal>
    </AppLayout>
</template>
