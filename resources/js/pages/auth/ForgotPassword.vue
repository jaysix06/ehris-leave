<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Forgot password"
        description="Enter your email first to continue"
    >
        <Head title="Forgot password" />

        <div class="space-y-6">
            <Form
                action="/forgot-password/otp/send"
                method="post"
                v-slot="{ errors, processing }"
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

                <div class="my-6 flex items-center justify-start">
                    <Button
                        class="w-full"
                        :disabled="processing"
                        data-test="email-otp-send-button"
                    >
                        <Spinner v-if="processing" />
                        Send OTP
                    </Button>
                </div>
            </Form>

            <div class="space-x-1 text-center text-sm text-muted-foreground">
                <span>Or, return to</span>
                <TextLink :href="login()">log in</TextLink>
            </div>
        </div>
    </AuthLayout>
</template>
