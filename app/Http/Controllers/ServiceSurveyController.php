<?php

// Moved to \Modules\Citizen\Http\Controllers\ServiceSurveyController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ServiceSurveyController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ServiceSurveyController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\ServiceSurveyController::class, ServiceSurveyController::class);
}
