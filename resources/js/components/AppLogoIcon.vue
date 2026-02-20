<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { useSidebar } from '@/components/ui/sidebar';


const { state } = useSidebar();

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/depedozamiz.png"
        alt="DepED Ozamiz City"
        :class="className"
        v-bind="$attrs"
        v-if="state==='expanded'"
    />
    <img
        src="/logo-sximo.png"
        alt="DepED Ozamiz City"
        :class="className"
        v-bind="$attrs"
        v-if="state==='collapsed'"
    />
</template>
