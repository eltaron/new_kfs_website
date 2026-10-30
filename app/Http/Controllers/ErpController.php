<?php

// Moved to \Modules\Content\Http\Controllers\ErpController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\ErpController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(ErpController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\ErpController::class, ErpController::class);
}
