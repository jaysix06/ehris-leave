<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
</script>

<template>
    <AuthLayout
        title="Two-Factor Authentication Disabled"
        description="Two-factor authentication is currently disabled for this application."
    >
        <Head title="Two-Factor Authentication Disabled" />

        <div class="space-y-4 text-center">
            <p class="text-sm text-muted-foreground">
                Sign in using your email and password.
            </p>
            <TextLink href="/login">
                Back to Login
            </TextLink>
        </div>
    </AuthLayout>
</template>
