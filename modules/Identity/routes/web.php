<?php

// Identity (shared kernel) routes (moved verbatim from routes/web.php — URIs, names and middleware unchanged).

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\ForgotPasswordController;
use Modules\Identity\Http\Controllers\ResetPasswordController;
use Modules\Identity\Http\Controllers\SecureFileController;
use Modules\Identity\Http\Controllers\TwoFactorController;

// Google Authenticator (2FA): pre-auth challenge after password, post-auth verification, settings.
Route::get('/two-factor-challenge', [TwoFactorController::class, 'challenge'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [TwoFactorController::class, 'challengeStore'])->name('two-factor.challenge.store')->middleware('throttle:two-factor');
Route::middleware(['auth'])->prefix('account/two-factor')->name('two-factor.')->group(function () {
    Route::get('/verify', [TwoFactorController::class, 'verify'])->name('verify');
    Route::post('/verify', [TwoFactorController::class, 'verifyStore'])->name('verify.store')->middleware('throttle:two-factor');
    Route::get('/', [TwoFactorController::class, 'settings'])->name('settings');
    Route::post('/enable', [TwoFactorController::class, 'enable'])->name('enable')->middleware('throttle:two-factor');
    Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm')->middleware('throttle:two-factor');
    Route::post('/cancel', [TwoFactorController::class, 'cancel'])->name('cancel');
    Route::delete('/', [TwoFactorController::class, 'disable'])->name('disable')->middleware('throttle:two-factor');
    Route::post('/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes')->middleware('throttle:two-factor');
});

// Authorized access to private uploads (national-ID images, complaint/emergency attachments, Estidama documents).
Route::get('/secure-files/{type}/{id}/{field}/{index?}', [SecureFileController::class, 'show'])
    ->whereNumber('index')
    ->middleware(['auth', 'throttle:60,1'])
    ->name('secure-files.show');

Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email')->middleware('throttle:5,10');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update')->middleware('throttle:5,10');
