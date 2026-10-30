<?php

// Content module routes (moved verbatim from routes/web.php — URIs, names and middleware unchanged).
// Loaded by ContentServiceProvider inside the "web" middleware group.

use Illuminate\Support\Facades\Route;
use Modules\Content\Http\Controllers\AboutPageController;
use Modules\Content\Http\Controllers\CommentController;
use Modules\Content\Http\Controllers\DirectoryController;
use Modules\Content\Http\Controllers\ErpController;
use Modules\Content\Http\Controllers\ExamResultController;
use Modules\Content\Http\Controllers\GovernorateController;
use Modules\Content\Http\Controllers\HomeController;
use Modules\Content\Http\Controllers\OfficialsController;
use Modules\Content\Http\Controllers\PostController;
use Modules\Content\Http\Controllers\SearchController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/wait', [HomeController::class, 'wait'])->name('wait');

Route::get('/search', SearchController::class)->name('search');

Route::get('/posts', [PostController::class, 'index'])->name('posts.index');
Route::get('/posts/{post:slug}', [PostController::class, 'show'])->name('posts.show');
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store')->middleware('throttle:5,10');

Route::get('/exam-result', [ExamResultController::class, 'index'])->name('exam-results.index');
Route::post('/exam-result', [ExamResultController::class, 'lookup'])->name('exam-results.lookup')->middleware('throttle:10,1');

Route::get('/directory', [DirectoryController::class, 'index'])->name('directory.index');

Route::get('/about/governor-message', [AboutPageController::class, 'governorMessage'])->name('about.governor');

Route::get('/officials/{role}', [OfficialsController::class, 'show'])->name('officials.show');

Route::get('/about-us', [GovernorateController::class, 'about'])->name('about');

Route::middleware(['auth'])->group(function () {
    Route::get('/erp-governorate', [ErpController::class, 'index'])->name('employee.erp.index');
});
