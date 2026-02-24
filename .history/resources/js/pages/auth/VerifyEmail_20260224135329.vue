<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { send } from '@/routes/verification';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout title="Verify email" description="Check your inbox to verify your account.">
        <Head title="Email verification" />
        <template #header>
            <div class="overflow-hidden rounded-xl">
                <img
                    src="/dous.png"
                    alt="DepEd Ozamiz Unit School Division"
                    class="h-28 w-full max-w-xs object-contain object-center"
                />
            </div>
        </template>

        <div class="space-y-6">
            <p class="text-center text-sm text-muted-foreground">
                Please verify your email address by clicking on the link we just emailed to you.
            </p>

            <div
                v-if="status === 'verification-link-sent'"
                class="rounded-md border border-emerald-600/30 bg-emerald-500/10 px-3 py-2 text-center text-sm text-emerald-700 dark:text-emerald-300"
            >
                A new verification link has been sent to your email address.
            </div>

            <Form
                v-bind="send.form()"
                class="space-y-4 text-center"
                v-slot="{ processing }"
            >
                <Button :disabled="processing" class="w-full">
                    <Spinner v-if="processing" />
                    Resend verification email
                </Button>
            </Form>
        </div>
    </AuthLayout>
</template>
