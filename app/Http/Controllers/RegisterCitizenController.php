<?php

// Moved to \Modules\Citizen\Http\Controllers\RegisterCitizenController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\RegisterCitizenController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(RegisterCitizenController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\RegisterCitizenController::class, RegisterCitizenController::class);
}
