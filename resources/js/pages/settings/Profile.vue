<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
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
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

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

const avatarInput = ref<HTMLInputElement | null>(null);
const avatarPreview = ref<string | null>(null);

const avatarUrl = computed(() => {
    if (avatarPreview.value) return avatarPreview.value;
    const filename = user?.avatar && user.avatar !== 'avatar-default.jpg' ? user.avatar : 'avatar-default.jpg';
    return `/storage/avatars/${filename}`;
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Profile information"
                    description="Update your name, email, and profile picture"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    enctype="multipart/form-data"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <div class="flex items-center gap-4">
                            <div
                                class="relative h-20 w-20 shrink-0 overflow-hidden rounded-full border border-border bg-muted"
                            >
                                <img
                                    :src="avatarUrl"
                                    :alt="user.name"
                                    class="h-full w-full object-cover"
                                    @error="($event as Event & { currentTarget: HTMLImageElement }).currentTarget.src = '/storage/avatars/avatar-default.jpg'"
                                />
                            </div>
                            <div class="flex flex-col gap-1">
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
                                    size="sm"
                                    @click="triggerAvatarChange"
                                >
                                    Change
                                </Button>
                                <p class="text-xs text-muted-foreground">
                                    JPG, PNG, GIF or WebP. Max 2MB.
                                </p>
                            </div>
                        </div>
                        <InputError class="mt-2" :message="errors.avatar" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="personal_email">Personal email</Label>
                        <Input
                            id="personal_email"
                            type="email"
                            class="mt-1 block w-full"
                            name="personal_email"
                            :default-value="user.personal_email ?? ''"
                            autocomplete="email"
                            placeholder="Personal email address"
                        />
                        <InputError class="mt-2" :message="errors.personal_email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Work / DepEd email address"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Save</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
