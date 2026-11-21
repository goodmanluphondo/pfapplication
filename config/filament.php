<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Domain
    |--------------------------------------------------------------------------
    |
    | When set, the admin Filament panel is served from this dedicated domain
    | or subdomain, for example "admin.example.com", instead of the "/admin"
    | path on the application's own domain. Leave it unset to keep serving the
    | panel at "/admin" on whichever domain the application is reached on.
    |
    */

    'admin_domain' => env('FILAMENT_ADMIN_DOMAIN'),

];
