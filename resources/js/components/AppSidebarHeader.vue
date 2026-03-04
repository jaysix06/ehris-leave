<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItem } from '@/types';
import { logout } from '@/routes';

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
const avatarSrc = computed(() => {
    const avatar = user.value?.avatar;
    if (typeof avatar !== 'string') return null;

    const s = avatar.trim();
    if (s === '') return null;

    const cleaned = s.split('?')[0]?.split('#')[0] ?? '';
    const normalizedName = cleaned.split('/').pop()?.toLowerCase() ?? '';
    if (normalizedName === 'avatar-default.jpg') {
        return '/avatar-default.jpg';
    }

    if (/^(https?:)?\/\//i.test(s) || s.startsWith('/') || s.startsWith('data:') || s.startsWith('blob:')) {
        return s;
    }

    return `/${s}`;
});

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

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button
                        type="button"
                        class="ehris-user-chip hover:bg-muted/60 transition-colors"
                    >
                        <Avatar class="h-10 w-10 overflow-hidden rounded-full border border-border/60">
                            <AvatarImage
                                v-if="avatarSrc"
                                :src="avatarSrc"
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
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    align="end"
                    class="w-72 overflow-hidden rounded-2xl border border-border/70 bg-card p-0 shadow-xl"
                >
                    <div class="bg-muted px-6 py-6 text-center text-foreground">
                        <Avatar
                            class="mx-auto mb-3 h-20 w-20 overflow-hidden rounded-full border-4 border-background bg-background"
                        >
                            <AvatarImage
                                v-if="avatarSrc"
                                :src="avatarSrc"
                                :alt="user.name"
                            />
                            <AvatarFallback class="text-xl font-semibold text-primary">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <p class="text-lg font-semibold">
                            {{ user.name }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </div>
                    <div class="space-y-3 bg-card px-6 py-4">
                        <Link href="/settings/password">
                            <button
                                type="button"
                                class="flex w-full items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-sm font-medium hover:bg-muted"
                            >
                                Change password
                            </button>
                        </Link>
                        <div class="flex gap-3">
                            <Link href="/my-profile" class="flex-1">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-sm font-medium hover:bg-muted"
                                >
                                    My profile
                                </button>
                            </Link>
                            <Link
                                :href="logout()"
                                as="button"
                                class="flex-1 rounded-md border border-destructive/40 bg-destructive px-3 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90"
                                @click="router.flushAll()"
                            >
                                Sign out
                            </Link>
                        </div>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

        <div v-if="breadcrumbs && breadcrumbs.length > 0" class="ehris-header-bottom">
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </div>
    </header>
</template>
