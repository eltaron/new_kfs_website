<?php

// Moved to \Modules\Content\Http\Controllers\OfficialsController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\OfficialsController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(OfficialsController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\OfficialsController::class, OfficialsController::class);
}
