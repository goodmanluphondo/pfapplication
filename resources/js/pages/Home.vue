<script setup lang="ts">
import { Head, router, useForm, useHttp, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppFooter from '@/components/AppFooter.vue';
import AppHeader from '@/components/AppHeader.vue';
import GenerationDetailsDialog from '@/components/generations/GenerationDetailsDialog.vue';
import RecentGenerations from '@/components/generations/RecentGenerations.vue';
import CostEstimatePopover from '@/components/prompt/CostEstimatePopover.vue';
import ReferenceImagesPopover from '@/components/prompt/ReferenceImagesPopover.vue';
import BuyCreditsDialog from '@/components/credits/BuyCreditsDialog.vue';
import { useGenerationPolling } from '@/composables/useGenerationPolling';
import {
    formatUsd,
    maximumReferenceImages,
    qualityOptions,
    sizeOptions,
} from '@/lib/generations';
import { login } from '@/routes';
import { store as storeGeneration } from '@/routes/generations';
import { estimate as estimatePromptRoute } from '@/routes/prompt';
import { usePromptDraftStore } from '@/stores/promptDraft';
import type { Generation, PriceEstimate } from '@/types/generation';

type PromptOption = 'size' | 'quality' | 'reference' | 'cost';

const maximumPromptLength = 4000;
const estimateDebounceMilliseconds = 350;

const props = defineProps<{
    recentGenerations: Generation[];
}>();

const page = usePage();
const promptDraft = usePromptDraftStore();
const form = useForm({
    prompt: '',
    size: '1024x1024',
    quality: 'medium',
    reference_image_urls: [''],
});
const estimateRequest = useHttp<
    {
        prompt: string;
        size: string;
        quality: string;
        reference_image_urls: string[];
    },
    PriceEstimate
>({
    prompt: '',
    size: '1024x1024',
    quality: 'medium',
    reference_image_urls: [],
});
const priceEstimate = ref<PriceEstimate | null>(null);
const isEstimateUnavailable = ref(false);
const activeOption = ref<PromptOption | null>(null);
const selectedGenerationId = ref<number | null>(null);
const isBuyCreditsOpen = ref(false);
const creditsError = computed(
    () => (form.errors as Record<string, string | undefined>).credits,
);
let estimateTimer: ReturnType<typeof setTimeout> | undefined;
let estimateRevision = 0;

const selectedSize = computed(
    () =>
        sizeOptions.find((option) => option.value === form.size) ??
        sizeOptions[0],
);
const selectedQuality = computed(
    () =>
        qualityOptions.find((option) => option.value === form.quality) ??
        qualityOptions[0],
);
const isPromptEmpty = computed(() => form.prompt.trim().length === 0);
const selectedGeneration = computed(
    () =>
        props.recentGenerations.find(
            (generation) => generation.id === selectedGenerationId.value,
        ) ?? null,
);
const perReferenceImageCost = computed(() =>
    priceEstimate.value
        ? formatUsd(priceEstimate.value.per_reference_image_user_cost_usd)
        : null,
);
const isReferenceMenuOpen = computed({
    get: () => activeOption.value === 'reference',
    set: (open: boolean) => setOptionOpen('reference', open),
});
const isCostDetailsOpen = computed({
    get: () => activeOption.value === 'cost',
    set: (open: boolean) => setOptionOpen('cost', open),
});

useGenerationPolling(() => props.recentGenerations, ['recentGenerations']);

function setOptionOpen(option: PromptOption, open: boolean): void {
    if (open) {
        activeOption.value = option;
    } else if (activeOption.value === option) {
        activeOption.value = null;
    }
}

function toggleOption(option: PromptOption): void {
    activeOption.value = activeOption.value === option ? null : option;
}

function closeOptionsOnOutsidePointer(event: PointerEvent): void {
    if (
        !(event.target instanceof Element) ||
        !event.target.closest('[data-prompt-option]')
    ) {
        activeOption.value = null;
    }
}

function filledReferenceUrls(): string[] {
    return form.reference_image_urls
        .map((url) => url.trim())
        .filter((url) => url.length > 0);
}

function hasOnlyValidUrls(urls: string[]): boolean {
    return urls.every((url) => {
        try {
            const parsedUrl = new URL(url);

            return (
                parsedUrl.protocol === 'http:' ||
                parsedUrl.protocol === 'https:'
            );
        } catch {
            return false;
        }
    });
}

function requestPriceEstimate(): void {
    const revision = ++estimateRevision;

    if (estimateTimer !== undefined) {
        clearTimeout(estimateTimer);
    }

    estimateRequest.cancel();

    const prompt = form.prompt.trim();
    const referenceUrls = filledReferenceUrls();

    if (
        prompt.length > maximumPromptLength ||
        referenceUrls.length > maximumReferenceImages ||
        !hasOnlyValidUrls(referenceUrls)
    ) {
        return;
    }

    estimateTimer = setTimeout(() => {
        estimateRequest.prompt = prompt;
        estimateRequest.size = form.size;
        estimateRequest.quality = form.quality;
        estimateRequest.reference_image_urls = referenceUrls;
        estimateRequest.post(estimatePromptRoute.url(), {
            onSuccess: (response) => {
                if (revision === estimateRevision) {
                    priceEstimate.value = response;
                    isEstimateUnavailable.value = false;
                }
            },
            onHttpException: () => {
                if (revision === estimateRevision) {
                    isEstimateUnavailable.value = true;
                }
            },
            onNetworkError: () => {
                if (revision === estimateRevision) {
                    isEstimateUnavailable.value = true;
                }
            },
        });
    }, estimateDebounceMilliseconds);
}

function submitPrompt(): void {
    if (!page.props.auth.user) {
        router.visit(login());

        return;
    }

    if (isPromptEmpty.value) {
        return;
    }

    form.transform((data) => ({
        ...data,
        prompt: data.prompt.trim(),
        reference_image_urls: filledReferenceUrls(),
    })).post(storeGeneration.url(), {
        preserveScroll: true,
        onSuccess: () => {
            promptDraft.clear();
            form.reset();
            activeOption.value = null;
        },
    });
}

onMounted(() => {
    Object.assign(form, promptDraft.restore());
    document.addEventListener('pointerdown', closeOptionsOnOutsidePointer);
    requestPriceEstimate();
});

onUnmounted(() => {
    document.removeEventListener('pointerdown', closeOptionsOnOutsidePointer);

    if (estimateTimer !== undefined) {
        clearTimeout(estimateTimer);
    }

    estimateRequest.cancel();
});

watch(
    () => form.data(),
    (draft) => {
        promptDraft.save(draft);
        requestPriceEstimate();
    },
    { deep: true },
);
</script>

<template>
    <Head title="Pixelforge" />

    <main
        class="relative flex min-h-screen flex-col bg-white text-[#202124] dark:bg-[#202124] dark:text-[#e8eaed]"
    >
        <AppHeader />

        <section
            aria-labelledby="page-title"
            class="flex min-h-screen flex-col items-center justify-center px-5 py-24"
        >
            <h1
                id="page-title"
                class="mb-8 text-[clamp(1.25rem,3vw,1.75rem)] leading-tight font-medium tracking-tight text-[#202124] select-none dark:text-[#f8f9fa]"
            >
                Generate images with Pixelforge
            </h1>

            <form
                class="w-full max-w-[584px]"
                novalidate
                @submit.prevent="submitPrompt"
                @keydown.esc="activeOption = null"
            >
                <label class="sr-only" for="prompt">Enter a prompt</label>
                <div
                    class="rounded-[28px] border border-[#d4d4d4] bg-white px-5 py-4 shadow-sm transition focus-within:shadow-md hover:shadow-md dark:border-[#5f6368] dark:bg-[#303134]"
                >
                    <textarea
                        id="prompt"
                        v-model="form.prompt"
                        autofocus
                        class="block min-h-24 w-full resize-none bg-transparent text-base leading-6 text-[#202124] outline-none placeholder:text-[#70757a] dark:text-[#e8eaed] dark:placeholder:text-[#9aa0a6]"
                        name="prompt"
                        placeholder="Ask Pixelforge"
                        :maxlength="maximumPromptLength"
                        required
                        rows="3"
                    />
                    <div
                        class="relative mt-3 flex flex-wrap items-center justify-between gap-3 pt-3"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative" data-prompt-option>
                                <button
                                    id="size-option-trigger"
                                    class="flex min-h-9 items-center gap-2 rounded-full border border-[#dadce0] px-3 text-sm text-[#3c4043] transition hover:bg-[#f8f9fa] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:border-[#5f6368] dark:text-[#e8eaed] dark:hover:bg-[#3c4043]"
                                    type="button"
                                    aria-controls="size-option-menu"
                                    :aria-expanded="activeOption === 'size'"
                                    @click="toggleOption('size')"
                                >
                                    <span>{{ selectedSize.label }}</span>
                                    <svg
                                        aria-hidden="true"
                                        class="size-4"
                                        fill="none"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="m5 7.5 5 5 5-5"
                                            stroke="currentColor"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                        />
                                    </svg>
                                </button>
                                <div
                                    v-if="activeOption === 'size'"
                                    id="size-option-menu"
                                    aria-labelledby="size-option-trigger"
                                    class="absolute bottom-full left-0 z-10 mb-2 grid w-60 gap-1 rounded-2xl border border-[#e0e0e0] bg-white p-2 shadow-lg dark:border-[#5f6368] dark:bg-[#303134]"
                                    role="group"
                                >
                                    <button
                                        v-for="option in sizeOptions"
                                        :key="option.value"
                                        class="flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-left text-sm transition hover:bg-[#f1f3f4] focus-visible:outline-2 focus-visible:outline-[#5f6368] dark:hover:bg-[#3c4043]"
                                        :aria-pressed="
                                            form.size === option.value
                                        "
                                        type="button"
                                        @click="
                                            form.size = option.value;
                                            activeOption = null;
                                        "
                                    >
                                        <span>{{ option.label }}</span>
                                        <span
                                            class="text-xs text-[#70757a] dark:text-[#9aa0a6]"
                                            >{{ option.dimensions }}</span
                                        >
                                    </button>
                                </div>
                            </div>

                            <div class="relative" data-prompt-option>
                                <button
                                    id="quality-option-trigger"
                                    class="flex min-h-9 items-center gap-2 rounded-full border border-[#dadce0] px-3 text-sm text-[#3c4043] transition hover:bg-[#f8f9fa] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:border-[#5f6368] dark:text-[#e8eaed] dark:hover:bg-[#3c4043]"
                                    type="button"
                                    aria-controls="quality-option-menu"
                                    :aria-expanded="activeOption === 'quality'"
                                    @click="toggleOption('quality')"
                                >
                                    <span>{{ selectedQuality.label }}</span>
                                    <svg
                                        aria-hidden="true"
                                        class="size-4"
                                        fill="none"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="m5 7.5 5 5 5-5"
                                            stroke="currentColor"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                        />
                                    </svg>
                                </button>
                                <div
                                    v-if="activeOption === 'quality'"
                                    id="quality-option-menu"
                                    aria-labelledby="quality-option-trigger"
                                    class="absolute bottom-full left-0 z-10 mb-2 grid w-40 gap-1 rounded-2xl border border-[#e0e0e0] bg-white p-2 shadow-lg dark:border-[#5f6368] dark:bg-[#303134]"
                                    role="group"
                                >
                                    <button
                                        v-for="option in qualityOptions"
                                        :key="option.value"
                                        class="rounded-xl px-3 py-2 text-left text-sm transition hover:bg-[#f1f3f4] focus-visible:outline-2 focus-visible:outline-[#5f6368] dark:hover:bg-[#3c4043]"
                                        :aria-pressed="
                                            form.quality === option.value
                                        "
                                        type="button"
                                        @click="
                                            form.quality = option.value;
                                            activeOption = null;
                                        "
                                    >
                                        {{ option.label }}
                                    </button>
                                </div>
                            </div>

                            <ReferenceImagesPopover
                                v-model:urls="form.reference_image_urls"
                                v-model:open="isReferenceMenuOpen"
                                :per-reference-image-cost="
                                    perReferenceImageCost
                                "
                                :server-errors="form.errors"
                            />
                        </div>

                        <div class="flex items-center gap-1">
                            <CostEstimatePopover
                                v-model:open="isCostDetailsOpen"
                                :estimate="priceEstimate"
                                :is-unavailable="isEstimateUnavailable"
                                :is-calculating="estimateRequest.processing"
                            />
                            <button
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#202124] text-white transition hover:bg-[#3c4043] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#e8eaed] dark:text-[#202124] dark:hover:bg-white"
                                :disabled="form.processing || isPromptEmpty"
                                type="submit"
                                aria-label="Submit prompt"
                                title="Submit prompt"
                            >
                                <svg
                                    aria-hidden="true"
                                    class="size-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M5 12h14M13 6l6 6-6 6"
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="w-full max-w-[584px] px-5" aria-live="polite">
                <p
                    v-for="error in [
                        form.errors.prompt,
                        form.errors.size,
                        form.errors.quality,
                    ].filter(Boolean)"
                    :key="error"
                    class="mt-2 text-sm leading-5 text-destructive-foreground"
                    role="alert"
                >
                    {{ error }}
                </p>
                <p
                    v-if="creditsError"
                    class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm leading-5 text-destructive-foreground"
                    role="alert"
                >
                    {{ creditsError }}
                    <button
                        type="button"
                        class="rounded-full bg-[#202124] px-4 py-1.5 text-[13px] font-medium text-white transition hover:bg-[#3c4043] dark:bg-[#e8eaed] dark:text-[#202124] dark:hover:bg-white"
                        @click="isBuyCreditsOpen = true"
                    >
                        Buy credits
                    </button>
                </p>
            </div>

            <RecentGenerations
                v-if="page.props.auth.user && recentGenerations.length"
                class="mt-10"
                :generations="recentGenerations"
                @open="(generation) => (selectedGenerationId = generation.id)"
            />
        </section>

        <AppFooter />

        <BuyCreditsDialog
            v-if="page.props.auth.user"
            v-model:open="isBuyCreditsOpen"
        />

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
