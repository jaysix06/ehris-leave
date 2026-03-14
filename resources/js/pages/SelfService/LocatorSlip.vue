<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { SendHorizontal } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { toast } from 'vue3-toastify';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';

type EmployeeProfile = {
    name: string;
    position: string;
    station: string;
};

const pageTitle = 'Self-Service - Locator Slip';

const props = defineProps<{
    employeeProfile: EmployeeProfile;
    filingDate: string;
}>();

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

const page = usePage();
const errors = computed(
    () => (page.props.errors as Record<string, string> | undefined) ?? {},
);
const submitting = ref(false);

const purposeOfTravel = ref('');
const travelType = ref<'official_business' | 'official_time'>(
    'official_business',
);
const travelDate = ref('');
const timeOut = ref('');
const timeIn = ref('');
const destination = ref('');

const formattedFilingDate = computed(() => {
    const parsed = new Date(`${props.filingDate}T00:00:00`);
    if (Number.isNaN(parsed.getTime())) {
        return props.filingDate;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(parsed);
});

function clearForm(): void {
    purposeOfTravel.value = '';
    travelType.value = 'official_business';
    travelDate.value = '';
    timeOut.value = '';
    timeIn.value = '';
    destination.value = '';
}

function submitLocatorSlip(): void {
    submitting.value = true;

    router.post(
        selfServiceRoutes.locatorSlip.store().url,
        {
            purpose_of_travel: purposeOfTravel.value,
            travel_type: travelType.value,
            travel_date: travelDate.value,
            time_out: timeOut.value || null,
            time_in: timeIn.value || null,
            destination: destination.value,
        },
        {
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
            class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8"
        >
            <section
                class="rounded-3xl border border-slate-300 bg-white p-4 shadow-sm sm:p-6"
            >
                <div
                    class="mx-auto max-w-5xl overflow-hidden rounded-2xl border border-slate-300 bg-white"
                >
                    <div
                        class="grid grid-cols-1 border-b border-slate-200 lg:grid-cols-[220px_1fr]"
                    >
                        <div
                            class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900"
                        >
                            Date of Filing
                        </div>
                        <div class="px-4 py-3">
                            <Input
                                :model-value="formattedFilingDate"
                                readonly
                                class="h-11 border-slate-200 bg-slate-50 text-base font-medium text-slate-900"
                            />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 border-b border-slate-200 lg:grid-cols-[220px_1fr]"
                    >
                        <div
                            class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900"
                        >
                            Name
                        </div>
                        <div class="px-4 py-3">
                            <Input
                                :model-value="props.employeeProfile.name"
                                readonly
                                class="h-11 border-slate-200 bg-slate-50 text-base font-medium text-slate-900"
                            />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 border-b border-slate-200 lg:grid-cols-[220px_1fr]"
                    >
                        <div
                            class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900"
                        >
                            Position / Designation
                        </div>
                        <div class="px-4 py-3">
                            <Input
                                :model-value="props.employeeProfile.position"
                                readonly
                                class="h-11 border-slate-200 bg-slate-50 text-base font-medium text-slate-900"
                            />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 border-b border-slate-200 lg:grid-cols-[220px_1fr]"
                    >
                        <div
                            class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900"
                        >
                            Permanent Station
                        </div>
                        <div class="px-4 py-3">
                            <Input
                                :model-value="props.employeeProfile.station"
                                readonly
                                class="h-11 border-slate-200 bg-slate-50 text-base font-medium text-slate-900"
                            />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 border-b border-slate-200 lg:grid-cols-[220px_1fr]"
                    >
                        <div
                            class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900"
                        >
                            Purpose of Travel
                        </div>
                        <div class="px-4 py-3">
                            <textarea
                                v-model="purposeOfTravel"
                                rows="3"
                                class="w-full rounded-md border border-slate-200 px-3 py-2 text-base text-slate-900 outline-none placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100"
                                placeholder="Enter the purpose of travel"
                            />
                            <p
                                v-if="errors.purpose_of_travel"
                                class="mt-2 text-sm text-rose-600"
                            >
                                {{ errors.purpose_of_travel }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 border-b border-slate-200 lg:grid-cols-[220px_1fr]"
                    >
                        <div
                            class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900"
                        >
                            Please Check
                        </div>
                        <div class="px-4 py-3">
                            <div
                                class="flex flex-wrap gap-6 text-sm text-slate-900"
                            >
                                <label class="inline-flex items-center gap-3">
                                    <input
                                        v-model="travelType"
                                        type="radio"
                                        value="official_business"
                                        class="h-4 w-4 border-slate-400 text-slate-900 focus:ring-slate-400"
                                    />
                                    Official Business
                                </label>
                                <label class="inline-flex items-center gap-3">
                                    <input
                                        v-model="travelType"
                                        type="radio"
                                        value="official_time"
                                        class="h-4 w-4 border-slate-400 text-slate-900 focus:ring-slate-400"
                                    />
                                    Official Time
                                </label>
                            </div>
                            <p
                                v-if="errors.travel_type"
                                class="mt-2 text-sm text-rose-600"
                            >
                                {{ errors.travel_type }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 border-b border-slate-200 lg:grid-cols-[220px_1fr]"
                    >
                        <div
                            class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900"
                        >
                            Date and Time
                        </div>
                        <div
                            class="grid gap-4 px-4 py-3 lg:grid-cols-[1.2fr_1fr_1fr]"
                        >
                            <label
                                class="grid gap-2 text-sm font-medium text-slate-700"
                            >
                                <span class="text-slate-500">Travel Date</span>
                                <Input
                                    v-model="travelDate"
                                    type="date"
                                    class="h-11 border-slate-200 bg-white text-base text-slate-900"
                                />
                                <span
                                    v-if="errors.travel_date"
                                    class="text-sm text-rose-600"
                                    >{{ errors.travel_date }}</span
                                >
                            </label>
                            <label
                                class="grid gap-2 text-sm font-medium text-slate-700"
                            >
                                <span class="text-slate-500">Time Out</span>
                                <Input
                                    v-model="timeOut"
                                    type="time"
                                    class="h-11 border-slate-200 bg-white text-base text-slate-900"
                                />
                                <span
                                    v-if="errors.time_out"
                                    class="text-sm text-rose-600"
                                    >{{ errors.time_out }}</span
                                >
                            </label>
                            <label
                                class="grid gap-2 text-sm font-medium text-slate-700"
                            >
                                <span class="text-slate-500">Time In</span>
                                <Input
                                    v-model="timeIn"
                                    type="time"
                                    class="h-11 border-slate-200 bg-white text-base text-slate-900"
                                />
                                <span
                                    v-if="errors.time_in"
                                    class="text-sm text-rose-600"
                                    >{{ errors.time_in }}</span
                                >
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-[220px_1fr]">
                        <div
                            class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900"
                        >
                            Destination
                        </div>
                        <div class="px-4 py-3">
                            <Input
                                v-model="destination"
                                type="text"
                                placeholder="Enter destination"
                                class="h-11 border-slate-200 bg-white text-base text-slate-900"
                            />
                            <p
                                v-if="errors.destination"
                                class="mt-2 text-sm text-rose-600"
                            >
                                {{ errors.destination }}
                            </p>
                        </div>
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
                        {{
                            submitting ? 'Submitting...' : 'Submit Locator Slip'
                        }}
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
