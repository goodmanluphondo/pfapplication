<?php

namespace App\Support;

use RuntimeException;

final class InitialUserPassword
{
    /**
     * Resolve the password used for initial user accounts.
     *
     * Local and testing environments can run without configuration. Other environments
     * must provide INITIAL_USER_PASSWORD to avoid installing a known credential.
     */
    public static function resolve(string $environment, mixed $configured): string
    {
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        if (in_array($environment, ['local', 'testing'], true)) {
            return 'password';
        }

        throw new RuntimeException(
            'INITIAL_USER_PASSWORD must be set outside local development; refusing to seed a known credential.',
        );
    }
}
