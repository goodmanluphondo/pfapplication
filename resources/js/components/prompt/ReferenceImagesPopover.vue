<script setup lang="ts">
import { ImageOff, Plus, X } from '@lucide/vue';
import { computed, reactive } from 'vue';
import { maximumReferenceImages } from '@/lib/generations';

const { perReferenceImageCost, serverErrors = {} } = defineProps<{
    perReferenceImageCost: string | null;
    serverErrors?: Record<string, string | undefined>;
}>();

const referenceImageUrls = defineModel<string[]>('urls', { required: true });
const isOpen = defineModel<boolean>('open', { default: false });

const brokenPreviewUrls = reactive(new Set<string>());

type ReferenceUrlState = 'empty' | 'invalid' | 'valid';

function urlState(url: string): ReferenceUrlState {
    const trimmedUrl = url.trim();

    if (trimmedUrl.length === 0) {
        return 'empty';
    }

    try {
        const parsedUrl = new URL(trimmedUrl);

        return (parsedUrl.protocol === 'http:' ||
            parsedUrl.protocol === 'https:') &&
            parsedUrl.hostname.includes('.')
            ? 'valid'
            : 'invalid';
    } catch {
        return 'invalid';
    }
}

const filledUrlCount = computed(
    () =>
        referenceImageUrls.value.filter((url) => url.trim().length > 0).length,
);
const canAddAnother = computed(
    () => referenceImageUrls.value.length < maximumReferenceImages,
);
const chipLabel = computed(() =>
    filledUrlCount.value ? `Reference · ${filledUrlCount.value}` : 'Reference',
);

function errorFor(url: string, index: number): string {
    if (urlState(url) === 'invalid') {
        return 'Enter a link starting with http:// or https://';
    }

    return serverErrors[`reference_image_urls.${index}`] ?? '';
}

function toggle(): void {
    isOpen.value = !isOpen.value;

    if (isOpen.value && referenceImageUrls.value.length === 0) {
        referenceImageUrls.value.push('');
    }
}

function addAnother(): void {
    if (canAddAnother.value) {
        referenceImageUrls.value.push('');
    }
}

function addAnotherOnEnter(url: string): void {
    if (url.trim().length > 0) {
        addAnother();
    }
}

function remove(index: number): void {
    referenceImageUrls.value.splice(index, 1);

    if (referenceImageUrls.value.length === 0) {
        referenceImageUrls.value.push('');
    }
}
</script>

<template>
    <div class="relative" data-prompt-option>
        <div
            v-if="isOpen"
            id="reference-image-menu"
            aria-label="Reference images"
            class="absolute bottom-full left-0 z-20 mb-2 grid max-h-[calc(100vh-8rem)] w-[360px] max-w-[calc(100vw-2rem)] gap-3.5 overflow-y-auto rounded-[20px] border border-[#e0e0e0] bg-white p-4 shadow-lg dark:border-[#5f6368] dark:bg-[#303134]"
            role="group"
        >
            <div class="grid gap-1">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm leading-5 font-medium"
                        >Reference images</span
                    >
                    <span
                        class="text-xs leading-4 text-[#70757a] dark:text-[#9aa0a6]"
                    >
                        {{ filledUrlCount }} of {{ maximumReferenceImages }}
                    </span>
                </div>
                <p class="text-xs leading-4 text-[#5f6368] dark:text-[#bdc1c6]">
                    Optional. Paste image links to guide the style.
                    <template v-if="perReferenceImageCost"
                        >Each adds about {{ perReferenceImageCost }}.</template
                    >
                </p>
            </div>

            <div class="grid gap-2">
                <div
                    v-for="(url, index) in referenceImageUrls"
                    :key="index"
                    class="grid gap-1"
                >
                    <div
                        class="grid grid-cols-[40px_minmax(0,1fr)_36px] items-center gap-2"
                    >
                        <span
                            class="relative flex size-10 items-center justify-center overflow-hidden rounded-[10px] bg-[#f1f3f4] text-[#70757a] dark:bg-[#3c4043] dark:text-[#9aa0a6]"
                        >
                            <template v-if="urlState(url) === 'valid'">
                                <ImageOff
                                    v-if="brokenPreviewUrls.has(url.trim())"
                                    aria-hidden="true"
                                    class="size-4"
                                />
                                <img
                                    v-else
                                    :key="url.trim()"
                                    :src="url.trim()"
                                    alt=""
                                    class="absolute inset-0 size-full object-cover"
                                    referrerpolicy="no-referrer"
                                    @error="brokenPreviewUrls.add(url.trim())"
                                />
                            </template>
                            <svg
                                v-else
                                aria-hidden="true"
                                class="size-4"
                                fill="none"
                                viewBox="0 0 20 20"
                            >
                                <rect
                                    x="2.5"
                                    y="3.5"
                                    width="15"
                                    height="13"
                                    rx="2"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                />
                                <circle
                                    cx="7"
                                    cy="8"
                                    r="1.5"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                />
                                <path
                                    d="m4 15 4.5-4 2.5 2 2.5-2 2.5 2"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                />
                            </svg>
                        </span>
                        <label
                            class="sr-only"
                            :for="`reference-image-url-${index}`"
                        >
                            Reference image URL {{ index + 1 }}
                        </label>
                        <input
                            :id="`reference-image-url-${index}`"
                            v-model="referenceImageUrls[index]"
                            :aria-invalid="errorFor(url, index) !== ''"
                            class="h-10 min-w-0 rounded-[10px] border bg-white px-3 text-sm text-[#202124] transition outline-none placeholder:text-[#70757a] focus-visible:border-[#5f6368] focus-visible:ring-2 focus-visible:ring-[#5f6368]/20 dark:bg-[#202124] dark:text-[#e8eaed] dark:placeholder:text-[#9aa0a6]"
                            :class="
                                errorFor(url, index)
                                    ? 'border-destructive-foreground'
                                    : 'border-[#d4d4d4] dark:border-[#5f6368]'
                            "
                            name="reference_image_urls[]"
                            placeholder="https://example.com/image.jpg"
                            type="url"
                            @keydown.enter.prevent="addAnotherOnEnter(url)"
                        />
                        <button
                            class="flex size-9 items-center justify-center rounded-full text-[#5f6368] transition hover:bg-[#f1f3f4] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:text-[#bdc1c6] dark:hover:bg-[#3c4043]"
                            :aria-label="`Remove reference image URL ${index + 1}`"
                            type="button"
                            @click="remove(index)"
                        >
                            <X aria-hidden="true" class="size-4" />
                        </button>
                    </div>
                    <p
                        v-if="errorFor(url, index)"
                        class="ml-12 text-xs leading-4 text-destructive-foreground"
                        role="alert"
                    >
                        {{ errorFor(url, index) }}
                    </p>
                </div>
            </div>

            <button
                v-if="canAddAnother"
                class="flex h-10 items-center justify-center gap-1.5 rounded-[10px] border border-dashed border-[#dadce0] text-[13px] font-medium text-[#3c4043] transition hover:bg-[#f8f9fa] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:border-[#5f6368] dark:text-[#e8eaed] dark:hover:bg-[#3c4043]"
                type="button"
                @click="addAnother"
            >
                <Plus aria-hidden="true" class="size-3.5" />
                Add another
            </button>
            <p
                v-else
                class="text-xs leading-4 text-[#70757a] dark:text-[#9aa0a6]"
            >
                That's the maximum of {{ maximumReferenceImages }}.
            </p>
            <p
                v-if="serverErrors.reference_image_urls"
                class="text-xs leading-4 text-destructive-foreground"
                role="alert"
            >
                {{ serverErrors.reference_image_urls }}
            </p>
        </div>

        <button
            id="reference-option-trigger"
            class="flex min-h-9 items-center gap-2 rounded-full border border-[#dadce0] px-3 text-sm text-[#3c4043] transition hover:bg-[#f8f9fa] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:border-[#5f6368] dark:text-[#e8eaed] dark:hover:bg-[#3c4043]"
            :class="filledUrlCount ? 'bg-[#f1f3f4] dark:bg-[#3c4043]' : ''"
            type="button"
            aria-controls="reference-image-menu"
            :aria-expanded="isOpen"
            @click="toggle"
        >
            <svg
                aria-hidden="true"
                class="size-4"
                fill="none"
                viewBox="0 0 20 20"
            >
                <rect
                    x="2.5"
                    y="3.5"
                    width="15"
                    height="13"
                    rx="2"
                    stroke="currentColor"
                    stroke-width="1.5"
                />
                <circle
                    cx="7"
                    cy="8"
                    r="1.5"
                    stroke="currentColor"
                    stroke-width="1.5"
                />
                <path
                    d="m4 15 4.5-4 2.5 2 2.5-2 2.5 2"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                />
            </svg>
            <span>{{ chipLabel }}</span>
        </button>
    </div>
</template>
