<?php

// Moved to \Modules\Citizen\Http\Controllers\ServiceSubmissionController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ServiceSubmissionController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ServiceSubmissionController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\ServiceSubmissionController::class, ServiceSubmissionController::class);
}
