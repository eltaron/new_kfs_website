<?php

use Illuminate\Support\Facades\Route;
use Modules\Syndicate\Http\Controllers\Web\SyndicateWebController;

/*
| صفحات عامة ولوحة المواطن لمنظومة الإشراف الهندسي (مجموعة web يضيفها SyndicateServiceProvider).
| ملاحظة: لوحتا Filament على /syndicate و /council تسجلان مساراتهما بنفسيهما.
*/

Route::prefix('syndicate')->name('syndicate.')->group(function () {
    // التحقق العام من الشهادات (رقم الشهادة / QR)
    Route::get('verify', [SyndicateWebController::class, 'verifyForm'])
        ->middleware('throttle:30,1')->name('verify.form');
    Route::get('verify/{number}', [SyndicateWebController::class, 'verify'])
        ->where('number', '[A-Za-z0-9\-]+')
        ->middleware('throttle:30,1')->name('verify.show');

    Route::middleware('auth')->group(function () {
        Route::get('my-projects', [SyndicateWebController::class, 'myProjects'])->name('my-projects');
        Route::post('my-projects/{project}/requests', [SyndicateWebController::class, 'submitOnline'])
            ->whereNumber('project')->middleware('throttle:6,1')->name('my-projects.requests');
        Route::get('print/{number}', [SyndicateWebController::class, 'print'])
            ->where('number', '[A-Za-z0-9\-]+')->name('certificates.print');
    });
});
