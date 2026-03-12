<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot password" />

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
                <h1 class="text-2xl font-semibold text-foreground">
                    Forgot password
                </h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Enter your email to receive a one-time password (OTP).
                </p>
            </div>

            <div
                v-if="status"
                class="mb-4 rounded-md border border-emerald-600/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300"
            >
                {{ status }}
            </div>

            <Form
                action="/forgot-password/otp/send"
                method="post"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5"
            >
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <Button
                    class="mt-2 w-full"
                    :disabled="processing"
                    data-test="email-otp-send-button"
                >
                    <Spinner v-if="processing" />
                    Send OTP
                </Button>

                <div class="space-x-1 text-center text-sm text-muted-foreground">
                    <span>Return to</span>
                    <TextLink :href="login()">log in</TextLink>
                </div>
            </Form>
        </div>
    </div>
</template>
