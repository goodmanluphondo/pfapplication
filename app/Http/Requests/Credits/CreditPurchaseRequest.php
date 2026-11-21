<?php

declare(strict_types=1);

namespace App\Http\Requests\Credits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreditPurchaseRequest extends FormRequest
{
    /**
     * @var list<int>
     */
    public const array PACK_DOLLARS = [5, 10, 25];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'dollars' => ['required', 'integer', Rule::in(self::PACK_DOLLARS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dollars.in' => 'Choose one of the credit packs.',
        ];
    }

    public function dollars(): int
    {
        return $this->integer('dollars');
    }
}
