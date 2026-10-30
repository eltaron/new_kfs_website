<?php

// Moved to \Modules\Content\Http\Controllers\AboutPageController (modular monolith, see docs/architecture.md).
// This alias keeps old references and stored strings ('App\Http\Controllers\AboutPageController') working.
// Do not add code here.

namespace App\Http\Controllers;

if (! class_exists(AboutPageController::class, false)) {
    class_alias(\Modules\Content\Http\Controllers\AboutPageController::class, AboutPageController::class);
}
