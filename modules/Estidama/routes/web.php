<?php

// Estidama / LMS module routes (moved verbatim from routes/web.php — URIs, names and middleware unchanged).

use Illuminate\Support\Facades\Route;
use Modules\Estidama\Http\Controllers\Estidama\CertificateController;
use Modules\Estidama\Http\Controllers\Estidama\ExamController;
use Modules\Estidama\Http\Controllers\Estidama\LearningController;
use Modules\Estidama\Http\Controllers\EstidamaController;

Route::get('/estidama/index', [EstidamaController::class, 'index'])->name('estidama.index');
Route::get('/estidama/programs', [EstidamaController::class, 'programs'])->name('estidama.programs');

// ─── Estidama LMS ───
Route::get('/estidama/programs/{program}', [LearningController::class, 'show'])
    ->whereNumber('program')->name('estidama.program.show');
Route::get('/estidama/certificates/verify/{serial?}', [CertificateController::class, 'verify'])
    ->where('serial', '[A-Za-z0-9\-]+')->middleware('throttle:30,1')->name('lms.certificates.verify');
Route::middleware('auth')->prefix('estidama')->name('lms.')->group(function () {
    $learning = LearningController::class;
    $exams = ExamController::class;

    Route::post('/programs/{program}/enroll', [$learning, 'enroll'])->whereNumber('program')->middleware('throttle:10,1')->name('enroll');
    Route::get('/my-learning', [$learning, 'dashboard'])->name('dashboard');
    Route::get('/learn/{program}', [$learning, 'course'])->whereNumber('program')->name('course');
    Route::get('/learn/{program}/lessons/{lesson}', [$learning, 'lesson'])->whereNumber(['program', 'lesson'])->name('lesson');
    Route::post('/learn/{program}/lessons/{lesson}/complete', [$learning, 'complete'])->whereNumber(['program', 'lesson'])->name('lesson.complete');
    Route::post('/learn/{program}/lessons/{lesson}/progress', [$learning, 'saveProgress'])->whereNumber(['program', 'lesson'])->middleware('throttle:120,1')->name('lesson.progress');
    Route::get('/lessons/{lesson}/media/{kind}', [$learning, 'media'])->whereNumber('lesson')->whereIn('kind', ['file', 'captions'])->name('lesson.media');
    Route::get('/enrollments/{enrollment}/certificate-file', [$learning, 'enrollmentCertificateFile'])->whereNumber('enrollment')->name('enrollment.certificate-file');

    Route::get('/learn/{program}/exams/{exam}', [$exams, 'show'])->whereNumber(['program', 'exam'])->name('exams.show');
    Route::post('/learn/{program}/exams/{exam}/start', [$exams, 'start'])->whereNumber(['program', 'exam'])->middleware('throttle:10,1')->name('exams.start');
    Route::get('/attempts/{attempt}', [$exams, 'take'])->whereNumber('attempt')->name('attempts.show');
    Route::post('/attempts/{attempt}/save', [$exams, 'save'])->whereNumber('attempt')->middleware('throttle:60,1')->name('attempts.save');
    Route::post('/attempts/{attempt}/submit', [$exams, 'submit'])->whereNumber('attempt')->name('attempts.submit');
    Route::get('/attempts/{attempt}/review', [$exams, 'review'])->whereNumber('attempt')->name('attempts.review');

    Route::get('/certificates/{serial}', [CertificateController::class, 'show'])
        ->where('serial', '[A-Za-z0-9\-]+')->name('certificates.show');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/estidama/apply/{program:id}', [EstidamaController::class, 'apply'])->name('estidama.apply');
    Route::post('/estidama/apply/{program:id}', [EstidamaController::class, 'storeApplication'])->name('estidama.storeApplication')->middleware('throttle:5,10');
});
