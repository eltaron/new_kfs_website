<?php

// خدمة «طلب صلاحية الموقع» — صفحات المواطن. تُحمّل من SiteValidityServiceProvider داخل مجموعة "web".
// لوحة الموظفين: /site-validity (Filament). كل المسارات تبدأ بـ /site-validity* (مستثناة من كاش PWA في public/sw.js).

use Illuminate\Support\Facades\Route;
use Modules\SiteValidity\Http\Controllers\CitizenRequestController;
use Modules\SiteValidity\Http\Controllers\ReportController;

// التقرير المطبوع لـ«خريطة الطلبات» في اللوحة (مسؤول الخدمة — التحقق داخل المتحكم).
Route::get('/site-validity-reports/map', [ReportController::class, 'map'])
    ->middleware(['auth', 'throttle:30,1'])->name('site-validity.reports.map');

Route::prefix('site-validity-request')->name('site-validity.')->group(function () {
    Route::get('/', [CitizenRequestController::class, 'landing'])->name('landing');
    Route::get('/villages/{centre}', [CitizenRequestController::class, 'villages'])
        ->whereNumber('centre')->middleware('throttle:60,1')->name('villages');

    Route::middleware('auth')->group(function () {
        Route::get('/apply', [CitizenRequestController::class, 'create'])->name('apply');
        Route::post('/apply', [CitizenRequestController::class, 'store'])->middleware('throttle:5,10')->name('store');
        Route::get('/my-requests', [CitizenRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{svRequest}', [CitizenRequestController::class, 'show'])->whereNumber('svRequest')->name('requests.show');
        Route::get('/requests/{svRequest}/submitted', [CitizenRequestController::class, 'success'])->whereNumber('svRequest')->name('requests.success');
        Route::post('/requests/{svRequest}/completion', [CitizenRequestController::class, 'completion'])
            ->whereNumber('svRequest')->middleware('throttle:10,10')->name('requests.completion');
        Route::post('/requests/{svRequest}/appointment/confirm', [CitizenRequestController::class, 'confirmAppointment'])
            ->whereNumber('svRequest')->middleware('throttle:10,10')->name('requests.appointment.confirm');
        Route::post('/requests/{svRequest}/appointment/reschedule', [CitizenRequestController::class, 'rescheduleAppointment'])
            ->whereNumber('svRequest')->middleware('throttle:10,10')->name('requests.appointment.reschedule');
        Route::get('/files/{file}', [CitizenRequestController::class, 'download'])->whereNumber('file')->middleware('throttle:120,1')->name('files.download');
    });
});
