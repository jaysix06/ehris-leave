<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

defineProps<{
    title?: string;
    description?: string;
    contentClass?: string;
}>();
</script>

<template>
    <AuthLayout :title="title" :description="description" :content-class="contentClass">
        <slot />
    </AuthLayout>
</template>
