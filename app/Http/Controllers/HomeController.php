<?php

// Moved to \Modules\Content\Http\Controllers\HomeController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\HomeController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(HomeController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\HomeController::class, HomeController::class);
}
