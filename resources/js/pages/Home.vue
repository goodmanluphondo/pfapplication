<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronDown, Moon, Sun, User as UserIcon } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import { home, login, register } from '@/routes';
import { submit as submitPromptRoute } from '@/routes/prompt';
import { useAppearance } from '@/composables/useAppearance';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { usePromptDraftStore } from '@/stores/promptDraft';

const promptDraft = usePromptDraftStore();
const form = useForm({
    prompt: '',
    size: '1024x1024',
    quality: 'standard',
    reference_image_urls: [''],
});
const submittedPrompt = ref('');

onMounted(() => {
    Object.assign(form, promptDraft.restore());
});

watch(
    () => form.data(),
    (draft) => promptDraft.save(draft),
    { deep: true },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
const { resolvedAppearance, updateAppearance } = useAppearance();
const isDarkMode = computed(() => resolvedAppearance.value === 'dark');
const userName = computed(() =>
    [user.value?.first_name, user.value?.last_name]
        .filter(Boolean)
        .join(' '),
);
const activeOption = ref<'size' | 'quality' | null>(null);
const showReferenceUrls = ref(false);
const sizeOptions = [
    { value: '1024x1024', label: 'Square', dimensions: '1024 × 1024' },
    { value: '1024x1536', label: 'Portrait', dimensions: '1024 × 1536' },
    { value: '1536x1024', label: 'Landscape', dimensions: '1536 × 1024' },
] as const;
const qualityOptions = [
    { value: 'standard', label: 'Standard' },
    { value: 'high', label: 'High' },
] as const;
const selectedSize = computed(
    () => sizeOptions.find((option) => option.value === form.size) ?? sizeOptions[0],
);
const selectedQuality = computed(
    () => qualityOptions.find((option) => option.value === form.quality) ?? qualityOptions[0],
);

function toggleAppearance(): void {
    updateAppearance(isDarkMode.value ? 'light' : 'dark');
}

function toggleOption(option: 'size' | 'quality'): void {
    showReferenceUrls.value = false;
    activeOption.value = activeOption.value === option ? null : option;
}

function toggleReferenceUrls(): void {
    activeOption.value = null;
    showReferenceUrls.value = !showReferenceUrls.value;

    if (showReferenceUrls.value && form.reference_image_urls.length === 0) {
        form.reference_image_urls.push('');
    }
}

function addReferenceUrl(): void {
    form.reference_image_urls.push('');
}

function removeReferenceUrl(index: number): void {
    form.reference_image_urls.splice(index, 1);
}

function submitPrompt(): void {
    if (!page.props.auth.user) {
        router.visit(login());

        return;
    }

    const trimmedPrompt = form.prompt.trim();

    if (trimmedPrompt.length === 0) {
        return;
    }

    form.prompt = trimmedPrompt;
    form.reference_image_urls = form.reference_image_urls
        .map((url) => url.trim())
        .filter((url) => url.length > 0);

    form.post(submitPromptRoute.url(), {
        preserveScroll: true,
        onSuccess: () => {
            submittedPrompt.value = trimmedPrompt;
            promptDraft.clear();
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Pixelforge" />

    <main class="relative flex min-h-screen flex-col bg-white text-[#202124] dark:bg-[#202124] dark:text-[#e8eaed]">
        <header
            class="absolute inset-x-0 top-0 flex h-16 items-center justify-between gap-5 px-6 text-[13px]"
        >
            <Link
                :href="home()"
                :aria-label="`${page.props.name} home`"
                class="text-lg font-semibold tracking-tight text-[#202124] dark:text-[#f8f9fa]"
            >
                {{ page.props.name }}
            </Link>

            <div class="flex items-center gap-3">
                <button
                    :aria-label="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                    :aria-pressed="isDarkMode"
                    class="flex size-10 items-center justify-center rounded-full text-[#5f6368] transition hover:bg-[#f1f3f4] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:text-[#bdc1c6] dark:hover:bg-[#3c4043]"
                    type="button"
                    @click="toggleAppearance"
                >
                    <Sun v-if="isDarkMode" aria-hidden="true" class="size-5" />
                    <Moon v-else aria-hidden="true" class="size-5" />
                </button>

            <DropdownMenu v-if="user">
                <DropdownMenuTrigger :as-child="true">
                    <button
                        :aria-label="`Open account menu for ${userName}`"
                        class="flex h-10 w-16 items-center justify-center gap-1 rounded-full bg-[#f1f3f4] p-1 text-[#5f6368] transition hover:bg-[#f1f3f4] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:bg-[#3c4043] dark:text-[#e8eaed] dark:hover:bg-[#3c4043]"
                        type="button"
                    >
                        <Avatar class="size-8 shrink-0 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="user.avatar"
                                :src="user.avatar"
                                :alt="userName"
                            />
                            <AvatarFallback
                                class="rounded-full bg-[#e8eaed] text-xs font-semibold text-[#202124] dark:bg-[#5f6368] dark:text-[#f8f9fa]"
                            >
                                <UserIcon aria-hidden="true" class="size-4" />
                            </AvatarFallback>
                        </Avatar>
                        <ChevronDown aria-hidden="true" class="size-4 shrink-0" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
            <div v-else class="flex items-center gap-5">
                <Link
                    :href="login()"
                    class="text-[#202124] hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-[#5f6368] dark:text-[#e8eaed]"
                >
                    Log in
                </Link>
                <Link
                    :href="register()"
                    class="rounded-full bg-[#202124] px-5 py-2.5 font-medium text-white transition hover:bg-[#3c4043] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:bg-[#e8eaed] dark:text-[#202124] dark:hover:bg-white"
                >
                    Register
                </Link>
            </div>
            </div>
        </header>

        <section
            aria-labelledby="page-title"
            class="flex min-h-screen flex-col items-center justify-center px-5 py-24"
        >
            <h1
                id="page-title"
                class="mb-8 select-none text-[clamp(1.25rem,3vw,1.75rem)] font-medium leading-tight tracking-tight text-[#202124] dark:text-[#f8f9fa]"
            >
                Generate images with Pixelforge
            </h1>

            <form
                class="w-full max-w-[584px]"
                novalidate
                @submit.prevent="submitPrompt"
                @keydown.esc="activeOption = null; showReferenceUrls = false"
            >
                <label class="sr-only" for="prompt">Enter a prompt</label>
                <div
                    class="rounded-[28px] border border-[#d4d4d4] bg-white px-5 py-4 shadow-sm transition hover:shadow-md focus-within:shadow-md dark:border-[#5f6368] dark:bg-[#303134]"
                >
                    <textarea
                        id="prompt"
                        v-model="form.prompt"
                        autofocus
                        class="min-h-24 w-full resize-none bg-transparent text-base leading-6 text-[#202124] outline-none placeholder:text-[#70757a] dark:text-[#e8eaed] dark:placeholder:text-[#9aa0a6]"
                        name="prompt"
                        placeholder="Ask Pixelforge"
                        required
                        rows="3"
                    />
                    <div
                        class="mt-3 flex flex-wrap items-center justify-between gap-3 pt-3"
                    >
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="relative">
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
                                        :aria-pressed="form.size === option.value"
                                        type="button"
                                        @click="
                                            form.size = option.value;
                                            activeOption = null;
                                        "
                                    >
                                        <span>{{ option.label }}</span>
                                        <span class="text-xs text-[#70757a] dark:text-[#9aa0a6]">{{
                                            option.dimensions
                                        }}</span>
                                    </button>
                                </div>
                            </div>

                            <div class="relative">
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
                                        :aria-pressed="form.quality === option.value"
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

                            <div class="relative">
                                <div
                                    v-if="showReferenceUrls"
                                    id="reference-image-menu"
                                    aria-labelledby="reference-option-trigger"
                                    class="absolute bottom-full left-0 z-20 mb-2 grid max-h-[calc(100vh-8rem)] w-72 max-w-[calc(100vw-2rem)] gap-3 overflow-y-auto rounded-2xl border border-[#e0e0e0] bg-white p-4 shadow-lg dark:border-[#5f6368] dark:bg-[#303134]"
                                    role="group"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-medium">Reference image URLs</span>
                                        <span class="text-xs text-[#70757a] dark:text-[#9aa0a6]">Optional</span>
                                    </div>
                                    <div
                                        v-for="(url, index) in form.reference_image_urls"
                                        :key="index"
                                        class="grid grid-cols-[1fr_auto] items-start gap-2"
                                    >
                                        <div class="grid gap-1">
                                            <label
                                                class="sr-only"
                                                :for="`reference-image-url-${index}`"
                                            >
                                                Reference image URL {{ index + 1 }}
                                            </label>
                                            <input
                                                :id="`reference-image-url-${index}`"
                                                v-model="form.reference_image_urls[index]"
                                                class="h-10 min-w-0 rounded-lg border border-[#d4d4d4] bg-white px-3 text-sm outline-none transition placeholder:text-[#70757a] focus-visible:border-[#5f6368] focus-visible:ring-2 focus-visible:ring-[#5f6368]/20 dark:border-[#5f6368] dark:bg-[#202124] dark:text-[#e8eaed] dark:placeholder:text-[#9aa0a6]"
                                                name="reference_image_urls[]"
                                                placeholder="https://example.com/image.jpg"
                                                type="url"
                                            />
                                            <p
                                                v-if="form.errors[`reference_image_urls.${index}`]"
                                                class="text-sm text-destructive"
                                                role="alert"
                                            >
                                                {{ form.errors[`reference_image_urls.${index}`] }}
                                            </p>
                                        </div>
                                        <button
                                            class="flex size-10 items-center justify-center rounded-lg text-[#5f6368] transition hover:bg-[#f1f3f4] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:text-[#bdc1c6] dark:hover:bg-[#3c4043]"
                                            :aria-label="`Remove reference image URL ${index + 1}`"
                                            type="button"
                                            @click="removeReferenceUrl(index)"
                                        >
                                            <span aria-hidden="true" class="text-xl">×</span>
                                        </button>
                                    </div>
                                    <button
                                        class="w-fit rounded-lg px-2 py-1 text-sm font-medium text-[#202124] transition hover:bg-[#f1f3f4] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:text-[#e8eaed] dark:hover:bg-[#3c4043]"
                                        type="button"
                                        @click="addReferenceUrl"
                                    >
                                        Add URL
                                    </button>
                                    <p
                                        v-if="form.errors.reference_image_urls"
                                        class="text-sm text-destructive"
                                        role="alert"
                                    >
                                        {{ form.errors.reference_image_urls }}
                                    </p>
                                </div>
                                <button
                                    id="reference-option-trigger"
                                class="flex min-h-9 items-center gap-2 rounded-full border border-[#dadce0] px-3 text-sm text-[#3c4043] transition hover:bg-[#f8f9fa] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] dark:border-[#5f6368] dark:text-[#e8eaed] dark:hover:bg-[#3c4043]"
                                type="button"
                                aria-controls="reference-image-menu"
                                :aria-expanded="showReferenceUrls"
                                @click="toggleReferenceUrls"
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
                                    Reference
                                </button>
                            </div>
                        </div>

                        <button
                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#202124] text-white transition hover:bg-[#3c4043] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-[#e8eaed] dark:text-[#202124] dark:hover:bg-white"
                            :disabled="form.processing || form.prompt.trim().length === 0"
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
                <p
                    v-if="form.errors.prompt"
                    class="mt-2 text-sm text-destructive"
                    role="alert"
                >
                    {{ form.errors.prompt }}
                </p>
                <div
                    v-if="form.errors.size || form.errors.quality"
                    class="mt-2 grid gap-1"
                    role="alert"
                >
                    <p v-if="form.errors.size" class="text-sm text-destructive">
                        {{ form.errors.size }}
                    </p>
                    <p v-if="form.errors.quality" class="text-sm text-destructive">
                        {{ form.errors.quality }}
                    </p>
                </div>

            </form>

            <p
                v-if="submittedPrompt"
                aria-live="polite"
                class="mt-6 max-w-[584px] text-center text-sm text-[#5f6368] dark:text-[#bdc1c6]"
            >
                Prompt ready: {{ submittedPrompt }}
            </p>
        </section>

        <footer
            class="absolute inset-x-0 bottom-0 flex min-h-14 items-center justify-between gap-4 px-6 py-3 text-xs text-[#5f6368] dark:text-[#bdc1c6]"
        >
            <p>Copyright &copy; 2026 PF Application. All rights reserved.</p>
            <a
                class="shrink-0 hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#5f6368]"
                href="#"
                @click.prevent
            >
                Privacy
            </a>
        </footer>
    </main>
</template>
