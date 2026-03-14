<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Bell, Cake, CircleCheck } from 'lucide-vue-next';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type HeaderNotification, type NotificationKind, useHeaderNotifications } from '@/composables/useHeaderNotifications';
import { useAuthUser } from '@/composables/useAuthUser';
import { useAvatarSrc } from '@/composables/useAvatarSrc';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
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
const user = useAuthUser();
const { getInitials } = useInitials();
const avatarSrc = useAvatarSrc(() => {
    const u = user.value as Record<string, unknown> | null;
    return (u?.avatar_url ?? u?.avatar) as string | null;
});

const currentTitle = computed(() => {
    if (!props.breadcrumbs.length) {
        return 'Dashboard';
    }

    return props.breadcrumbs[props.breadcrumbs.length - 1].title;
});

const { notifications, unreadNotificationCount, markNotificationAsRead } = useHeaderNotifications();

const onNotificationClick = (event: MouseEvent, notification: HeaderNotification) => {
    markNotificationAsRead(notification.id);
    if (!notification.href) {
        event.preventDefault();
    }
};

const notificationIconByKind = (kind: NotificationKind) => {
    if (kind === 'leave') return CircleCheck;
    if (kind === 'birthday') return Cake;

    return Bell;
};
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

            <div class="flex items-center gap-2">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-border/80 bg-transparent text-muted-foreground transition-colors hover:bg-muted/60"
                            aria-label="Open notifications"
                        >
                            <Bell class="h-5 w-5" />
                            <span
                                v-if="unreadNotificationCount > 0"
                                class="absolute -right-1 -top-1 min-w-[1.1rem] rounded-full bg-destructive px-1 text-center text-[10px] font-semibold leading-[1.1rem] text-destructive-foreground"
                            >
                                {{ unreadNotificationCount > 9 ? '9+' : unreadNotificationCount }}
                            </span>
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-80">
                        <DropdownMenuLabel class="flex items-center justify-between">
                            <span>Notifications</span>
                            <span class="text-xs text-muted-foreground">
                                {{ unreadNotificationCount }} unread
                            </span>
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <div
                            v-if="notifications.length"
                            class="max-h-80 space-y-1 overflow-y-auto px-1 py-1"
                        >
                            <Link
                                v-for="notification in notifications"
                                :key="notification.id"
                                :href="notification.href ?? '#'"
                                class="flex items-start gap-3 rounded-md px-2 py-2 hover:bg-muted"
                                @click="onNotificationClick($event, notification)"
                            >
                                <component
                                    :is="notificationIconByKind(notification.kind)"
                                    class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-foreground">
                                        {{ notification.title }}
                                    </p>
                                    <p
                                        v-if="notification.description"
                                        class="line-clamp-2 text-xs text-muted-foreground"
                                    >
                                        {{ notification.description }}
                                    </p>
                                </div>
                                <span
                                    v-if="!notification.read"
                                    class="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary"
                                />
                            </Link>
                        </div>
                        <p v-else class="px-3 py-4 text-sm text-muted-foreground">
                            No notifications yet.
                        </p>
                    </DropdownMenuContent>
                </DropdownMenu>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="ehris-user-chip bg-transparent hover:bg-muted/60 transition-colors"
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
                                <p class="ehris-user-name text-left">{{ user.name }}</p>
                                <p class="ehris-user-role text-left">{{ user.email }}</p>
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
                            <Link href="/settings/password" class="block">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-center rounded-md border border-input bg-background px-3 py-2 text-sm font-medium hover:bg-muted"
                                >
                                    Change password
                                </button>
                            </Link>
                            <div class="grid grid-cols-2 gap-3">
                                <Link href="/my-details?section=official-info" class="min-w-0">
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
                                    class="flex min-w-0 items-center justify-center rounded-md border border-destructive/40 bg-destructive px-3 py-2 text-sm font-medium text-destructive-foreground hover:bg-destructive/90"
                                    @click="router.flushAll()"
                                >
                                    Sign out
                                </Link>
                            </div>
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div v-if="breadcrumbs && breadcrumbs.length > 0" class="ehris-header-bottom">
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </div>
    </header>
</template>
