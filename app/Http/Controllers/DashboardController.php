<?php

// Moved to \Modules\Citizen\Http\Controllers\DashboardController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\DashboardController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(DashboardController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\DashboardController::class, DashboardController::class);
}
