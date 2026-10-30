<?php

use App\Http\Controllers\AboutPageController;
use App\Http\Controllers\AdvertisingGuideController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\LandmarkController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\EmergencyReportController;
use App\Http\Controllers\ErpController;
use App\Http\Controllers\EstidamaController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GovernorateController;
use App\Http\Controllers\HayahKarimaController;
use App\Http\Controllers\InvestmentContactController;
use App\Http\Controllers\LoginCitizenController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RegisterCitizenController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\ServiceSubmissionController;
use App\Http\Controllers\ServiceSurveyController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\InvestmentPlanController;
use App\Http\Controllers\MapController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OfficialsController;
use App\Http\Controllers\GisPortalController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\RequestController;
use Illuminate\Http\Request;
use App\Models\ServiceSubmission;

Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/wait', [HomeController::class, 'wait'])->name('wait');
Route::get('/interactive-map', [MapController::class, 'index'])->name('governorate.map');

Route::get('/search', SearchController::class)->name('search');

Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,10');

Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post:slug}', [PostController::class, 'show'])->name('posts.show');
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service:id}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/investments', [InvestmentController::class, 'index'])->name('investments.index');
Route::get('/investments/{investment:id}', [InvestmentController::class, 'show'])->name('investments.show');

Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

Route::get('/landmarks', [LandmarkController::class, 'index'])->name('landmarks.index');
Route::get('/landmarks/{landmark:id}', [LandmarkController::class, 'show'])->name('landmarks.show');

Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');

Route::get('/exam-result', [ExamResultController::class, 'index'])->name('exam-results.index');
Route::post('/exam-result', [ExamResultController::class, 'lookup'])->name('exam-results.lookup')->middleware('throttle:10,1');

Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store')->middleware('throttle:5,10');

Route::get('/suggestions/create', [SuggestionController::class, 'create'])->name('suggestions.create');
Route::post('/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store')->middleware('throttle:5,10');

Route::get('/directory', [DirectoryController::class, 'index'])->name('directory.index');

Route::get('/survey/service-center', [ServiceSurveyController::class, 'create'])->name('surveys.service.create');
Route::post('/survey/service-center', [ServiceSurveyController::class, 'store'])->name('surveys.service.store')->middleware('throttle:3,10');

Route::get('/register/citizen', [RegisterCitizenController::class, 'create'])->name('register.citizen');
Route::post('/register/citizen', [RegisterCitizenController::class, 'store'])->name('register.citizen.store');

Route::get('/login', [LoginCitizenController::class, 'create'])->name('login');
Route::post('/login', [LoginCitizenController::class, 'store']);

Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');

Route::post('/logout', [LoginCitizenController::class, 'destroy'])->name('logout');
Route::get('/about/governor-message', [AboutPageController::class, 'governorMessage'])->name('about.governor');

Route::get('/estidamas', [EstidamaController::class, 'index'])->name('estidama.index');
Route::get('/estidama/programs', [EstidamaController::class, 'programs'])->name('estidama.programs');

Route::get('/emergency-report', [EmergencyReportController::class, 'create'])->name('emergency.create');
Route::post('/emergency-report', [EmergencyReportController::class, 'store'])->name('emergency.store')->middleware('throttle:5,10');
Route::get('/emergency-report/villages/{center_id}', [EmergencyReportController::class, 'getVillages'])->name('emergency.villages');
Route::get('/investment-plans', [InvestmentPlanController::class, 'index'])->name('investment.plans.index');
Route::get('/officials/{role}', [OfficialsController::class, 'show'])->name('officials.show');

Route::get('/about-us', [GovernorateController::class, 'about'])->name('about');
Route::get('/hayah-karima-projects', [HayahKarimaController::class, 'index'])->name('projects.hay-karima');

Route::get('/investments/contact/message', [InvestmentContactController::class, 'create'])->name('investment.contact');
Route::post('/investments/contact/message', [InvestmentContactController::class, 'store'])->name('investment.contact.store');

Route::get('/advertising-guide', [AdvertisingGuideController::class, 'index'])->name('advertising.guide');

Route::prefix('advertising-guide')->group(function () {
    Route::get('/points', [AdvertisingGuideController::class, 'points']);
    Route::middleware(['auth'])->group(function () {
        Route::post('/points', [AdvertisingGuideController::class, 'store']);
        Route::put('/points/{ad}', [AdvertisingGuideController::class, 'update']);
        Route::delete('/points/{ad}', [AdvertisingGuideController::class, 'destroy']);
    });
});

Route::prefix('gis-portal')->group(function () {
    Route::get('/', [GisPortalController::class, 'index'])->name('gis.index');
    Route::get('/service/{slug}', [GisPortalController::class, 'showService'])
        ->name('gis.service.show');
    Route::get('/track-request', [GisPortalController::class, 'track'])
        ->name('gis.tracking');
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

Route::post('/efinance/gis/callback', [GisPortalController::class, 'paymentCallback'])
    ->name('efinance.gis.callback');
Route::middleware(['auth'])->group(function () {
    Route::get('/citizen/submissions/{id}', [DashboardController::class, 'show'])
        ->name('citizen.submissions.show');
    Route::get('/erp-governorate', [ErpController::class, 'index'])->name('employee.erp.index');
    Route::post('/services/{service}/submit', [ServiceSubmissionController::class, 'store'])
        ->name('services.submit');
    Route::get('/services/success/{submission}', function (App\Models\ServiceSubmission $submission) {
        return view('services.success', compact('submission'));
    })->name('services.success');
    Route::get('/receipts/{transaction}/print', [ReceiptController::class, 'print'])->name('receipt.print');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('citizen.dashboard');
    Route::get('/dashboard/services', [DashboardController::class, 'services'])->name('citizen.dashboard.services');
    Route::get('/dashboard/gis', [DashboardController::class, 'gis'])->name('citizen.dashboard.gis');
    Route::get('/dashboard/estidama', [DashboardController::class, 'estidama'])->name('citizen.dashboard.estidama');
    Route::get('/dashboard/monitoring', [DashboardController::class, 'monitoring'])->name('citizen.dashboard.monitoring');
    Route::get('/dashboard/fulfillment', [DashboardController::class, 'fulfillment'])->name('citizen.dashboard.fulfillment');
    Route::get('/estidama/apply/{program:id}', [EstidamaController::class, 'apply'])->name('estidama.apply');
    Route::post('/estidama/apply/{program:id}', [EstidamaController::class, 'storeApplication'])->name('estidama.storeApplication');
});
Route::get('/gis/print/{record}', function (App\Models\GisSubmission $record) {
    return view('gis.print-submission', compact('record'));
})->name('gis.print')->middleware(['auth']);

Route::get('/gis/removal-print/{record}', function (App\Models\RemovalOrder $record) {
    return view('gis.print-removal-decision', compact('record'));
})->name('gis.removal.print')->middleware(['auth']);

Route::get('/gis/removal-geojson/{record}', function (App\Models\RemovalOrder $record) {
    $spatial = $record->spatial_data;
    $geojsonStr = $spatial['polygon_geojson'] ?? null;
    if (!$geojsonStr) {
        abort(404, 'No spatial data available');
    }
    $geojson = json_decode($geojsonStr, true);
    if (!$geojson) {
        abort(404, 'Invalid GeoJSON data');
    }
    return response()->json($geojson, 200, [
        'Content-Type' => 'application/geo+json',
        'Content-Disposition' => 'attachment; filename="removal-' . $record->id . '.geojson"',
    ]);
})->name('gis.removal.geojson')->middleware(['auth']);

Route::get('/gis/removal-shapefile/{record}', function (App\Models\RemovalOrder $record) {
    $spatial = $record->spatial_data;
    $geojsonStr = $spatial['polygon_geojson'] ?? null;
    if (!$geojsonStr) {
        abort(404, 'No spatial data available');
    }
    $geojson = json_decode($geojsonStr, true);
    if (!$geojson) {
        abort(404, 'Invalid GeoJSON data');
    }

    // Extract coordinates from FeatureCollection, Feature, or raw geometry
    $geometry = $geojson;
    if (isset($geojson['type']) && $geojson['type'] === 'FeatureCollection' && !empty($geojson['features'][0])) {
        $geometry = $geojson['features'][0]['geometry'] ?? $geojson['features'][0];
    }
    if (isset($geometry['type']) && $geometry['type'] === 'Feature') {
        $geometry = $geometry['geometry'] ?? $geometry;
    }
    $coords = $geometry['coordinates'][0] ?? [];
    if (empty($coords)) {
        abort(404, 'No polygon coordinates');
    }

    // Build Polygon geometry
    $ring = new \Shapefile\Geometry\Linestring();
    foreach ($coords as $c) {
        $ring->addPoint(new \Shapefile\Geometry\Point($c[0], $c[1]));
    }
    $polygon = new \Shapefile\Geometry\Polygon();
    $polygon->addRing($ring);

    // Write Shapefile
    $tempDir = sys_get_temp_dir() . '/shp_' . uniqid();
    mkdir($tempDir, 0777, true);
    $writer = new \Shapefile\ShapefileWriter($tempDir . '/export', [
        \Shapefile\Shapefile::OPTION_BUFFERED_RECORDS => 1,
    ]);
    $writer->setShapeType(\Shapefile\Shapefile::SHAPE_TYPE_POLYGON);
    $writer->addCharField('record_id', 36);
    $writer->addCharField('owner', 100);
    $writer->addCharField('center', 50);
    $writer->writeRecord($polygon, [
        'record_id' => $record->id,
        'owner'     => $record->owner_name,
        'center'    => $record->center,
    ]);
    $writer->save();

    // Zip all output files
    $zipFile = tempnam(sys_get_temp_dir(), 'shp_') . '.zip';
    $zip = new ZipArchive();
    $zip->open($zipFile, ZipArchive::CREATE);
    foreach (glob($tempDir . '/export.*') as $f) {
        $zip->addFile($f, basename($f));
    }
    $zip->close();

    // Cleanup
    array_map('unlink', glob($tempDir . '/export.*'));
    rmdir($tempDir);

    return response()->download($zipFile, 'removal-' . $record->id . '.zip')->deleteFileAfterSend(true);
})->name('gis.removal.shapefile')->middleware(['auth']);

Route::get('/gis/invoice/{record}', function (App\Models\GisSubmission $record) {
    $submission = $record->load(['user', 'subService']);
    return view('filament.gis.pages.invoice-print', compact('submission'));
})->name('gis.invoice.print')->middleware(['auth']);

Route::get('/gis/invoice/print-bulk/{ids}', function (string $ids) {
    $idArray = explode(',', $ids);
    $submissions = App\Models\GisSubmission::whereIn('id', $idArray)->with(['user', 'subService'])->get();
    return view('filament.gis.pages.invoice-print-bulk', compact('submissions'));
})->name('gis.invoice.print-bulk')->middleware(['auth']);

// Route::post('/efinance/callback', function (Request $request) {
//     $status = $request->input('ResponseCode');
//     $reqNum = $request->input('SenderRequestNumber');
//     $submission = ServiceSubmission::find($reqNum);
//     if ($submission && $status === '000') {
//         $submission->update(['status' => 'paid']);
//         return response('000');
//     }

//     return response('FAILED');
// })->name('efinance.callback')->withoutMiddleware([
//     \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class
// ]);
Route::post('/efinance/callback', [ServiceSubmissionController::class, 'paymentConfirmation'])
    ->name('efinance.callback');
