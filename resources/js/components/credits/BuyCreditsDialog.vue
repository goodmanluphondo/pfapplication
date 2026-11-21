<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { LoaderCircle, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatUsd } from '@/lib/generations';
import { purchase } from '@/routes/credits';

const isOpen = defineModel<boolean>('open', { default: false });

const page = usePage();
const credits = computed(() => page.props.credits);
const packDollars = computed(() => credits.value?.pack_dollars ?? []);
const isBuying = ref(false);
const errorMessage = ref('');

function buy(dollars: number): void {
    isBuying.value = true;
    errorMessage.value = '';

    router.post(
        purchase.url(),
        { dollars },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isOpen.value = false;
            },
            onError: (errors) => {
                errorMessage.value =
                    errors.dollars ?? 'We could not add credits. Try again.';
            },
            onFinish: () => {
                isBuying.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            :show-close-button="false"
            class="flex w-[min(420px,calc(100%-2rem))] flex-col gap-5 rounded-[28px] border-[#dadce0] bg-white p-7 text-[#202124] shadow-[0_8px_28px_rgba(60,64,67,0.2)] sm:max-w-[420px] dark:border-[#5f6368] dark:bg-[#303134] dark:text-[#e8eaed]"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="grid gap-1">
                    <DialogTitle
                        class="text-xl leading-7 font-medium tracking-tight text-[#202124] dark:text-[#f8f9fa]"
                    >
                        Buy credits
                    </DialogTitle>
                    <DialogDescription
                        class="text-[13px] leading-[18px] text-[#5f6368] dark:text-[#bdc1c6]"
                    >
                        This is a demo purchase. No payment is taken.
                    </DialogDescription>
                </div>
                <DialogClose
                    aria-label="Close"
                    class="flex size-9 shrink-0 items-center justify-center rounded-full text-[#5f6368] transition hover:bg-[#f1f3f4] dark:text-[#bdc1c6] dark:hover:bg-[#3c4043]"
                >
                    <X class="size-[18px]" aria-hidden="true" />
                </DialogClose>
            </div>

            <p class="text-sm leading-5 text-[#5f6368] dark:text-[#bdc1c6]">
                Current balance
                <span class="font-medium text-[#202124] dark:text-[#e8eaed]">
                    {{ formatUsd(credits?.balance_usd ?? '0') }}
                </span>
            </p>

            <div class="grid gap-2">
                <button
                    v-for="dollars in packDollars"
                    :key="dollars"
                    type="button"
                    class="flex items-center justify-between rounded-2xl border border-[#dadce0] px-4 py-3 text-left text-sm font-medium transition hover:bg-[#f8f9fa] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#5f6368] dark:hover:bg-[#3c4043]"
                    :disabled="isBuying"
                    @click="buy(dollars)"
                >
                    <span>Add ${{ dollars }}.00</span>
                    <LoaderCircle
                        v-if="isBuying"
                        aria-hidden="true"
                        class="size-4 animate-spin"
                    />
                </button>
            </div>

            <p
                v-if="errorMessage"
                role="alert"
                class="text-sm leading-5 text-destructive-foreground"
            >
                {{ errorMessage }}
            </p>
        </DialogContent>
    </Dialog>
</template>
