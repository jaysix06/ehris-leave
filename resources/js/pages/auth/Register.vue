<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
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
    employmentStatuses?: string[];
    districts?: { id: number | string; name: string }[];
    stations?: { id: number | string; name: string }[];
}>();
</script>

<template>
    <AuthBase
        title=""
        description=""
        content-class="max-w-4xl"
    >
        <Head title="Register" />

        <div class="rounded-lg border border-border bg-card p-6 shadow-sm">
            <div class="mb-6 flex flex-col gap-4">
                <Link
                    :href="home()"
                    class="inline-flex w-fit items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 19-7-7 7-7"/>
                        <path d="M19 12H5"/>
                    </svg>
                    Back to home
                </Link>
                <div class="flex flex-col items-center gap-1 text-center">
                    <img
                        src="/logo.png"
                        alt="Logo"
                        class="h-12 w-auto object-contain sm:h-14"
                    />
                    <h1 class="text-xl font-medium">Register</h1>
                    <p class="text-sm text-muted-foreground">
                        Enter your details below to create your account
                    </p>
                </div>
            </div>

            <Form
                v-bind="store.form()"
                :reset-on-success="['password', 'password_confirmation']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-6"
            >
                <div class="grid gap-6 md:grid-cols-3">
                <div class="grid gap-2">
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
                <div class="grid gap-2">
                    <Label for="lastname">Last name</Label>
                    <Input
                        id="lastname"
                        type="text"
                        required
                        :tabindex="2"
                        autocomplete="family-name"
                        name="lastname"
                        placeholder="Last name"
                    />
                    <InputError :message="errors.lastname" />
                </div>
                <div class="grid gap-2">
                    <Label for="middlename">Middle name</Label>
                    <Input
                        id="middlename"
                        type="text"
                        :tabindex="3"
                        autocomplete="additional-name"
                        name="middlename"
                        placeholder="Middle name"
                    />
                    <InputError :message="errors.middlename" />
                </div>

                <div class="grid gap-2">
                    <Label for="extname">Name extension (Jr., Sr.)</Label>
                    <Input
                        id="extname"
                        type="text"
                        :tabindex="4"
                        name="extname"
                        placeholder="e.g. Jr., Sr."
                    />
                    <InputError :message="errors.extname" />
                </div>
                <div class="grid gap-2 md:col-span-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="5"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="employment_status">Employment status</Label>
                    <select
                        id="employment_status"
                        name="employment_status"
                        required
                        :tabindex="6"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">Select status</option>
                        <option
                            v-for="status in (props.employmentStatuses ?? [])"
                            :key="status"
                            :value="status"
                        >
                            {{ status }}
                        </option>
                    </select>
                    <InputError :message="errors.employment_status" />
                </div>
                <div class="grid gap-2">
                    <Label for="district">District</Label>
                    <select
                        id="district"
                        name="district"
                        required
                        :tabindex="7"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
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
                <div class="grid gap-2">
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
                            v-for="s in (props.stations ?? [])"
                            :key="String(s.id)"
                            :value="String(s.id)"
                        >
                            {{ s.name }}
                        </option>
                    </select>
                    <InputError :message="errors.station" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="9"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>
                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="10"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>
                <div class="flex justify-center md:col-span-3">
                    <Button
                        type="submit"
                        size="sm"
                        class="mt-2 w-fit"
                        tabindex="11"
                        :disabled="processing"
                        data-test="register-user-button"
                    >
                        <Spinner v-if="processing" />
                        Register
                    </Button>
                </div>
            </div>

                <div class="text-center text-sm text-muted-foreground">
                    Already have an account?
                    <TextLink
                        :href="login()"
                        class="underline underline-offset-4"
                        :tabindex="12"
                        >Log in</TextLink
                    >
                </div>
            </Form>
        </div>
    </AuthBase>
</template>
