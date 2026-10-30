<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Platform web routes
|--------------------------------------------------------------------------
| The portal is a modular monolith (docs/architecture.md). Every bounded
| context loads its own routes from modules/<Name>/routes/web.php through its
| <Name>ServiceProvider (bootstrap/providers.php), with identical URIs, names
| and middleware:
|
|   Identity          two-factor.*, password.*, secure-files.show
|   Content           home, wait, search, posts.*, comments.store, directory.index,
|                     officials.show, about, about.governor, exam-results.*, employee.erp.index
|   Citizen           services.*, complaints.*, suggestions.*, surveys.*, emergency.*,
|                     contact.*, register.citizen*, login, logout, citizen.*, citizen-lookup*, requests.index
|   Gis               gis.index, gis.service.show, gis.tracking, gis.apply.*, gis.print,
|                     gis.invoice.*, governorate.map
|   RemovalGovernance gis.removal.*
|   Estidama          estidama.*, lms.*
|   Tourism           landmarks.*, bookings.store, tour-bookings.*
|   Investment        investments.*, projects.*, investment.*, center-guides.*, advertising.guide
|   Payments          payments.*, efinance.*, receipt.print
|   Syndicate / Marketplace (existing modules)
|
| Only cross-module (platform) routes stay here.
*/

// Legacy (removed) e-Finance callback, kept for reference — now served by
// Modules\Payments (route "efinance.callback", App\Services\Payments\PaymentService):
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

// Cross-module back-office monitor (reads every module; composition root).
Route::get('/dashboard-monitor', [\App\Http\Controllers\DashboardMonitorController::class, 'index'])->name('dashboard-monitor')
    ->middleware(['auth', 'can:view-dashboard-monitor']);
