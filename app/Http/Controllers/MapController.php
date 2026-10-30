<?php

// Moved to \Modules\Gis\Http\Controllers\MapController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\MapController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(MapController::class, false)) {
    class_alias(\Modules\Gis\Http\Controllers\MapController::class, MapController::class);
}
