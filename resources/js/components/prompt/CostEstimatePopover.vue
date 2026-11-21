<script setup lang="ts">
import { Info, LoaderCircle } from '@lucide/vue';
import { computed, ref } from 'vue';
import { formatTokens, formatUsd } from '@/lib/generations';
import type { PriceEstimate } from '@/types/generation';

const { estimate, isUnavailable, isCalculating } = defineProps<{
    estimate: PriceEstimate | null;
    isUnavailable: boolean;
    isCalculating: boolean;
}>();

const isPinnedOpen = defineModel<boolean>('open', { default: false });
const isHovered = ref(false);
const isOpen = computed(() => isPinnedOpen.value || isHovered.value);

const typicalCost = computed(() =>
    estimate ? `~${formatUsd(estimate.estimated_user_cost_usd)}` : '~$0.00',
);

const tokenRows = computed(() => {
    if (!estimate) {
        return [];
    }

    return [
        {
            label: 'Prompt',
            value: `~${formatTokens(estimate.tokens.input_text.estimated)} tokens`,
        },
        {
            label: 'Reference images',
            value: `${formatTokens(estimate.tokens.input_image)} tokens`,
        },
        {
            label: 'Image output',
            value: `${formatTokens(estimate.tokens.output)} tokens`,
        },
        {
            label: 'Reasoning',
            value: `${formatTokens(estimate.tokens.reasoning.minimum)}–${formatTokens(estimate.tokens.reasoning.maximum)} tokens`,
        },
    ];
});

function toggle(): void {
    isHovered.value = false;
    isPinnedOpen.value = !isPinnedOpen.value;
}
</script>

<template>
    <div
        class="relative"
        data-prompt-option
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
    >
        <button
            type="button"
            class="flex min-h-9 items-center gap-1.5 rounded-full px-2.5 text-sm text-[#5f6368] transition hover:bg-[#f8f9fa] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:text-[#bdc1c6] dark:hover:bg-[#3c4043]"
            aria-label="Estimated cost"
            aria-controls="cost-estimate-details"
            :aria-expanded="isOpen"
            @click="toggle"
        >
            <span aria-live="polite">{{ typicalCost }}</span>
            <LoaderCircle
                v-if="isCalculating && !estimate"
                aria-hidden="true"
                class="size-4 animate-spin"
            />
            <Info v-else aria-hidden="true" class="size-4" />
        </button>

        <div v-if="isOpen" class="absolute right-0 bottom-full z-20 pb-2">
            <div
                id="cost-estimate-details"
                role="tooltip"
                class="grid w-[300px] max-w-[calc(100vw-2rem)] gap-3 rounded-2xl border border-[#e0e0e0] bg-white p-4 text-[#202124] shadow-lg dark:border-[#5f6368] dark:bg-[#303134] dark:text-[#e8eaed]"
            >
                <p
                    v-if="isUnavailable && !estimate"
                    class="text-xs leading-4 text-[#5f6368] dark:text-[#bdc1c6]"
                >
                    Pricing is temporarily unavailable. Try again shortly.
                </p>
                <template v-else-if="estimate">
                    <div
                        class="flex items-baseline justify-between gap-3 text-sm font-medium"
                    >
                        <span>Estimated cost</span>
                        <span>{{ typicalCost }}</span>
                    </div>
                    <p
                        class="text-xs leading-4 text-[#5f6368] dark:text-[#bdc1c6]"
                    >
                        Usually between
                        {{ formatUsd(estimate.minimum_user_cost_usd) }} and
                        {{ formatUsd(estimate.maximum_user_cost_usd) }}. The
                        exact price is known once the image is ready.
                    </p>
                    <div class="h-px bg-[#e8eaed] dark:bg-[#5f6368]" />
                    <dl
                        class="grid grid-cols-[1fr_auto] gap-x-3 gap-y-1.5 text-[13px] leading-[18px]"
                    >
                        <template v-for="row in tokenRows" :key="row.label">
                            <dt>{{ row.label }}</dt>
                            <dd
                                class="text-right text-[#5f6368] dark:text-[#bdc1c6]"
                            >
                                {{ row.value }}
                            </dd>
                        </template>
                    </dl>
                    <div class="h-px bg-[#e8eaed] dark:bg-[#5f6368]" />
                    <p
                        class="text-xs leading-4 text-[#5f6368] dark:text-[#bdc1c6]"
                    >
                        Includes a 25% service fee. Failed generations are free.
                    </p>
                </template>
                <p
                    v-else
                    class="text-xs leading-4 text-[#5f6368] dark:text-[#bdc1c6]"
                >
                    Calculating estimate…
                </p>
            </div>
        </div>
    </div>
</template>
