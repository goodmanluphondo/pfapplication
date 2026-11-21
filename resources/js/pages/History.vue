<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import GenerationDetailsDialog from '@/components/generations/GenerationDetailsDialog.vue';
import GenerationStatusBadge from '@/components/generations/GenerationStatusBadge.vue';
import GenerationThumbnail from '@/components/generations/GenerationThumbnail.vue';
import { useGenerationPolling } from '@/composables/useGenerationPolling';
import { chargedLabel, formatUsd, generationMeta } from '@/lib/generations';
import { home } from '@/routes';
import type { Generation, GenerationSummary } from '@/types/generation';

const props = defineProps<{
    generations: Generation[];
    summary: GenerationSummary;
}>();

const selectedGenerationId = ref<number | null>(null);
const selectedGeneration = computed(
    () =>
        props.generations.find(
            (generation) => generation.id === selectedGenerationId.value,
        ) ?? null,
);

useGenerationPolling(() => props.generations, ['generations', 'summary']);

const summaryCards = computed(() => [
    {
        label: 'Total spent',
        value: formatUsd(props.summary.spent_usd),
        detail: '',
    },
    {
        label: 'Images generated',
        value: String(props.summary.succeeded_count),
        detail: '',
    },
    {
        label: 'In progress',
        value: String(props.summary.pending_count),
        detail: props.summary.pending_count
            ? `~${formatUsd(props.summary.pending_estimated_usd)} estimated`
            : '',
    },
]);
</script>

<template>
    <Head title="History" />

    <main
        class="relative flex min-h-screen flex-col bg-white text-[#202124] dark:bg-[#202124] dark:text-[#e8eaed]"
    >
        <AppHeader />

        <section class="flex flex-1 flex-col px-5 py-24">
            <div class="mx-auto flex w-full max-w-4xl flex-col gap-8">
                <header class="flex flex-col gap-2">
                    <h1
                        class="text-[clamp(1.25rem,3vw,1.75rem)] leading-tight font-medium tracking-tight text-[#202124] dark:text-[#f8f9fa]"
                    >
                        History
                    </h1>
                    <p class="text-sm text-[#5f6368] dark:text-[#bdc1c6]">
                        Your generations and what each one cost.
                    </p>
                </header>

                <div
                    class="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-3"
                >
                    <div
                        v-for="card in summaryCards"
                        :key="card.label"
                        class="grid gap-1 rounded-[20px] border border-[#dadce0] px-5 py-4 dark:border-[#5f6368]"
                    >
                        <span
                            class="text-[13px] leading-[18px] text-[#5f6368] dark:text-[#bdc1c6]"
                        >
                            {{ card.label }}
                        </span>
                        <span
                            class="text-[22px] leading-7 font-medium tracking-[-0.01em]"
                        >
                            {{ card.value }}
                            <span
                                v-if="card.detail"
                                class="ml-2 text-[13px] leading-[18px] font-normal tracking-normal text-[#5f6368] dark:text-[#bdc1c6]"
                            >
                                {{ card.detail }}
                            </span>
                        </span>
                    </div>
                </div>

                <div
                    v-if="generations.length"
                    class="overflow-x-auto rounded-[20px] border border-[#dadce0] dark:border-[#5f6368]"
                >
                    <div class="min-w-[560px]">
                        <div
                            class="flex items-center gap-4 border-b border-[#dadce0] px-5 py-2.5 text-xs leading-4 text-[#5f6368] dark:border-[#5f6368] dark:text-[#bdc1c6]"
                        >
                            <span class="w-14 shrink-0" />
                            <span class="flex-1">Prompt</span>
                            <span class="w-24 shrink-0">Status</span>
                            <span class="w-22 shrink-0 text-right"
                                >Estimated</span
                            >
                            <span class="w-22 shrink-0 text-right"
                                >Charged</span
                            >
                        </div>
                        <button
                            v-for="generation in generations"
                            :key="generation.id"
                            type="button"
                            class="flex w-full items-center gap-4 border-b border-[#dadce0] px-5 py-3 text-left transition last:border-b-0 hover:bg-[#f8f9fa] focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-[#5f6368] dark:border-[#5f6368] dark:hover:bg-[#3c4043]"
                            @click="selectedGenerationId = generation.id"
                        >
                            <GenerationThumbnail
                                :generation="generation"
                                size="md"
                            />
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
                            <span class="w-24 shrink-0">
                                <GenerationStatusBadge
                                    :generation="generation"
                                />
                            </span>
                            <span
                                class="w-22 shrink-0 text-right text-sm leading-5 text-[#5f6368] dark:text-[#bdc1c6]"
                            >
                                ~{{ formatUsd(generation.estimated_cost_usd) }}
                            </span>
                            <span
                                class="w-22 shrink-0 text-right text-sm leading-5 font-medium"
                            >
                                {{ chargedLabel(generation) }}
                            </span>
                        </button>
                    </div>
                </div>

                <div
                    v-else
                    class="rounded-[20px] border border-[#dadce0] px-6 py-12 text-center dark:border-[#5f6368]"
                >
                    <p class="font-medium">No generations yet</p>
                    <p
                        class="mt-1 mb-4 text-sm leading-5 text-[#5f6368] dark:text-[#bdc1c6]"
                    >
                        Images you generate will appear here.
                    </p>
                    <Link
                        :href="home()"
                        class="inline-flex rounded-full bg-[#202124] px-5 py-2.5 text-[13px] font-medium text-white transition hover:bg-[#3c4043] dark:bg-[#e8eaed] dark:text-[#202124] dark:hover:bg-white"
                    >
                        Generate an image
                    </Link>
                </div>
            </div>
        </section>

        <AppFooter />

        <GenerationDetailsDialog
            :generation="selectedGeneration"
            @close="selectedGenerationId = null"
            @retried="
                (retriedGenerationId) =>
                    (selectedGenerationId = retriedGenerationId)
            "
        />
    </main>
</template>
