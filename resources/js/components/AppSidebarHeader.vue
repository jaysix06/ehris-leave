<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();

const currentTitle = computed(() => {
    if (!props.breadcrumbs.length) {
        return 'Dashboard';
    }

    return props.breadcrumbs[props.breadcrumbs.length - 1].title;
});
</script>

<template>
    <header class="ehris-header-shell">
        <div class="ehris-header-top">
            <div class="ehris-header-left">
                <SidebarTrigger class="ehris-trigger" />
                <div>
                    <p class="ehris-header-title">{{ currentTitle }}</p>
                    <p class="ehris-header-subtitle">Human Resource Information System</p>
                </div>
            </div>

            <div class="ehris-user-chip">
                <Avatar class="h-10 w-10 overflow-hidden rounded-full border border-border/60">
                    <AvatarImage
                        v-if="user.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                    />
                    <AvatarFallback class="bg-primary/10 text-primary">
                        {{ getInitials(user.name) }}
                    </AvatarFallback>
                </Avatar>
                <div>
                    <p class="ehris-user-name">{{ user.name }}</p>
                    <p class="ehris-user-role">{{ user.email }}</p>
                </div>
            </div>
        </div>

        <div v-if="breadcrumbs && breadcrumbs.length > 0" class="ehris-header-bottom">
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </div>
    </header>
</template>
