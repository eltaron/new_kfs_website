<?php

// Moved to \Modules\Citizen\Http\Controllers\ServiceController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ServiceController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ServiceController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\ServiceController::class, ServiceController::class);
}
