<?php

declare(strict_types=1);

namespace App\Http\Requests\Prompt;

use Illuminate\Foundation\Http\FormRequest;

class PromptDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:4000'],
            'size' => ['required', 'in:1024x1024,1024x1536,1536x1024'],
            'quality' => ['required', 'in:low,medium,high'],
            'reference_image_urls' => ['nullable', 'array', 'max:4'],
            'reference_image_urls.*' => ['required', 'url:http,https'],
        ];
    }
}
