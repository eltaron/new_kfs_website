<?php

// Moved to \Modules\Investment\Http\Controllers\AdvertisingGuideController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\AdvertisingGuideController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(AdvertisingGuideController::class, false)) {
    class_alias(\Modules\Investment\Http\Controllers\AdvertisingGuideController::class, AdvertisingGuideController::class);
}
