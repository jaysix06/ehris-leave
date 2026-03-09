<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { useSlots } from 'vue';
import { useSessionTrap } from '@/composables/useSessionTrap';

defineProps<{
    title?: string;
    description?: string;
    contentClass?: string;
}>();

const slots = useSlots();

useSessionTrap({
    mode: 'guest',
    redirectIfAuthenticated: '/dashboard',
});
</script>

<template>
    <AuthLayout :title="title" :description="description" :content-class="contentClass">
        <template v-if="slots.header" #header>
            <slot name="header" />
        </template>
        <slot />
    </AuthLayout>
</template>
