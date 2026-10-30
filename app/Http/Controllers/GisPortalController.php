<?php

// Moved to \Modules\Gis\Http\Controllers\GisPortalController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\GisPortalController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(GisPortalController::class, false)) {
    class_alias(\Modules\Gis\Http\Controllers\GisPortalController::class, GisPortalController::class);
}
