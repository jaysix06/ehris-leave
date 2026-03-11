<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';

defineProps<{
    status?: string;
    otpEmail: string;
}>();

const otp = ref('');

const sanitizeOtpInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const numericValue = target.value.replace(/\D/g, '').slice(0, 6);

    target.value = numericValue;
    otp.value = numericValue;
};
</script>

<template>
    <Head title="OTP verification" />

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
                <h1 class="text-2xl font-semibold text-foreground">OTP verification</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Enter the 6-digit code we sent to your email.
                </p>
            </div>

            <div
                v-if="status === 'otp-sent'"
                class="mb-4 rounded-md border border-emerald-600/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300"
            >
                A new 6-digit OTP has been sent.
            </div>

            <div class="mb-5 rounded-lg border border-border/80 bg-muted/50 px-4 py-3 text-sm">
                <span class="text-muted-foreground">Code sent to:</span>
                <p class="font-medium text-foreground">{{ otpEmail }}</p>
            </div>

            <Form
                action="/forgot-password/otp/verify"
                method="post"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5"
            >
                <div class="grid gap-2">
                    <Label for="otp">OTP code</Label>
                    <Input
                        id="otp"
                        type="text"
                        name="otp"
                        v-model="otp"
                        inputmode="numeric"
                        maxlength="6"
                        pattern="[0-9]*"
                        placeholder="000000"
                        autofocus
                        @input="sanitizeOtpInput"
                    />
                    <InputError :message="errors.otp" />
                </div>

                <Button class="mt-2 w-full" :disabled="processing">
                    <Spinner v-if="processing" />
                    Verify OTP
                </Button>
            </Form>

            <Form
                action="/forgot-password/otp/send"
                method="post"
                v-slot="{ processing }"
                class="mt-3"
            >
                <input type="hidden" name="email" :value="otpEmail" />
                <div class="text-center">
                    <Button
                        type="submit"
                        variant="link"
                        class="h-auto px-0 text-sm"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" />
                        Resend OTP
                    </Button>
                </div>
            </Form>

            <div class="mt-4 space-x-1 text-center text-sm text-muted-foreground">
                <span>Return to</span>
                <TextLink :href="login()">log in</TextLink>
            </div>
        </div>
    </div>
</template>
