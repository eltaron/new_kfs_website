<?php

// Moved to \Modules\Citizen\Http\Controllers\EmergencyReportController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\EmergencyReportController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(EmergencyReportController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\EmergencyReportController::class, EmergencyReportController::class);
}
