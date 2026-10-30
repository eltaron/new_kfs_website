<?php

// Moved to \Modules\Estidama\Http\Controllers\EstidamaController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\EstidamaController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(EstidamaController::class, false)) {
    class_alias(\Modules\Estidama\Http\Controllers\EstidamaController::class, EstidamaController::class);
}
