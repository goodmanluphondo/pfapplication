<?php

test('it configures Cloudflare as the default production mail transport', function () {
    $originalEnvironment = [
        $_ENV['APP_ENV'] ?? null,
        $_SERVER['APP_ENV'] ?? null,
        $_ENV['MAIL_MAILER'] ?? null,
        $_SERVER['MAIL_MAILER'] ?? null,
        $_ENV['CLOUDFLARE_ACCOUNT_ID'] ?? null,
        $_SERVER['CLOUDFLARE_ACCOUNT_ID'] ?? null,
        $_ENV['CLOUDFLARE_API_TOKEN'] ?? null,
        $_SERVER['CLOUDFLARE_API_TOKEN'] ?? null,
    ];
    $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';
    $_ENV['MAIL_MAILER'] = $_SERVER['MAIL_MAILER'] = 'cloudflare';
    $_ENV['CLOUDFLARE_ACCOUNT_ID'] = $_SERVER['CLOUDFLARE_ACCOUNT_ID'] = 'account-123';
    $_ENV['CLOUDFLARE_API_TOKEN'] = $_SERVER['CLOUDFLARE_API_TOKEN'] = 'api-token-456';

    try {
        $mailConfig = require config_path('mail.php');
        $servicesConfig = require config_path('services.php');
    } finally {
        [
            $_ENV['APP_ENV'], $_SERVER['APP_ENV'],
            $_ENV['MAIL_MAILER'], $_SERVER['MAIL_MAILER'],
            $_ENV['CLOUDFLARE_ACCOUNT_ID'], $_SERVER['CLOUDFLARE_ACCOUNT_ID'],
            $_ENV['CLOUDFLARE_API_TOKEN'], $_SERVER['CLOUDFLARE_API_TOKEN'],
        ] = $originalEnvironment;
    }

    expect($mailConfig['default'])->toBe('cloudflare')
        ->and($mailConfig['mailers']['cloudflare']['transport'])->toBe('cloudflare')
        ->and($servicesConfig['cloudflare']['account_id'])->toBe('account-123')
        ->and($servicesConfig['cloudflare']['token'])->toBe('api-token-456');
});

test('it routes local Cloudflare mail through the Mailpit SMTP catcher', function () {
    expect(config('mail.mailers.cloudflare.transport'))->toBe('cloudflare');

    $originalEnvironment = [
        $_ENV['APP_ENV'] ?? null,
        $_SERVER['APP_ENV'] ?? null,
        $_ENV['MAIL_TRANSPORT'] ?? null,
        $_SERVER['MAIL_TRANSPORT'] ?? null,
    ];
    $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'local';
    $_ENV['MAIL_TRANSPORT'] = $_SERVER['MAIL_TRANSPORT'] = 'smtp';

    try {
        $mailConfig = require config_path('mail.php');
    } finally {
        [$_ENV['APP_ENV'], $_SERVER['APP_ENV'], $_ENV['MAIL_TRANSPORT'], $_SERVER['MAIL_TRANSPORT']] = $originalEnvironment;
    }

    expect($mailConfig['mailers']['cloudflare']['transport'])->toBe('smtp')
        ->and($mailConfig['mailers']['cloudflare']['host'])->toBe('127.0.0.1')
        ->and($mailConfig['mailers']['cloudflare']['port'])->toBe(1025);
});

test('it always uses Cloudflare in production even when SMTP is configured', function () {
    $originalEnvironment = [
        $_ENV['APP_ENV'] ?? null,
        $_SERVER['APP_ENV'] ?? null,
        $_ENV['MAIL_TRANSPORT'] ?? null,
        $_SERVER['MAIL_TRANSPORT'] ?? null,
    ];
    $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';
    $_ENV['MAIL_TRANSPORT'] = $_SERVER['MAIL_TRANSPORT'] = 'smtp';

    try {
        $mailConfig = require config_path('mail.php');
    } finally {
        [$_ENV['APP_ENV'], $_SERVER['APP_ENV'], $_ENV['MAIL_TRANSPORT'], $_SERVER['MAIL_TRANSPORT']] = $originalEnvironment;
    }

    expect($mailConfig['mailers']['cloudflare']['transport'])->toBe('cloudflare');
});
