<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CircleAlert, ImageOff, LoaderCircle, RotateCcw, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import GenerationStatusBadge from '@/components/generations/GenerationStatusBadge.vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    aspectRatio,
    chargedLabel,
    formatTimestamp,
    formatTokens,
    formatUsd,
    isPending,
    qualityLabel,
    sizeDimensions,
    sizeLabel,
} from '@/lib/generations';
import { retry } from '@/routes/generations';
import type { Generation } from '@/types/generation';

const { generation } = defineProps<{
    generation: Generation | null;
}>();

const emit = defineEmits<{
    close: [];
    retried: [generationId: number];
}>();

const failureMessages: Record<string, string> = {
    internal_error:
        'Something went wrong on our side while generating. Retrying usually works.',
    generation_failed: "The model couldn't produce an image for this prompt.",
};

const isRetrying = ref(false);
const retryError = ref('');
const hasImageFailedToLoad = ref(false);

watch(
    () => generation?.id,
    () => {
        retryError.value = '';
        hasImageFailedToLoad.value = false;
    },
);

const isOpen = computed({
    get: () => generation !== null,
    set: (open: boolean) => {
        if (!open) {
            emit('close');
        }
    },
});

const showsImage = computed(
    () => !!generation?.image_url && !hasImageFailedToLoad.value,
);

const referencesText = computed(() => {
    const count = generation?.reference_image_count ?? 0;

    return count ? `${count} image${count > 1 ? 's' : ''}` : 'None';
});

const tokenRows = computed(() => {
    if (!generation) {
        return [];
    }

    const estimated = generation.estimated_tokens;
    const usage = generation.usage;

    return [
        {
            label: 'Prompt',
            estimated: `~${formatTokens(estimated.input_text.estimated)}`,
            actual: usage ? formatTokens(usage.input_text_tokens) : '—',
        },
        {
            label: 'Reference images',
            estimated: formatTokens(estimated.input_image),
            actual: usage ? formatTokens(usage.input_image_tokens) : '—',
        },
        {
            label: 'Image output',
            estimated: formatTokens(estimated.output),
            actual: usage ? formatTokens(usage.output_tokens) : '—',
        },
        {
            label: 'Reasoning',
            estimated: `${formatTokens(estimated.reasoning.minimum)}–${formatTokens(estimated.reasoning.maximum)}`,
            actual: usage ? formatTokens(usage.reasoning_tokens) : '—',
        },
    ];
});

function retryGeneration(): void {
    if (!generation) {
        return;
    }

    isRetrying.value = true;
    retryError.value = '';

    router.post(
        retry.url(generation.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFlash: (flash) => {
                const retriedGenerationId = Number(flash.retriedGenerationId);

                if (retriedGenerationId) {
                    emit('retried', retriedGenerationId);
                }
            },
            onError: (errors) => {
                retryError.value =
                    errors.prompt ??
                    "We couldn't start this image. Try again in a moment.";
            },
            onFinish: () => {
                isRetrying.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent
            v-if="generation"
            :show-close-button="false"
            class="grid max-h-[calc(100vh-2rem)] w-[min(920px,calc(100%-2rem))] grid-cols-[repeat(auto-fit,minmax(300px,1fr))] gap-0 overflow-y-auto rounded-[28px] border-[#dadce0] bg-white p-0 text-[#202124] shadow-[0_8px_28px_rgba(60,64,67,0.2)] sm:max-w-[920px] dark:border-[#5f6368] dark:bg-[#303134] dark:text-[#e8eaed]"
        >
            <div class="flex items-center justify-center p-4">
                <div
                    class="relative flex max-h-[70vh] w-full items-center justify-center overflow-hidden rounded-[20px] bg-[#f1f3f4] text-[#5f6368] dark:bg-[#3c4043] dark:text-[#bdc1c6]"
                    :style="{ aspectRatio: aspectRatio(generation.size) }"
                >
                    <img
                        v-if="showsImage"
                        :src="generation.image_url!"
                        :alt="generation.prompt"
                        class="absolute inset-0 size-full object-contain"
                        @error="hasImageFailedToLoad = true"
                    />
                    <span
                        v-else-if="isPending(generation)"
                        class="flex flex-col items-center gap-2.5 text-[13px]"
                    >
                        <LoaderCircle
                            aria-hidden="true"
                            class="size-7 animate-spin"
                        />
                        <span>{{
                            generation.status === 'queued'
                                ? 'Waiting in queue'
                                : 'Generating'
                        }}</span>
                    </span>
                    <span
                        v-else-if="generation.status === 'failed'"
                        class="flex flex-col items-center gap-2.5 text-[13px]"
                    >
                        <CircleAlert
                            aria-hidden="true"
                            class="size-7"
                            :stroke-width="1.5"
                        />
                        <span>No image</span>
                    </span>
                    <span
                        v-else
                        class="flex flex-col items-center gap-2.5 px-6 text-center text-[13px]"
                    >
                        <ImageOff
                            aria-hidden="true"
                            class="size-7"
                            :stroke-width="1.5"
                        />
                        <span
                            >The image link has expired. Pixelforge keeps images
                            for 24 hours.</span
                        >
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-5 px-6 pt-3 pb-6 min-[700px]:pt-6">
                <div class="flex items-center justify-between gap-3">
                    <GenerationStatusBadge :generation="generation" />
                    <DialogClose
                        aria-label="Close"
                        class="flex size-9 items-center justify-center rounded-full text-[#5f6368] transition hover:bg-[#f1f3f4] dark:text-[#bdc1c6] dark:hover:bg-[#3c4043]"
                    >
                        <X aria-hidden="true" class="size-[18px]" />
                    </DialogClose>
                </div>

                <DialogTitle
                    class="text-base leading-6 font-normal text-pretty"
                >
                    {{ generation.prompt }}
                </DialogTitle>
                <DialogDescription class="sr-only"
                    >Generation details</DialogDescription
                >

                <dl
                    class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-1.5 text-[13px] leading-[18px]"
                >
                    <dt class="text-[#5f6368] dark:text-[#bdc1c6]">Size</dt>
                    <dd>
                        {{ sizeLabel(generation.size) }} ·
                        {{ sizeDimensions(generation.size) }}
                    </dd>
                    <dt class="text-[#5f6368] dark:text-[#bdc1c6]">Quality</dt>
                    <dd>{{ qualityLabel(generation.quality) }}</dd>
                    <dt class="text-[#5f6368] dark:text-[#bdc1c6]">
                        References
                    </dt>
                    <dd>{{ referencesText }}</dd>
                    <dt class="text-[#5f6368] dark:text-[#bdc1c6]">Created</dt>
                    <dd>{{ formatTimestamp(generation.created_at) }}</dd>
                    <template v-if="generation.completed_at">
                        <dt class="text-[#5f6368] dark:text-[#bdc1c6]">
                            Finished
                        </dt>
                        <dd>{{ formatTimestamp(generation.completed_at) }}</dd>
                    </template>
                </dl>

                <div
                    v-if="generation.status === 'failed'"
                    class="flex flex-col items-start gap-3 rounded-2xl border border-[#fee2e2] bg-[#fef2f2] px-4 py-3.5 dark:border-[rgba(254,202,202,.1)] dark:bg-[rgba(185,28,28,.1)]"
                >
                    <div
                        class="grid gap-0.5 text-[#dc2626] dark:text-[#fee2e2]"
                    >
                        <span class="text-sm leading-5 font-medium"
                            >Generation failed</span
                        >
                        <span class="text-[13px] leading-[18px]">
                            {{
                                failureMessages[generation.error_code ?? ''] ??
                                generation.error_message
                            }}
                        </span>
                        <span class="text-[13px] leading-[18px] font-medium"
                            >You were not charged.</span
                        >
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full bg-[#202124] px-4 py-2 text-[13px] font-medium text-white transition hover:bg-[#3c4043] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#e8eaed] dark:text-[#202124] dark:hover:bg-white"
                        :disabled="isRetrying"
                        @click="retryGeneration"
                    >
                        <LoaderCircle
                            v-if="isRetrying"
                            aria-hidden="true"
                            class="size-3.5 animate-spin"
                        />
                        <RotateCcw v-else aria-hidden="true" class="size-3.5" />
                        <span
                            >Retry · ~{{
                                formatUsd(generation.estimated_cost_usd)
                            }}</span
                        >
                    </button>
                    <p
                        v-if="retryError"
                        role="alert"
                        class="text-[13px] leading-[18px] text-[#dc2626] dark:text-[#fee2e2]"
                    >
                        {{ retryError }}
                    </p>
                </div>

                <div
                    class="grid gap-3 rounded-2xl border border-[#dadce0] p-4 dark:border-[#5f6368]"
                >
                    <div
                        class="flex items-baseline justify-between gap-3 text-sm leading-5"
                    >
                        <span>Estimated</span>
                        <span class="flex flex-col items-end whitespace-nowrap">
                            <span
                                >~{{
                                    formatUsd(generation.estimated_cost_usd)
                                }}</span
                            >
                            <span
                                class="text-xs leading-4 text-[#5f6368] dark:text-[#bdc1c6]"
                            >
                                {{
                                    formatUsd(
                                        generation.estimated_minimum_cost_usd,
                                    )
                                }}–{{
                                    formatUsd(
                                        generation.estimated_maximum_cost_usd,
                                    )
                                }}
                            </span>
                        </span>
                    </div>
                    <div
                        class="flex items-baseline justify-between gap-3 text-sm leading-5 font-medium"
                    >
                        <span>Charged</span>
                        <span>{{ chargedLabel(generation) }}</span>
                    </div>
                    <div class="h-px bg-[#e8eaed] dark:bg-[#5f6368]" />
                    <div
                        class="grid grid-cols-[1fr_auto_auto] gap-x-5 gap-y-1.5 text-xs leading-4"
                    >
                        <span class="text-[#5f6368] dark:text-[#bdc1c6]"
                            >Tokens</span
                        >
                        <span
                            class="text-right text-[#5f6368] dark:text-[#bdc1c6]"
                            >Estimated</span
                        >
                        <span
                            class="text-right text-[#5f6368] dark:text-[#bdc1c6]"
                            >Actual</span
                        >
                        <template v-for="row in tokenRows" :key="row.label">
                            <span>{{ row.label }}</span>
                            <span class="text-right">{{ row.estimated }}</span>
                            <span class="text-right">{{ row.actual }}</span>
                        </template>
                    </div>
                    <p
                        class="text-xs leading-4 text-[#5f6368] dark:text-[#bdc1c6]"
                    >
                        Includes a 25% service fee on Pixelforge's token prices.
                    </p>
                </div>

                <p
                    v-if="
                        generation.image_url && generation.image_url_expires_at
                    "
                    class="text-xs leading-4 text-[#5f6368] dark:text-[#bdc1c6]"
                >
                    Image link expires
                    {{ formatTimestamp(generation.image_url_expires_at) }}.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
