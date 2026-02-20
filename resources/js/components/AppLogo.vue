<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useSidebar } from '@/components/ui/sidebar';

const { state } = useSidebar();
</script>

<template>
    <div
        class="flex aspect-auto size-auto items-center justify-center rounded-md "
        v-if="state==='expanded'"
    >
        <AppLogoIcon class="size-auto fill-current text-white dark:text-black" />
    </div>
    <div class="flex aspect-square size-8 items-center justify-center rounded-md "
        v-if="state==='collapsed'"
    >
        <AppLogoIcon class="size-auto fill-current text-white dark:text-black" />
    </div>
</template>
