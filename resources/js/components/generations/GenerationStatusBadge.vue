<script setup lang="ts">
import { statusBadgeClasses } from '@/lib/generations';
import type { Generation } from '@/types/generation';

defineProps<{
    generation: Generation;
}>();
</script>

<template>
    <span
        class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs leading-4 font-medium"
        :class="statusBadgeClasses[generation.status]"
    >
        {{ generation.status_label }}
    </span>
</template>
