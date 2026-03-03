<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    FileText,
    Megaphone,
    Shield,
} from 'lucide-vue-next';
import { login, register } from '@/routes';

const announcements = [
    {
        title: 'HRIS Scheduled Maintenance on Saturday',
        date: 'February 28, 2026',
    },
    {
        title: 'Submission of Updated Employee Information',
        date: 'February 22, 2026',
    },
    {
        title: 'Payroll Cut-off Reminder for Month End',
        date: 'February 20, 2026',
    },
    {
        title: 'Orientation for New HRIS Users',
        date: 'February 15, 2026',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-background text-foreground">
        <header class="sticky top-0 z-30 border-b border-border/80 bg-card/95 backdrop-blur">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center">
                    <img
                        src="/logo-sximo.png"
                        alt="DepEd Ozamiz City seal"
                        class="size-10 shrink-0 rounded-full object-cover"
                    />
                    <div class="hidden min-w-0 sm:block">
                        <p class="truncate text-sm font-bold leading-tight text-foreground">
                            DepEd Ozamiz City
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            Division Unit School&ensp;|&ensp;HRIS Portal
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="register()"
                        class="hidden rounded-md px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background sm:inline-flex"
                    >
                        Register
                    </Link>
                    <Link
                        :href="login()"
                        class="inline-flex shrink-0 items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition hover:opacity-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                    >
                        Log in
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden border-b border-border bg-card">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-10%,hsl(227_62%_44%/0.12),transparent)]" />
                <div class="pointer-events-none absolute -right-24 -top-24 size-96 rounded-full bg-primary/5 blur-3xl" />
                <div class="pointer-events-none absolute -bottom-32 -left-32 size-80 rounded-full bg-primary/5 blur-3xl" />

                <div class="relative mx-auto flex w-full max-w-6xl flex-col items-center gap-8 px-4 py-16 text-center sm:px-6 sm:py-20 lg:px-8 lg:py-24">
                    <img
                        src="/ehris.png"
                        alt="DepEd eHRIS"
                        class="h-50 object-contain sm:h-60"
                    />

                    <div class="max-w-2xl space-y-4">
                        <p class="mx-auto max-w-xl text-base leading-relaxed text-muted-foreground sm:text-lg">
                            A modern HR platform for employee records and leave management.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-4">
                    
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-6 py-3 text-sm font-semibold text-foreground shadow-sm transition hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                        >
                            Create Account
                        </Link>

                        <Link
                            :href="login()"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-md transition hover:opacity-95 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                        >
                            Access HRIS Portal
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>
                </div>
            </section>

            <section class="border-t border-border bg-card/50">
                <div class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16" aria-labelledby="announcements-title">
                    <div class="mb-8 flex items-start gap-3">
                        <div class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Megaphone class="size-5" />
                        </div>
                        <div>
                            <h2 id="announcements-title" class="text-2xl font-bold tracking-tight sm:text-3xl">
                                Announcements
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Latest advisory notices from the HR office.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <article
                            v-for="item in announcements"
                            :key="item.title"
                            class="flex items-start gap-4 rounded-xl border border-border bg-card p-5 shadow-sm transition hover:border-primary/30"
                        >
                            <div class="mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                <FileText class="size-4" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold leading-snug text-foreground">
                                    {{ item.title }}
                                </p>
                                <time class="mt-1 block text-xs text-muted-foreground">
                                    {{ item.date }}
                                </time>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-border bg-card">
            <div class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <div class="grid gap-8 text-sm lg:grid-cols-4">
                    <div class="lg:col-span-1">
                        <div class="flex items-center gap-2.5">
                            <img
                                src="/logo-sximo.png"
                                alt="DepEd Ozamiz City seal"
                                class="size-9 shrink-0 rounded-full"
                            />
                            <div>
                                <p class="font-bold leading-tight text-foreground">DepEd Ozamiz City</p>
                                <p class="text-xs text-muted-foreground">HRIS Portal</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="mb-2 flex items-center gap-2 font-semibold text-foreground">
                            <Shield class="size-4 text-primary" />
                            Data Privacy
                        </h2>
                        <p class="text-muted-foreground">
                            This system processes personal data in compliance with RA 10173 (Data Privacy Act of 2012).
                        </p>
                    </div>

                    <div>
                        <h2 class="mb-2 flex items-center gap-2 font-semibold text-foreground">
                            <FileText class="size-4 text-primary" />
                            Terms of Use
                        </h2>
                        <p class="text-muted-foreground">
                            Use of this portal is limited to authorized personnel and official government transactions.
                        </p>
                    </div>

                    <div>
                        <h2 class="mb-2 font-semibold text-foreground">Contact</h2>
                        <p class="text-muted-foreground">
                            HR Office: (088) 000-0000
                        </p>
                        <p class="text-muted-foreground">
                            Email: hris.support@gov.ph
                        </p>
                    </div>
                </div>

                <div class="mt-8 border-t border-border pt-6 text-center text-xs text-muted-foreground">
                    &copy; {{ new Date().getFullYear() }} Department of Education &mdash; Ozamiz City Division. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>
