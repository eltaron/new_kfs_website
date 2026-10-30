<?php

// Moved to \Modules\Investment\Http\Controllers\CenterGuideController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\CenterGuideController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(CenterGuideController::class, false)) {
    class_alias(\Modules\Investment\Http\Controllers\CenterGuideController::class, CenterGuideController::class);
}
