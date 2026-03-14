<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, FileCheck2 } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';

type RequestRow = {
    id: number;
    type: string;
    purpose: string;
    reason: string;
    status: string;
    remarks: string;
    attachment: string | null;
    running_year: string;
    submitted_at: string | null;
};

const pageTitle = 'Request Status - My Requests';

const props = withDefaults(
    defineProps<{
        requests?: RequestRow[];
        statusMessage?: string | null;
    }>(),
    {
        requests: () => [],
        statusMessage: null,
    },
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: pageTitle,
    },
];

const sortedRequests = computed(() => [...props.requests]);

const statusClasses: Record<string, string> = {
    'On Process': 'bg-amber-100 text-amber-800',
    Done: 'bg-emerald-100 text-emerald-800',
    Approved: 'bg-emerald-100 text-emerald-800',
    Disapproved: 'bg-rose-100 text-rose-800',
    Cancelled: 'bg-slate-200 text-slate-700',
};

function requestStatusClass(status: string): string {
    return statusClasses[status] ?? 'bg-sky-100 text-sky-800';
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8"
        >
            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-linear-to-br from-white via-slate-50 to-cyan-50 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 px-6 py-7 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-3xl">
                        <p
                            class="text-xs font-semibold tracking-[0.28em] text-cyan-700 uppercase"
                        >
                            Request Status
                        </p>
                        <h1
                            class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl"
                        >
                            My Requests
                        </h1>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Review the status of your submitted requests,
                            including locator slip requests filed from
                            Self-Service.
                        </p>
                    </div>

                    <Link
                        :href="selfServiceRoutes.locatorSlip().url"
                        class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
                    >
                        <FileCheck2 class="h-4 w-4" />
                        New Locator Slip
                    </Link>
                </div>
            </section>

            <section
                v-if="props.statusMessage"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm"
            >
                {{ props.statusMessage }}
            </section>

            <section
                v-if="sortedRequests.length === 0"
                class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500"
                >
                    <ClipboardList class="h-7 w-7" />
                </div>
                <h2 class="mt-4 text-xl font-semibold text-slate-900">
                    No requests yet
                </h2>
                <p
                    class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-600"
                >
                    Once you submit a locator slip or other tracked request, it
                    will appear here.
                </p>
            </section>

            <section v-else class="grid gap-4">
                <article
                    v-for="request in sortedRequests"
                    :key="request.id"
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div
                        class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2
                                    class="text-lg font-semibold text-slate-900"
                                >
                                    {{ request.type || 'Request' }}
                                </h2>
                                <span
                                    :class="[
                                        'rounded-full px-3 py-1 text-xs font-semibold',
                                        requestStatusClass(request.status),
                                    ]"
                                >
                                    {{ request.status || 'Pending' }}
                                </span>
                            </div>

                            <div
                                class="grid gap-3 text-sm text-slate-600 sm:grid-cols-2"
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                    >
                                        Purpose
                                    </p>
                                    <p class="mt-1 text-slate-900">
                                        {{ request.purpose || 'Not provided' }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                    >
                                        Submitted
                                    </p>
                                    <p class="mt-1 text-slate-900">
                                        {{
                                            request.submitted_at ||
                                            'Not available'
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                    >
                                        Reason
                                    </p>
                                    <p class="mt-1 text-slate-900">
                                        {{ request.reason || 'Not provided' }}
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                                    >
                                        Running Year
                                    </p>
                                    <p class="mt-1 text-slate-900">
                                        {{ request.running_year || 'Not set' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="min-w-0 rounded-2xl bg-slate-50 px-4 py-3 lg:max-w-sm"
                        >
                            <p
                                class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                            >
                                Remarks
                            </p>
                            <p class="mt-2 text-sm leading-6 text-slate-700">
                                {{ request.remarks || 'No remarks yet.' }}
                            </p>
                            <p
                                v-if="request.attachment"
                                class="mt-3 text-xs text-slate-500"
                            >
                                Attachment saved: {{ request.attachment }}
                            </p>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AppLayout>
</template>
