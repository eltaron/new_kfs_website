<?php

// Moved to \Modules\Content\Http\Controllers\GovernorateController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\GovernorateController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(GovernorateController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\GovernorateController::class, GovernorateController::class);
}
