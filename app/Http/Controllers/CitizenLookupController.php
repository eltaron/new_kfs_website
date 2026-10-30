<?php

// Moved to \Modules\Citizen\Http\Controllers\CitizenLookupController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\CitizenLookupController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(CitizenLookupController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\CitizenLookupController::class, CitizenLookupController::class);
}
