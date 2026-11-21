<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrivacyDialog from '@/components/PrivacyDialog.vue';

const page = usePage();
const currentYear = new Date().getFullYear();
const isPrivacyNoticeOpen = ref(false);
</script>

<template>
    <footer
        class="absolute inset-x-0 bottom-0 flex min-h-14 items-center justify-between gap-4 px-6 py-3 text-xs text-[#5f6368] dark:text-[#bdc1c6]"
    >
        <p>
            Copyright &copy; {{ currentYear }} {{ page.props.name }}. All rights
            reserved.
        </p>
        <button
            class="shrink-0 hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368]"
            type="button"
            @click="isPrivacyNoticeOpen = true"
        >
            Privacy
        </button>
        <PrivacyDialog v-model:open="isPrivacyNoticeOpen" />
    </footer>
</template>
