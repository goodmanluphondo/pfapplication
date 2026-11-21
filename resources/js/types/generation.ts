export type GenerationStatus = 'queued' | 'processing' | 'succeeded' | 'failed';

export type EstimatedTokens = {
    input_text: { estimated: number; minimum: number; maximum: number };
    input_image: number;
    output: number;
    reasoning: { minimum: number; maximum: number };
};

export type Generation = {
    id: number;
    status: GenerationStatus;
    status_label: string;
    prompt: string;
    size: string;
    quality: string;
    reference_image_count: number;
    estimated_cost_usd: string;
    estimated_minimum_cost_usd: string;
    estimated_maximum_cost_usd: string;
    charged_usd: string | null;
    estimated_tokens: EstimatedTokens;
    usage: {
        input_text_tokens: number;
        input_image_tokens: number;
        output_tokens: number;
        reasoning_tokens: number;
    } | null;
    image_url: string | null;
    image_expired: boolean;
    image_url_expires_at: string | null;
    error_code: string | null;
    error_message: string | null;
    created_at: string;
    completed_at: string | null;
};

export type GenerationSummary = {
    spent_usd: string;
    succeeded_count: number;
    pending_count: number;
    pending_estimated_usd: string;
};

export type PriceEstimate = {
    estimated_provider_cost_usd: string;
    estimated_user_cost_usd: string;
    minimum_user_cost_usd: string;
    maximum_user_cost_usd: string;
    per_reference_image_user_cost_usd: string;
    tokens: EstimatedTokens;
};
