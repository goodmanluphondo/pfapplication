<?php

declare(strict_types=1);

namespace App\Enums\Users;

enum UserRole: string
{
    case BasicUser = 'basic_user';
    case SystemAdmin = 'system_admin';

    public function label(): string
    {
        return match ($this) {
            self::BasicUser => 'Basic user',
            self::SystemAdmin => 'System administrator',
        };
    }
}
