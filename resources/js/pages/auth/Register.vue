<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
    },
});
</script>

<template>
    <Head title="Register" />

    <div class="flex flex-col gap-8">
        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
            <div class="grid grid-cols-2 gap-3">
                <div class="grid gap-1.5">
                    <Label for="first_name">First name</Label>
                    <Input
                        id="first_name"
                        type="text"
                        required
                        v-focus
                        :tabindex="1"
                        autocomplete="given-name"
                        name="first_name"
                    />
                    <InputError :message="errors.first_name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="last_name">Last name</Label>
                    <Input
                        id="last_name"
                        type="text"
                        required
                        :tabindex="2"
                        autocomplete="family-name"
                        name="last_name"
                    />
                    <InputError :message="errors.last_name" />
                </div>
            </div>

            <div class="grid gap-1.5">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="3"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password"
                    placeholder="At least 8 characters"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </Form>

        <p
            class="text-center text-sm leading-5 text-[#5f6368] dark:text-[#bdc1c6]"
        >
            Already have an account?
            <Link
                :href="login()"
                class="font-medium text-[#202124] hover:underline dark:text-[#e8eaed]"
                :tabindex="6"
            >
                Log in
            </Link>
        </p>
    </div>
</template>
