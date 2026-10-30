<?php

// GIS module routes (moved verbatim from routes/web.php — URIs, names and middleware unchanged).

use Illuminate\Support\Facades\Route;
use Modules\Gis\Http\Controllers\GisPortalController;
use Modules\Gis\Http\Controllers\MapController;

Route::get('/interactive-map', [MapController::class, 'index'])->name('governorate.map');

Route::prefix('gis-portal')->group(function () {
    Route::get('/', [GisPortalController::class, 'index'])->name('gis.index');
    Route::get('/service/{slug}', [GisPortalController::class, 'showService'])
        ->name('gis.service.show');
    Route::get('/track-request', [GisPortalController::class, 'track'])
        ->name('gis.tracking')->middleware('throttle:20,1');
    Route::middleware(['auth'])->group(function () {
        Route::get('/apply/{slug}', [GisPortalController::class, 'createApplication'])
            ->name('gis.apply.start');
        Route::post('/apply/{slug}/submit', [GisPortalController::class, 'storeApplication'])
            ->name('gis.apply.submit');
        Route::get('/application-success/{id}', [GisPortalController::class, 'success'])
            ->name('gis.apply.success');
    });
    Route::prefix('api')->group(function () {
        Route::get('/markaz/{markazId}/units', [GisPortalController::class, 'getLocalUnits']);
        Route::get('/unit/{unitId}/villages', [GisPortalController::class, 'getVillages']);
    });
});

Route::get('/gis/print/{record}', function (Modules\Gis\Models\GisSubmission $record) {
    return view('gis.print-submission', compact('record'));
})->name('gis.print')->middleware(['auth', 'can:view-gis-submission,record']);

Route::get('/gis/invoice/{record}', function (Modules\Gis\Models\GisSubmission $record) {
    $submission = $record->load(['user', 'subService']);
    return view('filament.gis.pages.invoice-print', compact('submission'));
})->name('gis.invoice.print')->middleware(['auth', 'can:view-gis-submission,record']);

Route::get('/gis/invoice/print-bulk/{ids}', function (string $ids) {
    $idArray = explode(',', $ids);
    $submissions = Modules\Gis\Models\GisSubmission::whereIn('id', $idArray)->with(['user', 'subService'])->get();
    // IDOR guard: every requested submission must be viewable by the current user.
    abort_if($submissions->isEmpty(), 404);
    foreach ($submissions as $submission) {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view-gis-submission', $submission), 403);
    }
    return view('filament.gis.pages.invoice-print-bulk', compact('submissions'));
})->name('gis.invoice.print-bulk')->middleware(['auth']);
