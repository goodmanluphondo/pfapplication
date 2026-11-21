<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Choose a new password for your account.',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Reset password" />

    <div class="flex flex-col gap-8">
        <Form
            v-bind="update.form()"
            :transform="(data) => ({ ...data, token, email })"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
            <div class="grid gap-1.5">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    v-model="inputEmail"
                    readonly
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <Label for="password">New password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    autofocus
                    placeholder="At least 8 characters"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :disabled="processing"
                data-test="reset-password-button"
            >
                <Spinner v-if="processing" />
                Reset password
            </Button>
        </Form>

        <p
            class="text-center text-sm leading-5 text-[#5f6368] dark:text-[#bdc1c6]"
        >
            Or, return to
            <Link
                :href="login()"
                class="font-medium text-[#202124] hover:underline dark:text-[#e8eaed]"
                >log in</Link
            >
        </p>
    </div>
</template>
