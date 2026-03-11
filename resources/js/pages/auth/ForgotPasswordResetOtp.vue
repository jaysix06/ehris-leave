<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    otpEmail: string;
}>();
</script>

<template>
    <Head title="Reset password" />

    <div class="flex min-h-svh items-center justify-center bg-background p-6">
        <div class="w-full max-w-md rounded-2xl border bg-card p-6 shadow-sm sm:p-8">
            <div class="mb-6 overflow-hidden rounded-xl">
                <img
                    src="/ehris.png"
                    alt="DepEd Ozamiz Unit School Division"
                    class="h-36 w-full object-cover object-center"
                />
            </div>

            <div class="mb-6 text-center">
                <h1 class="text-2xl font-semibold text-foreground">Reset password</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Create your new password.
                </p>
            </div>

            <Form
                action="/forgot-password/otp/reset"
                method="post"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5"
            >
                <div class="grid gap-2">
                    <Label for="otp-email">Email address</Label>
                    <Input id="otp-email" :model-value="otpEmail" readonly />
                </div>

                <div class="grid gap-2">
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

                <div class="grid gap-2">
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

                <Button class="mt-2 w-full" :disabled="processing">
                    <Spinner v-if="processing" />
                    Reset password
                </Button>
            </Form>
        </div>
    </div>
</template>
