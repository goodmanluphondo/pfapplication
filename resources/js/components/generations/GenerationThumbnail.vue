<script setup lang="ts">
import { CircleAlert, ImageOff, LoaderCircle } from '@lucide/vue';
import { ref, watch } from 'vue';
import { isPending } from '@/lib/generations';
import type { Generation } from '@/types/generation';

const { generation, size = 'sm' } = defineProps<{
    generation: Generation;
    size?: 'sm' | 'md';
}>();

const hasImageFailedToLoad = ref(false);

watch(
    () => generation.image_url,
    () => {
        hasImageFailedToLoad.value = false;
    },
);
</script>

<template>
    <span
        class="relative flex shrink-0 items-center justify-center overflow-hidden bg-[#f1f3f4] text-[#5f6368] dark:bg-[#3c4043] dark:text-[#bdc1c6]"
        :class="size === 'md' ? 'size-14 rounded-xl' : 'size-10 rounded-[10px]'"
    >
        <img
            v-if="generation.image_url && !hasImageFailedToLoad"
            :src="generation.image_url"
            alt=""
            class="absolute inset-0 size-full object-cover"
            loading="lazy"
            @error="hasImageFailedToLoad = true"
        />
        <LoaderCircle
            v-else-if="isPending(generation)"
            aria-hidden="true"
            class="size-[18px] animate-spin"
        />
        <CircleAlert
            v-else-if="generation.status === 'failed'"
            aria-hidden="true"
            :class="size === 'md' ? 'size-[18px]' : 'size-4'"
        />
        <ImageOff
            v-else
            aria-hidden="true"
            :class="size === 'md' ? 'size-[18px]' : 'size-4'"
        />
    </span>
</template>
