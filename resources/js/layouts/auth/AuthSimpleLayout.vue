<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoLockup from '@/components/AppLogoLockup.vue';
import { home } from '@/routes';

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
        <div class="flex w-full max-w-[380px] flex-col gap-8">
            <div class="flex flex-col items-center gap-5 text-center">
                <Link
                    :href="home()"
                    :aria-label="`${page.props.name} home`"
                    class="rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#5f6368]"
                >
                    <AppLogoLockup size="lg" />
                </Link>

                <header class="grid gap-2">
                    <h1
                        class="text-2xl leading-8 font-medium tracking-tight text-[#202124] dark:text-[#f8f9fa]"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="text-sm leading-5 text-pretty text-[#5f6368] dark:text-[#bdc1c6]"
                    >
                        {{ description }}
                    </p>
                </header>
            </div>

            <div class="auth-controls">
                <slot />
            </div>
        </div>
    </main>
</template>

<style scoped>
.auth-controls :deep([data-slot='label']) {
    font-size: 13px;
    line-height: 18px;
    color: #3c4043;
}

.dark .auth-controls :deep([data-slot='label']) {
    color: #e8eaed;
}

.auth-controls :deep([data-slot='input']) {
    height: 2.75rem;
    border-color: #dadce0;
    border-radius: 14px;
    background-color: #fff;
    padding-left: 14px;
    box-shadow: none;
}

.auth-controls :deep([data-slot='input']:focus-visible) {
    border-color: #5f6368;
    box-shadow: 0 0 0 3px rgba(95, 99, 104, 0.15);
}

.dark .auth-controls :deep([data-slot='input']) {
    border-color: #5f6368;
    background-color: #303134;
}

.auth-controls
    :deep(
        [data-slot='button']:not([data-size='icon']):not(
                [data-size='icon-sm']
            ):not([data-size='icon-lg'])
    ) {
    height: 2.75rem;
    border-radius: 9999px;
}

.auth-controls :deep([data-slot='button'][data-variant='outline']) {
    border-color: #dadce0;
    background-color: transparent;
    box-shadow: none;
}

.auth-controls
    :deep([data-slot='button'][data-variant='outline']:hover:not(:disabled)) {
    background-color: #f8f9fa;
}

.dark .auth-controls :deep([data-slot='button'][data-variant='outline']) {
    border-color: #5f6368;
}

.dark
    .auth-controls
    :deep([data-slot='button'][data-variant='outline']:hover:not(:disabled)) {
    background-color: #3c4043;
}

.auth-controls :deep([data-slot='button'][type='submit']) {
    background-color: #202124;
    color: #fff;
}

.auth-controls :deep([data-slot='button'][type='submit']:hover:not(:disabled)) {
    background-color: #3c4043;
}

.dark .auth-controls :deep([data-slot='button'][type='submit']) {
    background-color: #e8eaed;
    color: #202124;
}

.dark
    .auth-controls
    :deep([data-slot='button'][type='submit']:hover:not(:disabled)) {
    background-color: #fff;
}
</style>
