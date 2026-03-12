<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { home, login } from '@/routes';
import { store } from '@/routes/register';

const props = defineProps<{
    roles?: string[];
    districts?: { id: number | string; name: string }[];
    stations?: { id: number | string; name: string; district_id?: number | string | null }[];
}>();

const page = usePage();
const step2ErrorKeys = ['role', 'district', 'station'];

const currentStep = ref(1);
const selectedDistrictId = ref<string>('');

// When we land back with validation errors, stay on step 2 if errors are for step-2 fields
watch(
    () => (page.props as { errors?: Record<string, string> }).errors,
    (errors) => {
        if (errors && Object.keys(errors).some((k) => step2ErrorKeys.includes(k))) {
            currentStep.value = 2;
        }
    },
    { immediate: true },
);

const filteredStations = computed(() => {
    const all = props.stations ?? [];
    if (!selectedDistrictId.value) return all;
    return all.filter(
        (s) => String(s.district_id ?? '') === selectedDistrictId.value,
    );
});

const handleDistrictChange = (event: Event) => {
    const target = event.target as HTMLSelectElement | null;
    const value = target?.value ?? '';
    selectedDistrictId.value = value;

    // Reset station selection and, when only one match, auto-select it.
    nextTick(() => {
        const stationSelect = document.getElementById('station') as HTMLSelectElement | null;
        if (!stationSelect) return;

        if (!selectedDistrictId.value) {
            stationSelect.value = '';
            return;
        }

        const options = filteredStations.value;
        stationSelect.value = '';
        if (options.length === 1) {
            stationSelect.value = String(options[0].id);
        }
    });
};
</script>

<template>
    <AuthBase
        title=""
        description=""
        content-class="max-w-md"
    >
        <Head title="Register" />

        <div class="rounded-2xl border bg-card p-6 shadow-sm sm:p-8">
            <div class="mb-6 overflow-hidden rounded-xl">
                <img
                    src="/ehris.png"
                    alt="DepEd Ozamiz Unit School Division"
                    class="h-36 w-full object-cover object-center"
                />
            </div>

            <div class="mb-6 flex flex-col gap-2 text-center">
                <h1 class="text-2xl font-semibold text-foreground">
                    Create your account
                </h1>
                <p class="text-sm text-muted-foreground">
                    Enter your information to register for eHRIS.
                </p>
            </div>

            <Form
                v-bind="store.form()"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-6"
            >
                <div class="flex items-center justify-center gap-2 rounded-full bg-muted/60 px-1 py-0.5 text-xs sm:text-sm">
                    <button
                        type="button"
                        class="flex-1 rounded-full px-3 py-1.5 font-medium transition"
                        :class="currentStep === 1 ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        @click="currentStep = 1"
                    >
                        Personal details
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-full px-3 py-1.5 font-medium transition"
                        :class="currentStep === 2 ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                        @click="currentStep = 2"
                    >
<<<<<<< HEAD
                        Employment details
=======
                        Employment Details
>>>>>>> januard
                    </button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div v-show="currentStep === 1" class="contents">
                        <div class="grid gap-2 md:col-span-1">
                            <Label for="firstname">First name</Label>
                            <Input
                                id="firstname"
                                type="text"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="given-name"
                                name="firstname"
                                placeholder="First name"
                            />
                            <InputError :message="errors.firstname" />
                        </div>
                        <div class="grid gap-2 md:col-span-1">
                            <Label for="middlename">Middle name</Label>
                            <Input
                                id="middlename"
                                type="text"
                                :tabindex="2"
                                autocomplete="additional-name"
                                name="middlename"
                                placeholder="Middle name"
                            />
                            <InputError :message="errors.middlename" />
                        </div>
                        <div class="grid gap-2 md:col-span-1">
                            <Label for="lastname">Last name</Label>
                            <Input
                                id="lastname"
                                type="text"
                                required
                                :tabindex="3"
                                autocomplete="family-name"
                                name="lastname"
                                placeholder="Last name"
                            />
                            <InputError :message="errors.lastname" />
                        </div>

                        <div class="grid gap-2 md:col-span-1">
                            <Label for="extname">Name extension (Jr., Sr.)</Label>
                            <select
                                id="extname"
                                name="extname"
                                :tabindex="4"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="">None</option>
                                <option value="Jr.">Jr.</option>
                                <option value="Sr.">Sr.</option>
                                <option value="II">II</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                            </select>
                            <InputError :message="errors.extname" />
                        </div>
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="email">Personal email</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                :tabindex="5"
                                autocomplete="email"
                                name="email"
                                placeholder="your.personal@example.com"
                            />
                            <InputError :message="errors.email" />
                            <p class="text-xs text-muted-foreground">
                                Your official DepEd login email will be provided after your account is activated.
                            </p>
                        </div>

                        <div class="md:col-span-2 flex justify-center">
                            <Button
                                type="button"
                                size="lg"
                                class="mt-4 w-full sm:w-auto px-8"
                                :tabindex="6"
                                @click="currentStep = 2"
                            >
                                Next
                            </Button>
                        </div>
                    </div>

                    <div v-show="currentStep === 2" class="contents">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="role">Role</Label>
                            <select
                                id="role"
                                name="role"
                                required
                                :tabindex="6"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="" disabled selected>Select role</option>
                                <option
                                    v-for="r in (props.roles ?? [])"
                                    :key="r"
                                    :value="r"
                                >
                                    {{ r }}
                                </option>
                            </select>
                            <InputError :message="errors.role" />
                        </div>
                        <div class="grid gap-2 md:col-span-1">
                            <Label for="district">District</Label>
                            <select
                                id="district"
                                name="district"
                                required
                                :tabindex="7"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                v-model="selectedDistrictId"
                                @change="handleDistrictChange"
                            >
                                <option value="">Select district</option>
                                <option
                                    v-for="d in (props.districts ?? [])"
                                    :key="String(d.id)"
                                    :value="String(d.id)"
                                >
                                    {{ d.name }}
                                </option>
                            </select>
                            <InputError :message="errors.district" />
                        </div>
                        <div class="grid gap-2 md:col-span-1">
                            <Label for="station">Office / School</Label>
                            <select
                                id="station"
                                name="station"
                                required
                                :tabindex="8"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            >
                                <option value="">Select office/school</option>
                                <option
                                    v-for="s in filteredStations"
                                    :key="String(s.id)"
                                    :value="String(s.id)"
                                >
                                    {{ s.name }}
                                </option>
                            </select>
                            <InputError :message="errors.station" />
                        </div>

                        <div class="md:col-span-2 flex justify-center gap-4">
                            <Button
                                type="button"
                                size="sm"
                                class="mt-2"
                                :tabindex="9"
                                variant="outline"
                                @click="currentStep = 1"
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                size="sm"
                                class="mt-2 px-8"
                                :tabindex="10"
                                :disabled="processing"
                                data-test="register-user-button"
                            >
                                <Spinner v-if="processing" />
                                Register
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="text-center text-sm text-muted-foreground">
                    Already have an account?
                    <TextLink
                        :href="login()"
                        class="underline underline-offset-4"
                        :tabindex="11"
                        >Log in</TextLink
                    >
                </div>
            </Form>
        </div>
    </AuthBase>
</template>
