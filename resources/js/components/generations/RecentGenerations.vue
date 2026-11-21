<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import GenerationStatusBadge from '@/components/generations/GenerationStatusBadge.vue';
import GenerationThumbnail from '@/components/generations/GenerationThumbnail.vue';
import { costSummaryLabel, generationMeta } from '@/lib/generations';
import { index as history } from '@/routes/generations';
import type { Generation } from '@/types/generation';

defineProps<{
    generations: Generation[];
}>();

const emit = defineEmits<{
    open: [generation: Generation];
}>();
</script>

<template>
    <div class="grid w-full max-w-[584px] gap-2">
        <div class="flex items-center justify-between px-1">
            <h2 class="text-sm leading-5 font-medium">Recent</h2>
            <Link
                :href="history()"
                class="text-[13px] text-[#5f6368] hover:underline dark:text-[#bdc1c6]"
            >
                View all
            </Link>
        </div>
        <ul
            class="overflow-hidden rounded-[20px] border border-[#dadce0] bg-white dark:border-[#5f6368] dark:bg-[#303134]"
        >
            <li
                v-for="generation in generations"
                :key="generation.id"
                class="border-b border-[#dadce0] last:border-b-0 dark:border-[#5f6368]"
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-3.5 px-4 py-2.5 text-left transition hover:bg-[#f8f9fa] focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-[#5f6368] dark:hover:bg-[#3c4043]"
                    @click="emit('open', generation)"
                >
                    <GenerationThumbnail :generation="generation" />
                    <span class="grid min-w-0 flex-1 gap-0.5">
                        <span class="truncate text-sm leading-5">{{
                            generation.prompt
                        }}</span>
                        <span
                            class="text-xs leading-4 text-[#5f6368] dark:text-[#bdc1c6]"
                        >
                            {{ generationMeta(generation) }}
                        </span>
                    </span>
                    <GenerationStatusBadge :generation="generation" />
                    <span
                        class="w-16 shrink-0 text-right text-sm leading-5 font-medium"
                    >
                        {{ costSummaryLabel(generation) }}
                    </span>
                </button>
            </li>
        </ul>
    </div>
</template>
