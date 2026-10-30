<?php

// Citizen module API routes (moved verbatim from routes/api.php). Prefix "api", middleware "api".

use Illuminate\Support\Facades\Route;
use Modules\Citizen\Http\Controllers\Api\VoiceComplaintController;

Route::post('/voice-complaint', [VoiceComplaintController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('api.voice-complaint');
