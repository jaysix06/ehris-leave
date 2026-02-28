<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

const props = defineProps<{
    employmentStatuses?: string[];
    districts?: { id: number | string; name: string }[];
    stations?: { id: number | string; name: string }[];
}>();
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                </div>

                <div class="grid gap-2">
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

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="11"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" />
                    Create account
                </Button>
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
    </AuthBase>
</template>
