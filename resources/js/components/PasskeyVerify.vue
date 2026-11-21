<script setup lang="ts">
import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { usePasskeyVerify } from '@laravel/passkeys/vue';
import { KeyRound } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    routes?: {
        options: UrlMethodPair;
        submit: UrlMethodPair;
    };
    label?: string;
    loadingLabel?: string;
    separator?: string;
    separatorPlacement?: 'before' | 'after';
};

const props = defineProps<Props>();

const { verify, isLoading, error, isSupported } = usePasskeyVerify({
    ...(props.routes
        ? {
              routes: {
                  options: props.routes.options.url,
                  submit: props.routes.submit.url,
              },
          }
        : {}),
    onSuccess: (response) => {
        router.visit(response.redirect ?? '/');
    },
});
</script>

<template>
    <div v-if="isSupported">
        <div
            v-if="props.separatorPlacement === 'before'"
            class="mb-4 flex items-center gap-3 text-xs leading-4 text-[#70757a] dark:text-[#9aa0a6]"
        >
            <span class="h-px flex-1 bg-[#dadce0] dark:bg-[#5f6368]" />
            {{ props.separator ?? 'or' }}
            <span class="h-px flex-1 bg-[#dadce0] dark:bg-[#5f6368]" />
        </div>

        <div class="grid gap-2">
            <Button
                type="button"
                variant="outline"
                class="w-full"
                @click="verify"
                :disabled="isLoading"
            >
                <Spinner v-if="isLoading" />
                <KeyRound v-else class="h-4 w-4" />
                {{
                    isLoading
                        ? (props.loadingLabel ?? 'Authenticating...')
                        : (props.label ?? 'Sign in with a passkey')
                }}
            </Button>

            <div v-if="error" class="text-center">
                <InputError :message="error" />
            </div>
        </div>

        <div v-if="props.separatorPlacement !== 'before'" class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <Separator class="w-full" />
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-background px-2 text-muted-foreground">
                    {{ props.separator ?? 'Or continue with email' }}
                </span>
            </div>
        </div>
    </div>
</template>
