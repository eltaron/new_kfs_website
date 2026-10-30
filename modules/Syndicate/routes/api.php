<?php

use Illuminate\Support\Facades\Route;
use Modules\Syndicate\Http\Controllers\Api\SyndicateApiController;
use Modules\Syndicate\Http\Middleware\AuthorizeSyndicateClient;
use Modules\Syndicate\Http\Middleware\HandleIdempotencyKey;
use Modules\Syndicate\Http\Middleware\LogSyndicateApiCall;

/*
| واجهة برمجة نقابة المهندسين — يحمّلها SyndicateServiceProvider تحت البادئة /api
| الترتيب: تدقيق (كل استدعاء) ← IP + توكن Sanctum + القدرة 'syndicate' ← حد المعدل ← Idempotency
*/

Route::prefix('syndicate/v1')
    ->name('syndicate.api.')
    ->middleware([LogSyndicateApiCall::class, AuthorizeSyndicateClient::class, 'throttle:syndicate-api', HandleIdempotencyKey::class])
    ->group(function () {
        Route::get('requests', [SyndicateApiController::class, 'listRequests'])->name('requests.index');
        Route::get('requests/{transaction_number}', [SyndicateApiController::class, 'showRequest'])->name('requests.show');
        Route::post('requests/{transaction_number}/status', [SyndicateApiController::class, 'updateRequestStatus'])->name('requests.status');

        Route::post('certificates', [SyndicateApiController::class, 'issueSupervision'])->name('certificates.store');
        Route::get('certificates/{number}', [SyndicateApiController::class, 'showCertificate'])->name('certificates.show');

        Route::post('cancellations/{transaction_number}/certificate', [SyndicateApiController::class, 'issueCancellation'])->name('cancellations.certificate');

        Route::get('projects/{id}/timeline', [SyndicateApiController::class, 'timeline'])->name('projects.timeline');

        Route::post('engineers/sync', [SyndicateApiController::class, 'syncEngineers'])->name('engineers.sync');
    });
