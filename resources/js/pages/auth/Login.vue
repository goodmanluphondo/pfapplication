<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import AuthStatus from '@/components/AuthStatus.vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Log in to your account',
    },
});

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    demoCredentials: {
        email: string;
        password: string | null;
    };
}>();
</script>

<template>
    <Head title="Log in" />

    <div class="flex flex-col gap-8">
        <AuthStatus v-if="status" :message="status" />

        <div class="flex flex-col gap-4">
            <Form
                v-bind="store.form()"
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
                        :default-value="props.demoCredentials.email"
                        required
                        v-focus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="password">Password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        :default-value="props.demoCredentials.password ?? undefined"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Your password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div
                    class="flex items-center justify-between gap-3 text-[13px] leading-[18px]"
                >
                    <Label
                        for="remember"
                        class="flex cursor-pointer items-center gap-2 font-normal"
                    >
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Remember me</span>
                    </Label>
                    <Link
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-[#5f6368] hover:underline dark:text-[#bdc1c6]"
                        :tabindex="5"
                    >
                        Forgot password?
                    </Link>
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Log in
                </Button>
            </Form>

            <PasskeyVerify separator="or" separator-placement="before" />
        </div>

        <p
            class="text-center text-sm leading-5 text-[#5f6368] dark:text-[#bdc1c6]"
        >
            Don't have an account?
            <Link
                :href="register()"
                class="font-medium text-[#202124] hover:underline dark:text-[#e8eaed]"
                :tabindex="6"
            >
                Sign up
            </Link>
        </p>
    </div>
</template>
