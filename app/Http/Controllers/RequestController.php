<?php

// Moved to \Modules\Citizen\Http\Controllers\RequestController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\RequestController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(RequestController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\RequestController::class, RequestController::class);
}
