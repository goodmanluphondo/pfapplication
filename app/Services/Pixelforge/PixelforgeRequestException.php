<?php

declare(strict_types=1);

namespace App\Services\Pixelforge;

use RuntimeException;

final class PixelforgeRequestException extends RuntimeException
{
    public const string CONNECTION_FAILED = 'connection_failed';

    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
    ) {
        parent::__construct($message);
    }
}
