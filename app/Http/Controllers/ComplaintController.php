<?php

// Moved to \Modules\Citizen\Http\Controllers\ComplaintController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ComplaintController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ComplaintController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\ComplaintController::class, ComplaintController::class);
}
