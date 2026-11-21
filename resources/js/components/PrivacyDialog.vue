<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { X } from '@lucide/vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';

const page = usePage();
const open = defineModel<boolean>('open', { default: false });

const sections = [
    {
        title: 'Your account',
        body: 'Your name, email address, a hashed password and any passkeys you add. We use these to sign you in.',
    },
    {
        title: 'Your generations',
        body: 'Prompts, settings, reference image URLs and what each generation cost you. These are sent to Pixelforge to create your images and kept here so you can see your history.',
    },
    {
        title: 'Images',
        body: "Images are hosted by Pixelforge, and their links stop working 24 hours after they're made.",
    },
    {
        title: 'Cookies and storage',
        body: 'A session cookie keeps you signed in, and a cookie remembers light or dark mode. Unsent prompts are saved in your browser until you close the tab.',
    },
    {
        title: 'Deleting your data',
        body: 'Deleting your account from your profile removes your account and generation history.',
    },
];
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            :show-close-button="false"
            class="flex max-h-[calc(100vh-2rem)] w-[min(520px,calc(100%-2rem))] flex-col gap-5 overflow-y-auto rounded-[28px] border-[#dadce0] bg-white p-7 text-[#202124] shadow-[0_8px_28px_rgba(60,64,67,0.2)] sm:max-w-[520px] dark:border-[#5f6368] dark:bg-[#303134] dark:text-[#e8eaed]"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="grid gap-1">
                    <DialogTitle
                        class="text-xl leading-7 font-medium tracking-tight text-[#202124] dark:text-[#f8f9fa]"
                    >
                        Privacy notice
                    </DialogTitle>
                    <DialogDescription
                        class="text-[13px] leading-[18px] text-[#5f6368] dark:text-[#bdc1c6]"
                    >
                        What {{ page.props.name }} keeps, and why.
                    </DialogDescription>
                </div>
                <DialogClose
                    aria-label="Close"
                    class="flex size-9 shrink-0 items-center justify-center rounded-full text-[#5f6368] transition hover:bg-[#f1f3f4] dark:text-[#bdc1c6] dark:hover:bg-[#3c4043]"
                >
                    <X class="size-[18px]" aria-hidden="true" />
                </DialogClose>
            </div>

            <div class="grid gap-4 text-sm leading-5">
                <div
                    v-for="section in sections"
                    :key="section.title"
                    class="grid gap-0.5"
                >
                    <span class="font-medium">{{ section.title }}</span>
                    <span class="text-[#5f6368] dark:text-[#bdc1c6]">{{
                        section.body
                    }}</span>
                </div>
            </div>

            <div class="flex justify-end">
                <DialogClose
                    class="inline-flex items-center justify-center rounded-full bg-[#202124] px-5 py-2.5 text-[13px] font-medium text-white transition hover:bg-[#3c4043] dark:bg-[#e8eaed] dark:text-[#202124] dark:hover:bg-white"
                >
                    Got it
                </DialogClose>
            </div>
        </DialogContent>
    </Dialog>
</template>
