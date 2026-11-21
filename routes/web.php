<?php

use App\Http\Controllers\CreditPurchaseController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\GenerationRetryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PixelforgeWebhookController;
use App\Http\Controllers\PromptEstimateController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::post('/prompt/estimate', PromptEstimateController::class)->name('prompt.estimate');

Route::middleware('auth')->group(function () {
    Route::get('/history', [GenerationController::class, 'index'])->name('generations.index');
    Route::post('/generations', [GenerationController::class, 'store'])->name('generations.store');
    Route::post('/generations/{generation}/retry', GenerationRetryController::class)->name('generations.retry');
    Route::post('/credits/purchases', CreditPurchaseController::class)->name('credits.purchase');
});

Route::post('/webhooks/pixelforge', PixelforgeWebhookController::class)->name('webhooks.pixelforge');

require __DIR__.'/profile.php';
