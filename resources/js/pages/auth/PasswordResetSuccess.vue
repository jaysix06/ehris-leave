<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';
import { onBeforeUnmount, onMounted } from 'vue';

let redirectTimer: number | undefined;

onMounted(() => {
    redirectTimer = window.setTimeout(() => {
        window.location.href = login().url;
    }, 4000);
});

onBeforeUnmount(() => {
    if (redirectTimer) {
        window.clearTimeout(redirectTimer);
    }
});
</script>

<template>
    <Head title="Password changed" />

    <div class="flex min-h-svh items-center justify-center bg-background p-6">
        <div class="w-full max-w-md rounded-2xl border bg-card p-6 text-center shadow-sm sm:p-8">
            <h1 class="text-2xl font-semibold text-foreground">Password successfully changed</h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Your password has been updated. You will be redirected to the login page in a few seconds.
            </p>

            <Button as-child class="mt-6 w-full">
                <Link :href="login()">Go to login now</Link>
            </Button>
        </div>
    </div>
</template>
