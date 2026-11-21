<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');

Route::post('/prompt', function (Request $request): RedirectResponse {
    $request->validate([
        'prompt' => ['required', 'string'],
        'size' => ['required', 'in:1024x1024,1024x1536,1536x1024'],
        'quality' => ['required', 'in:standard,high'],
        'reference_image_urls' => ['nullable', 'array'],
        'reference_image_urls.*' => ['required', 'url'],
    ]);

    return back();
})->middleware('auth')->name('prompt.submit');

require __DIR__.'/settings.php';
