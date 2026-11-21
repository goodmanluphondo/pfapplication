<?php

declare(strict_types=1);

namespace App\Http\Requests\Prompt;

final class PromptEstimateRequest extends PromptDataRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'prompt' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
