<script setup lang="ts">
import { Head, usePage, Link } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useAvatarSrc } from '@/composables/useAvatarSrc';
import { useInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const user = page.props.auth.user as any;
const { getInitials } = useInitials();
const avatarSrc = useAvatarSrc(() => user?.avatar);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'My profile',
        href: '/my-profile',
    },
];

const fullName = (() => {
    if (user?.fullname) return user.fullname;
    const parts = [
        user?.firstname,
        user?.middlename,
        user?.lastname,
        user?.extname,
    ]
        .filter((p) => typeof p === 'string' && p.trim() !== '')
        .join(' ');
    if (parts) return parts;
    if (user?.name) return user.name;
    return user?.email ?? '';
})();
</script>

<template>
    <Head title="My profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="grid gap-6 lg:grid-cols-[320px,1fr]">
                <!-- Left profile card -->
                <section
                    class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
                >
                    <div
                        class="bg-muted px-8 pt-10 pb-10 text-center text-foreground"
                    >
                        <Avatar
                            class="mx-auto mb-4 h-28 w-28 overflow-hidden rounded-full border-4 border-white/90 bg-white"
                        >
                            <AvatarImage
                                v-if="avatarSrc"
                                :src="avatarSrc"
                                :alt="fullName"
                            />
                            <AvatarFallback
                                class="text-2xl font-semibold text-primary"
                            >
                                {{ getInitials(fullName || user?.name || '') }}
                            </AvatarFallback>
                        </Avatar>

                        <h2 class="text-2xl font-semibold">
                            {{ fullName }}
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ user?.email }}
                        </p>
                    </div>

                    <div class="space-y-4 bg-card px-6 py-5">
                        <div class="grid gap-1 text-sm">
                            <p class="text-muted-foreground">HR ID</p>
                            <p class="font-medium">
                                {{ user?.hrId ?? user?.hrid ?? '—' }}
                            </p>
                        </div>

                        <div class="grid gap-1 text-sm">
                            <p class="text-muted-foreground">Role</p>
                            <p class="font-medium">
                                {{ user?.role ?? '—' }}
                            </p>
                        </div>

                        <div class="grid gap-1 text-sm">
                            <p class="text-muted-foreground">Department ID</p>
                            <p class="font-medium">
                                {{ user?.department_id ?? '—' }}
                            </p>
                        </div>

                        <div class="grid gap-2 pt-4">
                            <Link href="/settings/profile">
                                <Button
                                    class="w-full"
                                    variant="outline"
                                    size="sm"
                                >
                                    Edit profile
                                </Button>
                            </Link>
                            <Link href="/settings/password">
                                <Button
                                    class="w-full"
                                    variant="outline"
                                    size="sm"
                                >
                                    Change password
                                </Button>
                            </Link>
                        </div>
                    </div>
                </section>

                <!-- Right details card -->
                <section
                    class="rounded-2xl border border-border bg-card p-6 shadow-sm"
                >
                    <h2 class="text-lg font-semibold text-foreground">
                        User profile
                    </h2>
                    <p class="mt-1 mb-5 text-sm text-muted-foreground">
                        Basic information about your account. Most fields are
                        read-only and synced from HRIS.
                    </p>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1 text-sm">
                            <p class="text-muted-foreground">Email</p>
                            <p
                                class="rounded-md border border-border bg-muted/40 px-3 py-2 font-medium break-all"
                            >
                                {{ user?.email ?? '—' }}
                            </p>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="text-muted-foreground">Username</p>
                            <p
                                class="rounded-md border border-border bg-muted/40 px-3 py-2 font-medium"
                            >
                                {{ user?.username ?? user?.email ?? '—' }}
                            </p>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="text-muted-foreground">HR ID</p>
                            <p
                                class="rounded-md border border-border bg-muted/40 px-3 py-2 font-medium"
                            >
                                {{ user?.hrId ?? user?.hrid ?? '—' }}
                            </p>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="text-muted-foreground">Department ID</p>
                            <p
                                class="rounded-md border border-border bg-muted/40 px-3 py-2 font-medium"
                            >
                                {{ user?.department_id ?? '—' }}
                            </p>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="text-muted-foreground">Role</p>
                            <p
                                class="rounded-md border border-border bg-muted/40 px-3 py-2 font-medium"
                            >
                                {{ user?.role ?? '—' }}
                            </p>
                        </div>
                        <div class="space-y-1 text-sm">
                            <p class="text-muted-foreground">Date created</p>
                            <p
                                class="rounded-md border border-border bg-muted/40 px-3 py-2 font-medium"
                            >
                                {{ user?.date_created ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-6 rounded-lg border border-dashed border-border/60 bg-muted/30 px-4 py-3 text-xs text-muted-foreground"
                    >
                        To update official information like office, employment
                        status, and HR details, please use the
                        <span class="font-medium">My Details</span> section or
                        contact the HR administrator.
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
