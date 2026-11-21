<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuthStatus from '@/components/AuthStatus.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Forgot password',
        description:
            'Enter your email and we’ll send you a link to reset your password.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot password" />

    <div class="flex flex-col gap-8">
        <AuthStatus v-if="status" :message="status" />

        <Form
            v-bind="email.form()"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-4"
        >
            <div class="grid gap-1.5">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    v-focus
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :disabled="processing"
                data-test="email-password-reset-link-button"
            >
                <Spinner v-if="processing" />
                Email password reset link
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
