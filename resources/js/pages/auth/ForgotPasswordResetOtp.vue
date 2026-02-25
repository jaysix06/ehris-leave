<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';

defineProps<{
    otpEmail: string;
}>();
</script>

<template>
    <AuthLayout
        title="Reset password"
        description="Create your new password"
    >
        <Head title="Reset password" />

        <Form
            action="/forgot-password/otp/reset"
            method="post"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="otp-email">Email address</Label>
                <Input
                    id="otp-email"
                    :model-value="otpEmail"
                    readonly
                />
            </div>

            <div class="mt-4 grid gap-2">
                <Label for="password">New password</Label>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    placeholder="New password"
                    autofocus
                />
                <InputError :message="errors.password" />
            </div>

            <div class="mt-4 grid gap-2">
                <Label for="password_confirmation">Confirm new password</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Confirm new password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="w-full"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" />
                    Reset password
                </Button>
            </div>
        </Form>
    </AuthLayout>
</template>
