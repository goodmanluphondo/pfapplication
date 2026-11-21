<?php

use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;

function configuredAdminPanel(): Panel
{
    return (new AdminPanelProvider(app()))->panel(Panel::make());
}

test('the admin panel is served from the admin path when no domain is configured', function () {
    config(['filament.admin_domain' => null]);

    $panel = configuredAdminPanel();

    expect($panel->getPath())->toBe('admin')
        ->and($panel->getDomains())->toBe([]);
});

test('the admin panel is served from the root of the configured admin domain', function () {
    config(['filament.admin_domain' => 'admin.example.com']);

    $panel = configuredAdminPanel();

    expect($panel->getPath())->toBe('')
        ->and($panel->getDomains())->toBe(['admin.example.com']);
});
