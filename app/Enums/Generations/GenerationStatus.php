<?php

declare(strict_types=1);

namespace App\Enums\Generations;

enum GenerationStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    /**
     * @return list<self>
     */
    public static function pending(): array
    {
        return [self::Queued, self::Processing];
    }

    public function isFinal(): bool
    {
        return $this === self::Succeeded || $this === self::Failed;
    }

    public function canMoveTo(self $next): bool
    {
        return ! $this->isFinal() && $next->lifecycleStage() > $this->lifecycleStage();
    }

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Queued',
            self::Processing => 'Processing',
            self::Succeeded => 'Succeeded',
            self::Failed => 'Failed',
        };
    }

    private function lifecycleStage(): int
    {
        return match ($this) {
            self::Queued => 0,
            self::Processing => 1,
            self::Succeeded, self::Failed => 2,
        };
    }
}
