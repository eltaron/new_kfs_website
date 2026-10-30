<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Module API routes live in modules/<Name>/routes/api.php (e.g. api.voice-complaint → Citizen).

Route::middleware(['auth:sanctum', 'throttle:30,1'])->post('/fcm-token', function (Request $request) {
    $request->validate(['token' => 'required|string|max:4096']);
    $request->user()->update(['fcm_token' => $request->token]);

    return response()->json(['message' => 'Token saved']);
})->name('api.fcm-token');
