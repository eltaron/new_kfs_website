<?php

// Moved to \Modules\Tourism\Http\Controllers\LandmarkController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\LandmarkController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(LandmarkController::class, false)) {
    class_alias(\Modules\Tourism\Http\Controllers\LandmarkController::class, LandmarkController::class);
}
