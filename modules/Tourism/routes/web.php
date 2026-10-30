<?php

// Tourism module routes (moved verbatim from routes/web.php — URIs, names and middleware unchanged).

use Illuminate\Support\Facades\Route;
use Modules\Tourism\Http\Controllers\BookingController;
use Modules\Tourism\Http\Controllers\LandmarkController;
use Modules\Tourism\Http\Controllers\TourBookingController;

Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store')->middleware('throttle:public-forms');

Route::get('/landmarks', [LandmarkController::class, 'index'])->name('landmarks.index');
Route::get('/landmarks/{landmark:id}', [LandmarkController::class, 'show'])->name('landmarks.show');

Route::get('/tour-bookings', [TourBookingController::class, 'create'])->name('tour-bookings.create');
Route::post('/tour-bookings', [TourBookingController::class, 'store'])->name('tour-bookings.store')->middleware('throttle:public-forms');
