<?php

// Payments module routes (moved verbatim from routes/web.php — URIs, names and middleware unchanged).
// CSRF exceptions for these URIs stay in bootstrap/app.php.

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\PaymentController;
use Modules\Payments\Http\Controllers\ReceiptController;

// Legacy GIS callback URL — now served by the unified, idempotent handler.
Route::post('/efinance/gis/callback', [PaymentController::class, 'callback'])
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class, \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('efinance.gis.callback');

// ─── Unified e-Finance payments (App\Services\Payments\PaymentService) ───
// Server-to-server confirmation: no session / CSRF (gateway POST).
Route::post('/efinance/callback', [PaymentController::class, 'callback'])
    ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class, \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->middleware('throttle:120,1')
    ->name('efinance.callback');
Route::prefix('payments')->name('payments.')->group(function () {
    // Start/resume a payment: owner must be logged in; guest payables need a signed URL.
    Route::match(['get', 'post'], '/pay/{type}/{id}', [PaymentController::class, 'pay'])
        ->where(['type' => '[a-z0-9_]+', 'id' => '[A-Za-z0-9\-]+'])
        ->middleware('throttle:20,1')
        ->name('pay');
    Route::get('/checkout/{type}/{id}', [PaymentController::class, 'checkout'])
        ->where(['type' => '[a-z0-9_]+', 'id' => '[A-Za-z0-9\-]+'])
        ->name('checkout');
    // Browser return from the gateway (GET or cross-site POST -> 303 to GET).
    Route::get('/return/{transaction}', [PaymentController::class, 'return'])->name('return');
    Route::post('/return/{transaction}', [PaymentController::class, 'returnPost'])
        ->withoutMiddleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class, \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
        ->name('return.post');
});
// Receipts: owner, authorised staff, or a signed link (guest payers).
Route::get('/receipts/{transaction}/print', [ReceiptController::class, 'print'])->name('receipt.print');
