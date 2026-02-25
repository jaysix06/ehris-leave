<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { useSlots } from 'vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const slots = useSlots();
</script>

<template>
    <AuthLayout :title="title" :description="description">
        <template v-if="slots.header" #header>
            <slot name="header" />
        </template>
        <slot />
    </AuthLayout>
</template>
