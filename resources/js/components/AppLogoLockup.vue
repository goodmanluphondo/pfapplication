<script setup lang="ts">
const { size = 'md' } = defineProps<{
    size?: 'md' | 'lg';
}>();
</script>

<template>
    <span
        class="flex min-w-0 items-center text-[#202124] dark:text-[#f8f9fa]"
        :class="size === 'lg' ? 'gap-2.5' : 'gap-2'"
    >
        <svg
            :class="size === 'lg' ? 'size-10' : 'size-8'"
            class="shrink-0"
            viewBox="0 0 32 32"
            aria-hidden="true"
        >
            <rect
                width="32"
                height="32"
                rx="9"
                class="fill-[#202124] dark:fill-[#e8eaed]"
            />
            <g
                fill="none"
                stroke-width="2.8"
                stroke-linecap="round"
                class="stroke-white dark:stroke-[#202124]"
            >
                <path d="M8.5 12.2V25.6" />
                <circle cx="12.8" cy="16.5" r="4.3" />
                <path d="M21 20.8V12.6a3.2 3.2 0 0 1 3.2-3.2h.4M18.8 14.6h5" />
            </g>
            <rect
                x="24.6"
                y="17.6"
                width="3.4"
                height="3.4"
                rx="0.9"
                class="fill-[#8ab4f8] dark:fill-[#1a73e8]"
            />
        </svg>
        <span
            class="pb-0.5 font-[Quicksand] leading-none font-bold tracking-[-0.01em]"
            :class="
                size === 'lg' ? 'text-[26px]' : 'text-[21px] max-[419px]:hidden'
            "
        >
            application
        </span>
    </span>
</template>
