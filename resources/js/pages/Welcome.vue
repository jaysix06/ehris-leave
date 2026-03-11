<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    FileText,
    Megaphone,
    MessageSquareHeart,
    QrCode,
    Shield,
} from 'lucide-vue-next';
import { login, register } from '@/routes';
import { useSessionTrap } from '@/composables/useSessionTrap';
import { computed } from 'vue';

type AnnouncementLink = {
    label: string;
    url: string;
};

type AnnouncementItem = {
    id: number;
    title: string;
    content: string | null;
    links: AnnouncementLink[] | null;
    created_at: string;
};

const page = usePage();
const announcements = computed(() => (page.props.announcements ?? []) as AnnouncementItem[]);

const formatDate = (dateString: string): string => {
    const date = new Date(dateString);

    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

useSessionTrap({
    mode: 'guest',
    redirectIfAuthenticated: '/dashboard',
});
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-background text-foreground">

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
                            Login
                            <ArrowRight class="size-4" />
                        </Link>
                    </div>
                </div>
            </section>

            <section class="border-t border-border bg-card/50">
                <div class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
                    <div class="grid gap-10 lg:grid-cols-2 lg:items-start">
                        <div aria-labelledby="announcements-title" class="lg:flex lg:min-h-[32rem] lg:flex-col">
                            <div class="mb-6 flex items-start gap-3">
                                <div class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <Megaphone class="size-5" />
                                </div>
                                <div>
                                    <h2 id="announcements-title" class="text-2xl font-bold tracking-tight sm:text-3xl">
                                        Announcements
                                    </h2>
                                    <p class="mt-1 text-sm text-muted-foreground">
                                        Latest advisory notices.
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-4 lg:max-h-[24rem] lg:overflow-y-auto lg:pr-2">
                                <article
                                    v-for="item in announcements"
                                    :key="item.id"
                                    class="flex items-start gap-4 rounded-xl border border-border bg-card p-5 shadow-sm transition hover:border-primary/30"
                                >
                                    <div class="mt-0.5 inline-flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                                        <FileText class="size-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold leading-snug text-foreground">
                                            {{ item.title }}
                                        </p>
                                        <p v-if="item.content" class="mt-1 whitespace-pre-line text-xs text-muted-foreground">
                                            {{ item.content }}
                                        </p>
                                        <ul v-if="item.links && item.links.length > 0" class="mt-2 space-y-1 text-xs">
                                            <li v-for="(link, index) in item.links" :key="`${item.id}-${index}`">
                                                <a :href="link.url" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">
                                                    {{ link.label || link.url }}
                                                </a>
                                            </li>
                                        </ul>
                                        <time class="mt-1 block text-xs text-muted-foreground">
                                            {{ formatDate(item.created_at) }}
                                        </time>
                                    </div>
                                </article>
                                <article
                                    v-if="announcements.length === 0"
                                    class="rounded-xl border border-dashed border-border bg-card p-5 text-sm text-muted-foreground"
                                >
                                    No announcements available right now.
                                </article>
                            </div>
                        </div>

                        <div aria-labelledby="feedback-title">
                            <div class="mb-6 flex items-start gap-3">
                                <div class="mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                    <MessageSquareHeart class="size-5" />
                                </div>
                                <div>
                                    <h2 id="feedback-title" class="text-2xl font-bold tracking-tight sm:text-3xl">
                                        Customer Satisfaction Measurement
                                    </h2>
                                    <p class="mt-1 text-sm text-muted-foreground">
                                        Scan the QR code to share your feedback.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col items-center rounded-xl border border-border bg-card p-8 shadow-sm">
                                <div class="flex size-48 items-center justify-center rounded-lg border-2 border-dashed border-border bg-muted/50">
                                    <a href="https://ozamiz.deped.gov.ph/csm/" target="_blank">
                                        <img src="/csm.jpg" alt="QR Code" class="size-48 text-muted-foreground/60" />
                                    </a>
                                </div>
                                <p class="mt-4 text-center text-sm text-muted-foreground">
                                    Division of Ozamiz City Client Satisfaction Measurement (CSM)
                                </p>
                            </div>
                        </div>
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
                                <p class="text-xs text-muted-foreground">eHRIS Portal</p>
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
                            Contact No.: 088-545-0998 | 088-545-0990
                        </p>
                        <p class="text-muted-foreground">
                            Email: ozamiz.city@deped.gov.ph
                        </p> 
                        <a href="https://www.facebook.com/depedtayoozamiz" target="_blank" class="text-muted-foreground">
                            Facebook Page: www.fb.com/depedtayoozamiz
                        </a>
                    </div>
                </div>

                <div class="mt-8 border-t border-border pt-6 text-center text-xs text-muted-foreground">
                    &copy; {{ new Date().getFullYear() }} Department of Education &mdash; Ozamiz City Division. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>
