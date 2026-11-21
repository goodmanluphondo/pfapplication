import { usePoll } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import type { ComputedRef } from 'vue';
import { isPending } from '@/lib/generations';
import type { Generation } from '@/types/generation';

const pollIntervalMilliseconds = 4000;

export function useGenerationPolling(
    generations: () => Generation[],
    reloadedProps: string[],
): { hasPendingGenerations: ComputedRef<boolean> } {
    const hasPendingGenerations = computed(() => generations().some(isPending));
    const { start, stop } = usePoll(
        pollIntervalMilliseconds,
        { only: reloadedProps },
        { autoStart: false },
    );

    watch(
        hasPendingGenerations,
        (shouldPoll) => (shouldPoll ? start() : stop()),
        { immediate: true },
    );

    return { hasPendingGenerations };
}
