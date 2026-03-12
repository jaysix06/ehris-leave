<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';

const ROLE_OPTIONS = ['Employee', 'HR Manager', 'AO Manager', 'SDS Manager', 'System Admin'] as const;
const EXTENSION_OPTIONS = ['', 'Jr.', 'Sr.', 'II', 'III', 'IV'] as const;
const JOB_TITLE_OPTIONS = [
    'Teacher I', 'Teacher II', 'Teacher III', 'Master Teacher I', 'Master Teacher II',
    'Principal I', 'Principal II', 'Head Teacher I', 'Head Teacher II', 'Administrative Officer',
] as const;

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user as {
    name: string;
    email: string;
    personal_email?: string | null;
    avatar?: string | null;
    role?: string | null;
    department_id?: string | number | null;
    hrId?: number | null;
    firstname?: string | null;
    lastname?: string | null;
    middlename?: string | null;
    extname?: string | null;
    job_title?: string | null;
};
const props = defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    departments?: { id: number; name: string }[];
}>();
const departments = computed(() => props.departments ?? []);
const officeName = computed(() => {
    const id = user?.department_id != null ? Number(user.department_id) : null;
    if (id == null) return '—';
    const d = departments.value.find((x) => x.id === id);
    return d?.name ?? '—';
});
const isEditingProfile = ref(false);
const profileForm = ref({
    firstname: '',
    lastname: '',
    middlename: '',
    extname: '',
    department_id: '',
    role: '',
    job_title: '',
});
function initProfileForm() {
    profileForm.value = {
        firstname: user?.firstname ?? '',
        lastname: user?.lastname ?? '',
        middlename: user?.middlename ?? '',
        extname: user?.extname ?? '',
        department_id: user?.department_id != null ? String(user.department_id) : '',
        role: user?.role ?? '',
        job_title: user?.job_title ?? '',
    };
}
onMounted(() => initProfileForm());
watch(() => page.props.auth?.user, () => initProfileForm(), { deep: true });
const avatarInput = ref<HTMLInputElement | null>(null);
const avatarSubmitRef = ref<HTMLButtonElement | null>(null);
const avatarPreview = ref<string | null>(null);

/** Resolve avatar URL from tbl_user.avatar (filename or path). */
const avatarUrl = computed(() => {
    if (avatarPreview.value) return avatarPreview.value;
    const raw = user?.avatar;
    if (typeof raw !== 'string' || raw.trim() === '') return '/storage/avatars/avatar-default.jpg';
    const s = raw.trim();
    const base = s.split('?')[0]?.split('#')[0] ?? '';
    const lower = base.toLowerCase();
    if (lower === 'avatar-default.jpg' || lower.endsWith('/avatar-default.jpg')) {
        return '/storage/avatars/avatar-default.jpg';
    }
    if (/^(https?:)?\/\//i.test(base) || base.startsWith('/') || base.startsWith('data:') || base.startsWith('blob:')) {
        return base.startsWith('/') ? base : `/${base}`;
    }
    return `/storage/avatars/${base.split('/').pop() ?? base}`;
});

function triggerAvatarChange() {
    avatarInput.value?.click();
}

function onAvatarChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        avatarPreview.value = URL.createObjectURL(file);
        // Single-step upload: submit form as soon as a file is chosen
        avatarSubmitRef.value?.click();
    }
}

const displayRole = computed(() => user?.role ?? '—');
const displayDepartmentId = computed(() => (user?.department_id != null && user?.department_id !== '') ? String(user.department_id) : '—');
const displayHrId = computed(() => user?.hrId != null ? String(user.hrId) : '—');
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="My Profile" />

        <h1 class="sr-only">My Profile</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-8">
                <!-- My Profile header with red line -->
                <div class="border-b-2 border-red-600 pb-2">
                    <h2 class="text-xl font-semibold text-foreground">My Profile</h2>
                </div>

                <!-- Profile picture, name, and avatar upload -->
                <div class="flex flex-col items-center gap-4">
                    <div
                        class="relative h-32 w-32 shrink-0 overflow-hidden rounded-full border-2 border-border bg-muted"
                    >
                        <img
                            :src="avatarUrl"
                            :alt="user.name"
                            class="h-full w-full object-cover"
                            @error="($event as Event & { currentTarget: HTMLImageElement }).currentTarget.src = '/storage/avatars/avatar-default.jpg'"
                        />
                    </div>
                    <p class="text-lg font-semibold text-foreground">
                        {{ user.name }}
                    </p>
                    <Form
                        v-bind="ProfileController.update.form()"
                        class="flex flex-col items-center gap-2 w-full max-w-sm"
                        enctype="multipart/form-data"
                        v-slot="{ errors, processing }"
                    >
                        <input
                            ref="avatarInput"
                            type="file"
                            name="avatar"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            class="hidden"
                            @change="onAvatarChange"
                        />
                        <button
                            ref="avatarSubmitRef"
                            type="submit"
                            class="hidden"
                            aria-hidden="true"
                            tabindex="-1"
                        />
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <Button
                                type="button"
                                class="bg-red-600 hover:bg-red-700 text-white"
                                :disabled="processing"
                                @click="triggerAvatarChange"
                            >
                                {{ processing ? 'Uploading…' : 'Upload profile picture' }}
                            </Button>
                            <Button
                                type="submit"
                                variant="outline"
                                :disabled="processing"
                            >
                                Save
                            </Button>
                        </div>
                        <p class="text-xs text-muted-foreground text-center">
                            JPG, PNG, GIF or WebP. Max 2MB. Stored in tbl_user.avatar.
                        </p>
                        <InputError :message="errors.avatar" />
                        <input type="hidden" name="name" :value="user.name" />
                        <input type="hidden" name="email" :value="user.email" />
                        <input type="hidden" name="personal_email" :value="user.personal_email ?? ''" />
                    </Form>
                </div>

                <!-- Read-only details card: Name, Personal email, Email, Role, Department ID, HR ID -->
                <div class="rounded-lg border border-border bg-card shadow-sm">
                    <div class="border-b border-border px-4 py-3 flex justify-between items-center gap-4">
                        <span class="text-sm text-muted-foreground shrink-0">Name</span>
                        <span class="text-sm font-medium text-primary text-right">{{ user.name || '—' }}</span>
                    </div>
                    <div class="border-b border-border px-4 py-3 flex justify-between items-center gap-4">
                        <span class="text-sm text-muted-foreground shrink-0">Personal email</span>
                        <a
                            v-if="user.personal_email"
                            :href="`mailto:${user.personal_email}`"
                            class="text-sm font-medium text-primary hover:underline text-right truncate"
                        >
                            {{ user.personal_email }}
                        </a>
                        <span v-else class="text-sm font-medium text-muted-foreground text-right">—</span>
                    </div>
                    <div class="border-b border-border px-4 py-3 flex justify-between items-center gap-4">
                        <span class="text-sm text-muted-foreground shrink-0">Email</span>
                        <a :href="`mailto:${user.email}`" class="text-sm font-medium text-primary hover:underline text-right truncate">
                            {{ user.email }}
                        </a>
                    </div>
                    <div class="border-b border-border px-4 py-3 flex justify-between items-center gap-4">
                        <span class="text-sm text-muted-foreground shrink-0">Role</span>
                        <span class="text-sm font-medium text-primary">{{ displayRole }}</span>
                    </div>
                    <div class="border-b border-border px-4 py-3 flex justify-between items-center gap-4">
                        <span class="text-sm text-muted-foreground shrink-0">Department ID</span>
                        <span class="text-sm font-medium text-primary">{{ displayDepartmentId }}</span>
                    </div>
                    <div class="px-4 py-3 flex justify-between items-center gap-4">
                        <span class="text-sm text-muted-foreground shrink-0">HR ID</span>
                        <span class="text-sm font-medium text-primary">{{ displayHrId }}</span>
                    </div>
                </div>

                <!-- User Profile: editable details with Edit / Apply changes / Cancel -->
                <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
                    <div class="border-b border-red-600 pb-2 mb-4">
                        <h3 class="text-lg font-semibold text-foreground">User Profile</h3>
                    </div>
                    <Form
                        v-bind="ProfileController.update.form()"
                        class="space-y-4"
                        enctype="multipart/form-data"
                        v-slot="{ errors, processing }"
                        @submit="isEditingProfile = false"
                    >
                        <input type="hidden" name="name" :value="user.name" />
                        <input type="hidden" name="email" :value="user.email" />
                        <input type="hidden" name="personal_email" :value="user.personal_email ?? ''" />
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="profile-firstname">Firstname</Label>
                                <Input
                                    id="profile-firstname"
                                    name="firstname"
                                    v-model="profileForm.firstname"
                                    :readonly="!isEditingProfile"
                                    :class="isEditingProfile ? '' : 'bg-muted'"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="profile-middlename">Middlename</Label>
                                <Input
                                    id="profile-middlename"
                                    name="middlename"
                                    v-model="profileForm.middlename"
                                    :readonly="!isEditingProfile"
                                    :class="isEditingProfile ? '' : 'bg-muted'"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="profile-lastname">Lastname</Label>
                                <Input
                                    id="profile-lastname"
                                    name="lastname"
                                    v-model="profileForm.lastname"
                                    :readonly="!isEditingProfile"
                                    :class="isEditingProfile ? '' : 'bg-muted'"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="profile-extname">Extension Name</Label>
                                <select
                                    id="profile-extname"
                                    name="extname"
                                    v-model="profileForm.extname"
                                    :disabled="!isEditingProfile"
                                    :class="isEditingProfile ? 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm' : 'flex h-9 w-full rounded-md border border-input bg-muted px-3 py-1 text-sm'"
                                >
                                    <option value="">- Select Extension -</option>
                                    <option v-for="ext in EXTENSION_OPTIONS.filter(Boolean)" :key="ext" :value="ext">{{ ext }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label for="profile-office">Office / Department</Label>
                                <select
                                    id="profile-office"
                                    name="department_id"
                                    v-model="profileForm.department_id"
                                    :disabled="!isEditingProfile"
                                    :class="isEditingProfile ? 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm' : 'flex h-9 w-full rounded-md border border-input bg-muted px-3 py-1 text-sm'"
                                >
                                    <option value="">- Select Office -</option>
                                    <option
                                        v-for="d in departments"
                                        :key="d.id"
                                        :value="String(d.id)"
                                    >
                                        {{ d.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label for="profile-role">Role</Label>
                                <select
                                    id="profile-role"
                                    name="role"
                                    v-model="profileForm.role"
                                    :disabled="!isEditingProfile"
                                    :class="isEditingProfile ? 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm' : 'flex h-9 w-full rounded-md border border-input bg-muted px-3 py-1 text-sm'"
                                >
                                    <option value="">- Select Role -</option>
                                    <option
                                        v-for="r in ROLE_OPTIONS"
                                        :key="r"
                                        :value="r"
                                    >
                                        {{ r }}
                                    </option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <Label for="profile-job_title">Job title</Label>
                                <select
                                    id="profile-job_title"
                                    name="job_title"
                                    v-model="profileForm.job_title"
                                    :disabled="!isEditingProfile"
                                    :class="isEditingProfile ? 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm' : 'flex h-9 w-full rounded-md border border-input bg-muted px-3 py-1 text-sm'"
                                >
                                    <option value="">—</option>
                                    <option
                                        v-for="t in JOB_TITLE_OPTIONS"
                                        :key="t"
                                        :value="t"
                                    >
                                        {{ t }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <Button
                                v-if="!isEditingProfile"
                                type="button"
                                variant="outline"
                                @click="isEditingProfile = true"
                            >
                                Edit
                            </Button>
                            <template v-else>
                                <Button type="submit" :disabled="processing">
                                    Apply changes
                                </Button>
                                <Button type="button" variant="outline" @click="isEditingProfile = false; initProfileForm();">
                                    Cancel
                                </Button>
                            </template>
                        </div>
                        <InputError v-if="errors.firstname" :message="errors.firstname" />
                        <InputError v-if="errors.role" :message="errors.role" />
                    </Form>
                </div>

                <DeleteUser />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
