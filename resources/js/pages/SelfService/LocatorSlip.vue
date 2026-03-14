<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { FilePlus2, Paperclip, SendHorizontal } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue3-toastify';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import requestStatusRoutes from '@/routes/request-status';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';

const pageTitle = 'Self-Service - Locator Slip';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Self-Service',
        href: selfServiceRoutes.wfhTimeInOut().url,
    },
    {
        title: 'Locator Slip',
        href: selfServiceRoutes.locatorSlip().url,
    },
];

const purpose = ref('');
const reason = ref('');
const attachment = ref<File | null>(null);
const attachmentInput = ref<HTMLInputElement | null>(null);
const submitting = ref(false);
const page = usePage();

const errors = computed(
    () => (page.props.errors as Record<string, string> | undefined) ?? {},
);

function openAttachmentPicker(): void {
    attachmentInput.value?.click();
}

function onAttachmentChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    attachment.value = target.files?.[0] ?? null;
}

function clearForm(): void {
    purpose.value = '';
    reason.value = '';
    clearAttachment();
}

function clearAttachment(): void {
    attachment.value = null;
    if (attachmentInput.value) {
        attachmentInput.value.value = '';
    }
}

function submitLocatorSlip(): void {
    submitting.value = true;

    router.post(
        selfServiceRoutes.locatorSlipStore().url,
        {
            purpose: purpose.value,
            reason: reason.value,
            attachment: attachment.value,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Locator slip request submitted successfully.');
                clearForm();
            },
            onError: (formErrors) => {
                const firstError = Object.values(formErrors)[0];
                if (firstError) {
                    toast.error(firstError);
                }
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="mx-auto flex w-full max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8"
        >
            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-linear-to-br from-white via-slate-50 to-sky-50 shadow-sm"
            >
                <div
                    class="flex flex-col gap-4 px-6 py-7 lg:flex-row lg:items-end lg:justify-between"
                >
                    <div class="max-w-3xl">
                        <p
                            class="text-xs font-semibold tracking-[0.28em] text-sky-700 uppercase"
                        >
                            Self-Service
                        </p>
                        <h1
                            class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl"
                        >
                            Request Locator Slip
                        </h1>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Submit a locator slip request here. Once sent, it
                            will appear in
                            <a
                                :href="requestStatusRoutes.myRequests().url"
                                class="font-semibold text-sky-700 hover:underline"
                                >My Requests</a
                            >
                            so you can track its status.
                        </p>
                    </div>

                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white px-4 py-2 text-sm font-medium text-slate-700"
                    >
                        <FilePlus2 class="h-4 w-4 text-sky-700" />
                        Locator Slip Workflow
                    </div>
                </div>
            </section>

            <section
                class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div class="grid gap-5">
                    <label
                        class="grid gap-2 text-sm font-medium text-slate-700"
                    >
                        Purpose
                        <Input
                            v-model="purpose"
                            type="text"
                            placeholder="Enter the purpose of your request"
                            class="border-slate-300 bg-white text-slate-900"
                        />
                        <span
                            v-if="errors.purpose"
                            class="text-xs text-rose-600"
                            >{{ errors.purpose }}</span
                        >
                    </label>

                    <label
                        class="grid gap-2 text-sm font-medium text-slate-700"
                    >
                        Reason
                        <textarea
                            v-model="reason"
                            rows="5"
                            placeholder="Explain why you are requesting a locator slip"
                            class="min-h-32 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-xs transition outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
                        />
                        <span
                            v-if="errors.reason"
                            class="text-xs text-rose-600"
                            >{{ errors.reason }}</span
                        >
                    </label>

                    <div class="grid gap-2">
                        <span class="text-sm font-medium text-slate-700"
                            >Attachment</span
                        >
                        <div
                            class="flex flex-col gap-3 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-sky-700 shadow-sm"
                                >
                                    <Paperclip class="h-5 w-5" />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-medium text-slate-800"
                                    >
                                        {{
                                            attachment
                                                ? attachment.name
                                                : 'No file selected'
                                        }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Optional: PDF, JPG, JPEG, or PNG up to
                                        10 MB.
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                                    @click="openAttachmentPicker"
                                >
                                    Choose file
                                </button>
                                <button
                                    v-if="attachment"
                                    type="button"
                                    class="rounded-md border border-rose-200 px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-50"
                                    @click="clearAttachment"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                        <input
                            ref="attachmentInput"
                            type="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="hidden"
                            @change="onAttachmentChange"
                        />
                        <span
                            v-if="errors.attachment"
                            class="text-xs text-rose-600"
                            >{{ errors.attachment }}</span
                        >
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                        :disabled="submitting"
                        @click="clearForm"
                    >
                        Reset
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="submitting"
                        @click="submitLocatorSlip"
                    >
                        <SendHorizontal class="h-4 w-4" />
                        {{ submitting ? 'Submitting...' : 'Submit Request' }}
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
