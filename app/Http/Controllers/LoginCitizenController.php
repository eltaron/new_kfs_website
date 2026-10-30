<?php

// Moved to \Modules\Citizen\Http\Controllers\LoginCitizenController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\LoginCitizenController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(LoginCitizenController::class, false)) {
    class_alias(\Modules\Citizen\Http\Controllers\LoginCitizenController::class, LoginCitizenController::class);
}
