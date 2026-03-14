<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { toast } from 'vue3-toastify';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { useAvatarSrc } from '@/composables/useAvatarSrc';

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
};
const personalEmail = ref(user?.personal_email ?? '');
watch(() => (page.props.auth as { user?: typeof user })?.user, (u) => {
    personalEmail.value = u?.personal_email ?? '';
}, { deep: true });

const avatarInput = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);
const avatarSrc = useAvatarSrc(() => {
    const u = (page.props.auth as { user?: typeof user })?.user;
    return (u as Record<string, unknown> | undefined)?.avatar_url ?? (u as Record<string, unknown> | undefined)?.avatar ?? null;
});

const avatarUrl = computed(() => {
    if (avatarPreview.value) return avatarPreview.value;
    return avatarSrc.value ?? '/avatar-default.jpg';
});

function triggerAvatarChange() {
    avatarInput.value?.click();
}

function onAvatarChange(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        avatarPreview.value = URL.createObjectURL(file);
    }
}

const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED !== 'false';
const formOptions = {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
        toast.success('Profile updated.');
        avatarPreview.value = null;
        if (avatarInput.value) avatarInput.value.value = '';
        if (!reverbEnabled) router.reload();
    },
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Personal profile" />

        <h1 class="sr-only">Change personal profile / email</h1>

        <SettingsLayout>
            <div class="space-y-8">
                <Heading
                    variant="small"
                    title="Change personal profile / email"
                    description="Update your profile picture and personal email."
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    enctype="multipart/form-data"
                    :options="formOptions"
                    reset-on-success
                    :reset-on-error="['personal_email', 'avatar']"
                    class="space-y-8"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <input type="hidden" name="name" :value="user.name" />
                    <input type="hidden" name="email" :value="user.email" />

                    <!-- Profile picture -->
                    <div class="flex flex-col items-start gap-4">
                        <Label class="text-base font-medium">Profile picture</Label>
                        <div class="flex flex-col items-center gap-4">
                            <div
                                class="relative h-28 w-28 shrink-0 overflow-hidden rounded-full border-2 border-border bg-muted"
                            >
                                <img
                                    :src="avatarUrl"
                                    :alt="user.name"
                                    class="h-full w-full object-cover"
                                    @error="($event as Event & { currentTarget: HTMLImageElement }).currentTarget.src = '/avatar-default.jpg'"
                                />
                            </div>
                            <div class="flex flex-col items-center gap-2 text-center">
                                <input
                                    ref="avatarInput"
                                    type="file"
                                    name="avatar"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="hidden"
                                    @change="onAvatarChange"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    :disabled="processing"
                                    @click="triggerAvatarChange"
                                >
                                    Change photo
                                </Button>
                                <p class="text-xs text-muted-foreground">
                                    JPG, PNG, GIF or WebP. Max 10MB.
                                </p>
                                <InputError :message="errors.avatar" />
                            </div>
                        </div>
                    </div>

                    <!-- Personal email -->
                    <div class="grid gap-2">
                        <Label for="personal_email">Personal email</Label>
                        <Input
                            id="personal_email"
                            v-model="personalEmail"
                            name="personal_email"
                            type="email"
                            class="mt-1 block w-full max-w-md"
                            autocomplete="email"
                            placeholder="Enter your personal email"
                        />
                        <InputError :message="errors.personal_email" />
                        <p class="text-sm text-muted-foreground">
                            Your official DepEd email ({{ user.email }}) is used for login and cannot be changed here.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            :disabled="processing"
                        >
                            {{ processing ? 'Saving…' : 'Save profile' }}
                        </Button>
                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-muted-foreground"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
