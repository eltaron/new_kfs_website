<?php

// Moved to \Modules\Investment\Http\Controllers\ProjectController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ProjectController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ProjectController::class, false)) {
    class_alias(\Modules\Investment\Http\Controllers\ProjectController::class, ProjectController::class);
}
