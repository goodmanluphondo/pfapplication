import type { Generation } from '@/types/generation';

export const sizeOptions = [
    { value: '1024x1024', label: 'Square', dimensions: '1024 × 1024' },
    { value: '1024x1536', label: 'Portrait', dimensions: '1024 × 1536' },
    { value: '1536x1024', label: 'Landscape', dimensions: '1536 × 1024' },
] as const;

export const qualityOptions = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
] as const;

export const maximumReferenceImages = 4;

const nanodollarDigits = 9;
const centDigits = 2;
const secondsPerMinute = 60;
const secondsPerHour = 3600;
const secondsPerDay = 86400;
const justNowSeconds = 45;

export function formatUsd(usd: string): string {
    const [whole, fraction = ''] = usd.split('.');
    const paddedFraction = fraction.padEnd(nanodollarDigits, '0');
    const wholeCents = Number(paddedFraction.slice(0, centDigits));
    const hasRemainder = /[1-9]/.test(paddedFraction.slice(centDigits));
    const totalCents =
        Number(whole) * 100 + wholeCents + (hasRemainder ? 1 : 0);

    return `$${Math.floor(totalCents / 100)}.${String(totalCents % 100).padStart(centDigits, '0')}`;
}

export function formatTokens(count: number): string {
    return count.toLocaleString('en-ZA');
}

export function sizeLabel(size: string): string {
    return sizeOptions.find((option) => option.value === size)?.label ?? size;
}

export function sizeDimensions(size: string): string {
    return (
        sizeOptions.find((option) => option.value === size)?.dimensions ?? size
    );
}

export function qualityLabel(quality: string): string {
    return (
        qualityOptions.find((option) => option.value === quality)?.label ??
        quality
    );
}

export function isPending(generation: Generation): boolean {
    return generation.status === 'queued' || generation.status === 'processing';
}

export function timeAgo(isoDate: string): string {
    const secondsAgo = (Date.now() - new Date(isoDate).getTime()) / 1000;

    if (secondsAgo < justNowSeconds) {
        return 'just now';
    }

    if (secondsAgo < secondsPerHour) {
        return `${Math.max(1, Math.round(secondsAgo / secondsPerMinute))} min ago`;
    }

    if (secondsAgo < secondsPerDay) {
        return `${Math.round(secondsAgo / secondsPerHour)} h ago`;
    }

    return new Date(isoDate).toLocaleDateString('en-ZA', {
        month: 'short',
        day: 'numeric',
    });
}

export function formatTimestamp(isoDate: string): string {
    return new Date(isoDate).toLocaleString('en-ZA', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
}

export function generationMeta(generation: Generation): string {
    const references = generation.reference_image_count
        ? ` · ${generation.reference_image_count} ref${generation.reference_image_count > 1 ? 's' : ''}`
        : '';

    return `${sizeLabel(generation.size)} · ${qualityLabel(generation.quality)}${references} · ${timeAgo(generation.created_at)}`;
}

export function chargedLabel(generation: Generation): string {
    if (generation.status === 'succeeded' && generation.charged_usd !== null) {
        return formatUsd(generation.charged_usd);
    }

    return generation.status === 'failed' ? '$0.00' : '—';
}

export function costSummaryLabel(generation: Generation): string {
    if (generation.status === 'failed') {
        return 'Free';
    }

    return generation.status === 'succeeded'
        ? chargedLabel(generation)
        : `~${formatUsd(generation.estimated_cost_usd)}`;
}

export function aspectRatio(size: string): string {
    const [width, height] = size.split('x');

    return `${width} / ${height}`;
}

export const statusBadgeClasses: Record<Generation['status'], string> = {
    queued: 'bg-[#f1f3f4] text-[#5f6368] dark:bg-[#3c4043] dark:text-[#bdc1c6]',
    processing:
        'bg-[#e8f0fe] text-[#1967d2] dark:bg-[rgba(138,180,248,.16)] dark:text-[#8ab4f8]',
    succeeded:
        'bg-[#e6f4ea] text-[#137333] dark:bg-[rgba(129,201,149,.16)] dark:text-[#81c995]',
    failed: 'bg-[#fce8e6] text-[#c5221f] dark:bg-[rgba(242,139,130,.16)] dark:text-[#f28b82]',
};
