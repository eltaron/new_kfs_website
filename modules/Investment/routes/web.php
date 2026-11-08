<?php

// Investment module routes (moved verbatim from routes/web.php — URIs, names and middleware unchanged).

use Illuminate\Support\Facades\Route;
use Modules\Investment\Http\Controllers\AdvertisingGuideController;
use Modules\Investment\Http\Controllers\CenterGuideController;
use Modules\Investment\Http\Controllers\HayahKarimaController;
use Modules\Investment\Http\Controllers\InvestmentContactController;
use Modules\Investment\Http\Controllers\InvestmentController;
use Modules\Investment\Http\Controllers\InvestmentPlanController;
use Modules\Investment\Http\Controllers\ProjectController;

Route::get('/investments', [InvestmentController::class, 'index'])->name('investments.index');
Route::get('/investments/{investment:id}', [InvestmentController::class, 'show'])->name('investments.show');

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

Route::get('/investment-plans', [InvestmentPlanController::class, 'index'])->name('investment.plans.index');

Route::get('/center-guides', [CenterGuideController::class, 'create'])->name('center-guides.create')->middleware('auth');
Route::post('/center-guides', [CenterGuideController::class, 'store'])->name('center-guides.store')->middleware(['auth', 'throttle:5,10']);

Route::get('/hayah-karima-projects', [HayahKarimaController::class, 'index'])->name('projects.hay-karima');
Route::get('/hayah-karima-projects/{slug}', [HayahKarimaController::class, 'show'])->name('projects.hay-karima.show');

Route::get('/investments/contact/message', [InvestmentContactController::class, 'create'])->name('investment.contact');
Route::post('/investments/contact/message', [InvestmentContactController::class, 'store'])->name('investment.contact.store')->middleware('throttle:5,10');

Route::get('/advertising-guide', [AdvertisingGuideController::class, 'index'])->name('advertising.guide');

Route::prefix('advertising-guide')->group(function () {
    Route::get('/points', [AdvertisingGuideController::class, 'points']);
    // Writes: signed-in users only, rate-limited (20 changes / minute / user).
    Route::middleware(['auth', 'throttle:20,1'])->group(function () {
        Route::post('/points', [AdvertisingGuideController::class, 'store']);
        Route::put('/points/{ad}', [AdvertisingGuideController::class, 'update']);
        Route::delete('/points/{ad}', [AdvertisingGuideController::class, 'destroy']);
    });
});
