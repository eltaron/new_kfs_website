<?php

// Citizen module routes (moved verbatim from routes/web.php — URIs, names and middleware unchanged).
// Loaded by CitizenServiceProvider inside the "web" middleware group.

use Illuminate\Support\Facades\Route;
use Modules\Citizen\Http\Controllers\CitizenLookupController;
use Modules\Citizen\Http\Controllers\ComplaintController;
use Modules\Citizen\Http\Controllers\ContactController;
use Modules\Citizen\Http\Controllers\DashboardController;
use Modules\Citizen\Http\Controllers\EmergencyReportController;
use Modules\Citizen\Http\Controllers\LoginCitizenController;
use Modules\Citizen\Http\Controllers\RegisterCitizenController;
use Modules\Citizen\Http\Controllers\RequestController;
use Modules\Citizen\Http\Controllers\ServiceController;
use Modules\Citizen\Http\Controllers\ServiceSubmissionController;
use Modules\Citizen\Http\Controllers\ServiceSurveyController;
use Modules\Citizen\Http\Controllers\SuggestionController;

Route::get('/contact', [ContactController::class, 'index'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,10');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service:id}', [ServiceController::class, 'show'])->name('services.show');

Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');

Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store')->middleware('throttle:5,10');

Route::get('/suggestions/create', [SuggestionController::class, 'create'])->name('suggestions.create');
Route::post('/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store')->middleware('throttle:5,10');

Route::get('/survey/service-center', [ServiceSurveyController::class, 'create'])->name('surveys.service.create');
Route::post('/survey/service-center', [ServiceSurveyController::class, 'store'])->name('surveys.service.store')->middleware('throttle:3,10');

Route::get('/register/citizen', [RegisterCitizenController::class, 'create'])->name('register.citizen');
Route::post('/register/citizen', [RegisterCitizenController::class, 'store'])->name('register.citizen.store')->middleware('throttle:5,10');

Route::get('/login', [LoginCitizenController::class, 'create'])->name('login');
Route::post('/login', [LoginCitizenController::class, 'store'])->middleware('throttle:login');
Route::post('/logout', [LoginCitizenController::class, 'destroy'])->name('logout');

Route::get('/emergency-report', [EmergencyReportController::class, 'create'])->name('emergency.create');
Route::post('/emergency-report', [EmergencyReportController::class, 'store'])->name('emergency.store')->middleware('throttle:5,10');
Route::get('/emergency-report/villages/{center_id}', [EmergencyReportController::class, 'getVillages'])->name('emergency.villages');

Route::middleware(['auth'])->group(function () {
    Route::get('/citizen/submissions/{id}', [DashboardController::class, 'show'])
        ->name('citizen.submissions.show');
    Route::post('/services/{service}/submit', [ServiceSubmissionController::class, 'store'])
        ->name('services.submit');
    Route::get('/services/success/{submission}', [ServiceSubmissionController::class, 'success'])
        ->name('services.success');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('citizen.dashboard');
    Route::get('/dashboard/services', [DashboardController::class, 'services'])->name('citizen.dashboard.services');
    Route::get('/dashboard/gis', [DashboardController::class, 'gis'])->name('citizen.dashboard.gis');
    Route::get('/dashboard/estidama', [DashboardController::class, 'estidama'])->name('citizen.dashboard.estidama');
    Route::get('/dashboard/monitoring', [DashboardController::class, 'monitoring'])->name('citizen.dashboard.monitoring');
    Route::get('/dashboard/fulfillment', [DashboardController::class, 'fulfillment'])->name('citizen.dashboard.fulfillment');
});

// صفحة البحث عن قرار الإشراف للمواطنين
Route::get('/citizen-lookup', [CitizenLookupController::class, 'index'])->name('citizen-lookup');
Route::get('/citizen-lookup/search', [CitizenLookupController::class, 'search'])->middleware('throttle:10,1')->name('citizen-lookup.search');
