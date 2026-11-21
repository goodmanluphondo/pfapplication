<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { home } from "@/routes";

const page = usePage();

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <main
        class="flex min-h-screen flex-col items-center justify-center bg-white px-5 py-10 text-[#202124] dark:bg-[#202124] dark:text-[#e8eaed]"
    >
        <div
            class="w-full max-w-md rounded-[28px] border border-[#dadce0] bg-white shadow-sm dark:border-[#5f6368] dark:bg-[#303134]"
        >
            <div class="px-6 pt-8 text-center sm:px-10">
                <Link
                    :href="home()"
                    class="inline-flex rounded-full text-lg font-semibold tracking-tight text-[#202124] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#5f6368] dark:text-[#f8f9fa]"
                >
                    {{ page.props.name }}
                </Link>
            </div>

            <header class="mt-5 space-y-2 px-6 text-center sm:px-10">
                <h1 class="text-xl font-medium tracking-tight text-[#202124] dark:text-[#f8f9fa]">
                    {{ title }}
                </h1>
                <p class="text-sm leading-5 text-[#5f6368] dark:text-[#bdc1c6]">
                    {{ description }}
                </p>
            </header>

            <div class="auth-controls px-6 py-8 sm:px-10">
                <slot />
            </div>
        </div>
    </main>
</template>

<style scoped>
.auth-controls :deep([data-slot="input"]) {
    border-color: #dadce0;
    border-radius: 1rem;
    background-color: #fff;
}

.dark .auth-controls :deep([data-slot="input"]) {
    border-color: #5f6368;
    background-color: #303134;
}

.auth-controls
    :deep(
        [data-slot="button"]:not([data-size="icon"]):not([data-size="icon-sm"]):not(
                [data-size="icon-lg"]
            )
    ) {
    border-radius: 9999px;
}

.auth-controls :deep([data-slot="button"][type="submit"]) {
    background-color: #202124;
    color: #fff;
}

.auth-controls :deep([data-slot="button"][type="submit"]:hover:not(:disabled)) {
    background-color: #3c4043;
}

.dark .auth-controls :deep([data-slot="button"][type="submit"]) {
    background-color: #e8eaed;
    color: #202124;
}

.dark .auth-controls :deep([data-slot="button"][type="submit"]:hover:not(:disabled)) {
    background-color: #fff;
}
</style>
